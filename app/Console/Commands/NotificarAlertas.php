<?php

namespace App\Console\Commands;

use App\Models\AsignacionItem;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use App\Models\Prestamo;
use App\Models\User;
use App\Notifications\EquipoReparacionExtendidaNotification;
use App\Notifications\GarantiaProximaVencerNotification;
use App\Notifications\MantenimientoProximoNotification;
use App\Notifications\MantenimientoProximoUsuarioNotification;
use App\Notifications\PlanMantenimientoProximoNotification;
use App\Notifications\PrestamoProximoAVencerNotification;
use App\Notifications\PrestamoVencidoNotification;
use App\Notifications\RotacionAsignacionRecomendadaNotification;
use Illuminate\Console\Command;

class NotificarAlertas extends Command
{
    /** Días antes de un mantenimiento preventivo en que se avisa a los analistas. */
    const DIAS_AVISO_MANTENIMIENTO = [14, 7, 1];

    protected $signature   = 'cerberus:notificar-alertas';
    protected $description = 'Envía notificaciones diarias: préstamos vencidos/por vencer y garantías próximas a vencer.';

    public function handle(): int
    {
        $marcados = Prestamo::marcarVencidosAutomaticamente();
        if ($marcados > 0) {
            $this->line("  ✓ {$marcados} préstamo(s) marcado(s) como Vencido.");
        }

        $this->notificarPrestamosVencidos();
        $this->notificarPrestamosProximos();
        $this->notificarGarantiasProximas();
        $this->notificarRotacionesRecomendadas();
        $this->notificarReparacionesExtendidas();
        $this->notificarMantenimientosProximos();
        $this->notificarUsuariosEquipoProximo();

        $this->info('Alertas Cerberus enviadas correctamente.');
        return self::SUCCESS;
    }

    private function admins()
    {
        return User::role('Administrador')->get();
    }

    /**
     * Analistas que atienden la empresa: la tienen activa o asignada (un
     * analista con varias empresas recibe los avisos de todas, no solo de la
     * que tenga abierta ese día).
     */
    private function analistasDeEmpresa(int $empresaId)
    {
        return User::role('Analista')
            ->where('estado', 'Activo')
            ->where(fn ($q) => $q->where('empresa_activa_id', $empresaId)
                ->orWhereHas('empresasAsignadas', fn ($e) => $e->whereKey($empresaId)))
            ->get();
    }

    private function yaNotificadoHoy(User $user, string $type, string $metaKey, int $entityId): bool
    {
        return $user->notifications()
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->whereRaw("json_extract(data, '$.meta.{$metaKey}') = ?", [$entityId])
            ->exists();
    }

    private function notificarPrestamosVencidos(): void
    {
        Prestamo::whereNull('fecha_devolucion_real')
            ->whereNotNull('fecha_devolucion_esperada')
            ->where('fecha_devolucion_esperada', '<', now()->startOfDay())
            ->with('empresa')
            ->each(function (Prestamo $prestamo) {
                $notif = new PrestamoVencidoNotification($prestamo);

                $destinatarios = $prestamo->empresa_id
                    ? $this->analistasDeEmpresa($prestamo->empresa_id)
                    : collect();

                foreach ($destinatarios->merge($this->admins())->unique('id') as $user) {
                    if (! $this->yaNotificadoHoy($user, PrestamoVencidoNotification::class, 'prestamo_id', $prestamo->id)) {
                        $user->notify($notif);
                    }
                }

                $this->line("  ✓ Préstamo vencido #PRE-{$prestamo->id}");
            });
    }

    private function notificarPrestamosProximos(): void
    {
        $umbral = 3; // días de anticipación

        Prestamo::whereNull('fecha_devolucion_real')
            ->whereNotNull('fecha_devolucion_esperada')
            ->whereBetween('fecha_devolucion_esperada', [now()->startOfDay(), now()->addDays($umbral)->endOfDay()])
            ->with('empresa')
            ->each(function (Prestamo $prestamo) {
                $dias = now()->startOfDay()->diffInDays($prestamo->fecha_devolucion_esperada);
                $notif = new PrestamoProximoAVencerNotification($prestamo, (int) $dias);

                $destinatarios = $prestamo->empresa_id
                    ? $this->analistasDeEmpresa($prestamo->empresa_id)
                    : collect();

                foreach ($destinatarios->merge($this->admins())->unique('id') as $user) {
                    if (! $this->yaNotificadoHoy($user, PrestamoProximoAVencerNotification::class, 'prestamo_id', $prestamo->id)) {
                        $user->notify($notif);
                    }
                }

                $this->line("  ✓ Préstamo próximo #PRE-{$prestamo->id} (en {$dias}d)");
            });
    }

