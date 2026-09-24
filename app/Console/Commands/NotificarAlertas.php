<?php

namespace App\Console\Commands;

use App\Models\AsignacionItem;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\User;
use App\Notifications\GarantiaProximaVencerNotification;
use App\Notifications\PrestamoProximoAVencerNotification;
use App\Notifications\PrestamoVencidoNotification;
use App\Notifications\RotacionAsignacionRecomendadaNotification;
use Illuminate\Console\Command;

class NotificarAlertas extends Command
{
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

        $this->info('Alertas Cerberus enviadas correctamente.');
        return self::SUCCESS;
    }

    private function admins()
    {
        return User::role('Administrador')->get();
    }

    private function analistasDeEmpresa(int $empresaId)
    {
        return User::role('Analista')
            ->where('empresa_activa_id', $empresaId)
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

}

