<?php

namespace App\Livewire\Asignaciones;

use App\Models\AsignacionItem;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * AsignacionViewModal
 *
 * Modal de detalle centrado en el USUARIO — muestra todos sus equipos
 * activos actuales y el acceso al historial completo.
 *
 * Escucha: openAsignacionView con { userId: int }
 *
 * CORRECCIÓN: El evento envía 'userId' (no 'id') porque la tabla
 * está orientada al usuario, no a la asignación individual.
 * El método abrir() debe recibir int $userId para que Livewire
 * mapee correctamente el parámetro por nombre.
 */
class AsignacionViewModal extends Component
{
    public bool $open    = false;
    public ?int $userId  = null;

    #[On('openAsignacionView')]
    public function abrir(int $userId): void
    {
        $this->userId = $userId;
        $this->open   = true;
    }

    public function cerrar(): void
    {
        $this->open   = false;
        $this->userId = null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fix 1: escuchar perifericoVinculado e invalidar el cache
    // ─────────────────────────────────────────────────────────────────────────
 
    /**
     * Cuando el VincularPerifericoModal confirma o desvincula, emite este evento.
     * Invalidamos la computed property para que Livewire re-ejecute la query
     * en el próximo render y el modal muestre los datos actualizados.
     */
    #[On('perifericoVinculado')]
    public function refrescarEquipos(): void
    {
        
    }

    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function usuario(): ?User
    {
        if (!$this->userId) return null;

        return User::with([
            'cargo',
            'departamento',
            'empresaNomina',
            'ubicacion',
            'jefe',
        ])->find($this->userId);
    }

    /**
     * Items activos del usuario: solo principales, hijos anidados via relación.
     * Se invalida desde refrescarEquipos() tras cada vincular/desvincular.
     */
    #[Computed]
    public function equiposActivos()
    {
        if (! $this->userId) return collect();
 
        return AsignacionItem::with([
            'equipo.categoria',
            'equipo.atributosActuales.atributo',
            'asignacion',
            'asignacion.empresa',
            'hijos.equipo.categoria',
            'hijos.asignacion',       // ← necesario para mostrar badge "Otra asig."
        ])
            ->whereHas('asignacion', fn ($q) =>
                $q->where('usuario_id', $this->userId)
                  ->where('estado', 'Activa')
            )
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->orderBy('created_at')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.asignaciones.asignacion-view-modal');
    }
}