    private function notificarGarantiasProximas(): void
    {
        $umbral = 30; // días de anticipación

        Equipo::whereNotNull('fecha_garantia_fin')
            ->whereBetween('fecha_garantia_fin', [now()->startOfDay(), now()->addDays($umbral)->endOfDay()])
            ->with('empresa')
            ->each(function (Equipo $equipo) {
                $dias = (int) now()->startOfDay()->diffInDays($equipo->fecha_garantia_fin);
                $notif = new GarantiaProximaVencerNotification($equipo, $dias);

                $destinatarios = $equipo->empresa_id
                    ? $this->analistasDeEmpresa($equipo->empresa_id)
                    : collect();

                foreach ($destinatarios->merge($this->admins())->unique('id') as $user) {
                    if (! $this->yaNotificadoHoy($user, GarantiaProximaVencerNotification::class, 'equipo_id', $equipo->id)) {
                        $user->notify($notif);
                    }
                }

                $this->line("  ✓ Garantía {$equipo->nombre} (en {$dias}d)");
            });
    }

    /**
     * NO es vida útil/obsolescencia del equipo: alerta cuando un mismo
     * receptor lleva más tiempo con un mismo equipo que el periodo de
     * rotación recomendado configurado en la categoría.
     */
    private function notificarRotacionesRecomendadas(): void
    {
        AsignacionItem::with(['equipo.categoria', 'asignacion.usuario', 'asignacion.empresa'])
            ->whereHas('asignacion', fn ($q) => $q->where('estado', 'Activa'))
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->whereHas('equipo.categoria', fn ($q) => $q->whereNotNull('meses_rotacion_asignacion'))
            ->get()
            ->filter(fn (AsignacionItem $item) => $item->superaRotacionRecomendada())
            ->each(function (AsignacionItem $item) {
                $meses  = $item->mesesConReceptor();
                $umbral = $item->mesesRotacionRecomendada();
                $notif  = new RotacionAsignacionRecomendadaNotification($item, $meses, $umbral);

                $empresaId      = $item->asignacion->empresa_id;
                $destinatarios  = $empresaId
                    ? $this->analistasDeEmpresa($empresaId)
                    : collect();

                foreach ($destinatarios->merge($this->admins())->unique('id') as $user) {
                    if (! $this->yaNotificadoHoy($user, RotacionAsignacionRecomendadaNotification::class, 'asignacion_item_id', $item->id)) {
                        $user->notify($notif);
                    }
                }

                $this->line("  ✓ Rotación recomendada: {$item->equipo->codigo_interno} ({$meses}m/{$umbral}m)");
            });
    }

    /**
     * Reparaciones correctivas abiertas por demasiado tiempo. NO tiene
     * relación con "días de garantía" ni con vida útil — solo mide cuánto
     * lleva el caso abierto desde fecha_inicio.
     */
    private function notificarReparacionesExtendidas(): void
    {
        $umbralDias = 7;

        Mantenimiento::correctivos()->abiertos()
            ->where('fecha_inicio', '<=', now()->subDays($umbralDias)->toDateString())
            ->with('equipo.empresa')
            ->get()
            ->each(function (Mantenimiento $mantenimiento) {
                $equipo = $mantenimiento->equipo;
                if (! $equipo) return;

                $dias  = (int) \Carbon\Carbon::parse($mantenimiento->fecha_inicio)->diffInDays(now());
                $notif = new EquipoReparacionExtendidaNotification($equipo, $dias);

                $destinatarios = $mantenimiento->empresa_id
                    ? $this->analistasDeEmpresa($mantenimiento->empresa_id)
                    : collect();

                foreach ($destinatarios->merge($this->admins())->unique('id') as $user) {
                    if (! $this->yaNotificadoHoy($user, EquipoReparacionExtendidaNotification::class, 'equipo_id', $equipo->id)) {
                        $user->notify($notif);
                    }
                }

                $this->line("  ✓ Reparación extendida: {$equipo->codigo_interno} ({$dias}d)");
            });
    }

