<?php

namespace App\Console\Commands;

use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Prestamo;
use App\Models\User;
use App\Notifications\EquipoReparacionExtendidaNotification;
use App\Notifications\GarantiaProximaVencerNotification;
use App\Notifications\PrestamoProximoAVencerNotification;
use App\Notifications\PrestamoVencidoNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotificarAlertas extends Command
{
    protected $signature   = 'cerberus:notificar-alertas';
    protected $description = 'Envía notificaciones diarias: préstamos vencidos/por vencer, garantías y equipos en reparación extendida.';

    public function handle(): int
    {
        $this->notificarPrestamosVencidos();
        $this->notificarPrestamosProximos();
        $this->notificarGarantiasProximas();
        $this->notificarReparacionesExtendidas();

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
                    $user->notify($notif);
                }

                $this->line("  ✓ Préstamo vencido #{$prestamo->numero}");
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
                    $user->notify($notif);
                }

                $this->line("  ✓ Préstamo próximo #{$prestamo->numero} (en {$dias}d)");
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
                    $user->notify($notif);
                }

                $this->line("  ✓ Garantía {$equipo->nombre} (en {$dias}d)");
            });
    }

    private function notificarReparacionesExtendidas(): void
    {
        $umbralDias = 10;

        $estadoReparacion = EstadoEquipo::where('nombre', EstadoEquipo::EN_REPARACION)->first();
        if (! $estadoReparacion) {
            return;
        }

        // Detectar por la auditoría cuándo cambió al estado de reparación
        // Fallback: usar updated_at del equipo cuando estado_id cambió
        Equipo::where('estado_id', $estadoReparacion->id)
            ->where('updated_at', '<=', now()->subDays($umbralDias))
            ->with('empresa')
            ->each(function (Equipo $equipo) {
                $dias = (int) now()->diffInDays($equipo->updated_at);
                $notif = new EquipoReparacionExtendidaNotification($equipo, $dias);

                $destinatarios = $equipo->empresa_id
                    ? $this->analistasDeEmpresa($equipo->empresa_id)
                    : collect();

                foreach ($destinatarios->merge($this->admins())->unique('id') as $user) {
                    $user->notify($notif);
                }

                $this->line("  ✓ Reparación extendida: {$equipo->nombre} ({$dias}d)");
            });
    }
}
