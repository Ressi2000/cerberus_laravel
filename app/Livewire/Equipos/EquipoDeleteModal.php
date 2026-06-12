<?php

namespace App\Livewire\Equipos;

use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\User;
use App\Notifications\EquipoDadoDeBajaNotification;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class EquipoDeleteModal extends Component
{
    public bool $open = false;
    public ?Equipo $equipo = null;

    #[On('openEquipoDelete')]
    public function openEquipoDelete(int $id): void
    {
        $equipo = Equipo::with(['categoria', 'estado'])->findOrFail($id);

        $this->authorize('delete', $equipo);

        // ── Bloquear si el equipo está asignado ──────────────────────────────
        $tieneAsignacionActiva = \App\Models\AsignacionItem::where('equipo_id', $id)
            ->where('devuelto', false)
            ->whereHas('asignacion', fn($q) => $q->where('estado', 'Activa'))
            ->exists();

        if ($tieneAsignacionActiva) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'No se puede dar de baja: el equipo tiene una asignación activa. Primero realiza la devolución.'
            );
            return;
        }

        $this->equipo = $equipo;
        $this->open   = true;
    }

    public function desactivar(): void
    {
        if (! $this->equipo) return;

        $this->authorize('delete', $this->equipo);

        $equipo = $this->equipo;

        try {
            $estadoBaja = EstadoEquipo::where('nombre', 'Dado de baja')->value('id');
            $codigo     = $equipo->codigo_interno;

            $equipo->update([
                'activo'    => false,
                'estado_id' => $estadoBaja ?? $equipo->estado_id,
            ]);

            $this->close();
            $this->dispatch('toast', type: 'success', message: "Equipo «{$codigo}» dado de baja correctamente.");
            $this->dispatch('equipoActualizado');
        } catch (\Exception $e) {
            Log::error('Error desactivando equipo: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Ocurrió un error al dar de baja el equipo.');
            $this->close();
            return;
        }

        rescue(function () use ($equipo) {
            $notif = new EquipoDadoDeBajaNotification($equipo, auth()->user());
            User::role('Administrador')->each(fn($admin) => $admin->notify($notif));
        }, report: true);
    }

    public function close(): void
    {
        $this->reset(['open', 'equipo']);
    }

    public function render()
    {
        return view('livewire.equipos.equipo-delete-modal');
    }
}