    /**
     * Mantenimientos preventivos que se acercan: aviso en el sistema y por
     * correo a los analistas de la empresa (y al responsable, si lo hay)
     * exactamente 14, 7 y 1 día(s) antes — no todos los días.
     *
     *  - Del cronograma: UN aviso por plan (lote), con cuántos equipos son.
     *    Se mira el plan y no los casos porque los casos del lote se generan
     *    recién 7 días antes (PlanMantenimiento::DIAS_ANTELACION_GENERACION).
     *  - Sueltos (creados a mano, sin plan): un aviso por caso.
     */
    private function notificarMantenimientosProximos(): void
    {
        $fechas = collect(self::DIAS_AVISO_MANTENIMIENTO)
            ->mapWithKeys(fn ($d) => [now()->addDays($d)->toDateString() => $d]);

        PlanMantenimiento::activos()
            ->whereIn('fecha_proximo', $fechas->keys())
            ->with(['categoria', 'empresa', 'departamento', 'responsable'])
            ->get()
            ->each(function (PlanMantenimiento $plan) use ($fechas) {
                $dias    = $fechas[$plan->fecha_proximo->toDateString()];
                $equipos = $plan->equiposAlcanzados()->count();
                $notif   = new PlanMantenimientoProximoNotification($plan, $dias, $equipos);

                foreach ($this->destinatariosMantenimiento($plan->empresa_id, $plan->responsable_id) as $user) {
                    if (! $this->yaNotificadoHoy($user, PlanMantenimientoProximoNotification::class, 'plan_id', $plan->id)) {
                        $user->notify($notif);
                    }
                }

                $this->line("  ✓ Plan de mantenimiento #{$plan->id} ({$plan->categoria?->nombre}) en {$dias}d");
            });

        Mantenimiento::preventivos()->abiertos()
            ->whereNull('plan_mantenimiento_id')
            ->whereIn('proxima_fecha_programada', $fechas->keys())
            ->with('equipo', 'empresa')
            ->get()
            ->each(function (Mantenimiento $mantenimiento) use ($fechas) {
                $dias  = $fechas[$mantenimiento->proxima_fecha_programada->toDateString()];
                $notif = new MantenimientoProximoNotification($mantenimiento, $dias);

                foreach ($this->destinatariosMantenimiento($mantenimiento->empresa_id, $mantenimiento->responsable_id) as $user) {
                    if (! $this->yaNotificadoHoy($user, MantenimientoProximoNotification::class, 'mantenimiento_id', $mantenimiento->id)) {
                        $user->notify($notif);
                    }
                }

                $this->line("  ✓ Mantenimiento próximo: {$mantenimiento->equipo?->codigo_interno} (en {$dias}d)");
            });
    }

    /** Analistas de la empresa + el responsable asignado + administradores, sin repetir. */
    private function destinatariosMantenimiento(?int $empresaId, ?int $responsableId)
    {
        $responsable = $responsableId ? User::whereKey($responsableId)->where('estado', 'Activo')->get() : collect();

        return ($empresaId ? $this->analistasDeEmpresa($empresaId) : collect())
            ->merge($responsable)
            ->merge($this->admins())
            ->unique('id');
    }

    /**
     * Correo al usuario que tiene el equipo asignado, exactamente una semana
     * antes del mantenimiento preventivo programado — para que lo tenga
     * listo el día de la jornada. Solo equipos con asignación PERSONAL (un
     * equipo asignado a un área común no tiene un usuario individual a quién
     * avisarle). Dispara una sola vez (diasRestantes === 7 exacto, no un
     * rango) porque es un correo puntual, no una alerta de gestión que deba
     * repetirse cada día mientras se acerca la fecha.
     */
    private function notificarUsuariosEquipoProximo(): void
    {
        $diasAntelacion = 7;

        Mantenimiento::preventivos()->abiertos()
            ->whereNotNull('proxima_fecha_programada')
            ->whereDate('proxima_fecha_programada', now()->addDays($diasAntelacion)->toDateString())
            ->with('equipo.asignacionItemActivo.asignacion.usuario')
            ->get()
            ->each(function (Mantenimiento $mantenimiento) use ($diasAntelacion) {
                $equipo = $mantenimiento->equipo;
                $usuario = $equipo?->asignacionItemActivo?->asignacion?->usuario;

                if (! $usuario || ! $usuario->email) {
                    return;
                }

                $usuario->notify(new MantenimientoProximoUsuarioNotification($mantenimiento, $diasAntelacion));

                $this->line("  ✓ Aviso a usuario: {$equipo->codigo_interno} -> {$usuario->email}");
            });
    }

}

