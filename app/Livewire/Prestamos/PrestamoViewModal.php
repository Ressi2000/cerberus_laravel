<?php

namespace App\Livewire\Prestamos;

use App\Models\Prestamo;
use App\Models\PrestamoItem;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * PrestamoViewModal
 *
 * Modal de detalle centrado en el USUARIO — muestra todos sus equipos
 * prestados actualmente (Activo|Vencido) y el acceso al historial completo.
 *
 * Análogo a App\Livewire\Asignaciones\AsignacionViewModal, sin las acciones
 * de vincular/desvincular periférico (PrestamoItem no soporta esa función).
 *
 * Escucha: openPrestamoView con { userId: int }
 */
class PrestamoViewModal extends Component
{
    public bool $open   = false;
    public ?int $userId = null;

    #[On('openPrestamoView')]
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

    #[Computed]
    public function usuario(): ?User
    {
        if (! $this->userId) return null;

        return User::with([
            'cargo',
            'departamento',
            'empresaNomina',
            'ubicacion',
            'jefe',
        ])->find($this->userId);
    }

    #[Computed]
    public function prestamoActivo(): ?Prestamo
    {
        if (! $this->userId) return null;

        return Prestamo::where('usuario_id', $this->userId)
            ->whereIn('estado', ['Activo', 'Vencido'])
            ->orderByDesc('fecha_prestamo')
            ->first();
    }

    #[Computed]
    public function equiposActivos()
    {
        if (! $this->userId) return collect();

        return PrestamoItem::with([
            'equipo.categoria',
            'equipo.atributosActuales.atributo',
            'prestamo',
            'prestamo.empresa',
            'hijos.equipo.categoria',
        ])
            ->whereHas('prestamo', fn ($q) =>
                $q->where('usuario_id', $this->userId)
                  ->whereIn('estado', ['Activo', 'Vencido'])
            )
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->orderBy('created_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.prestamos.prestamo-view-modal');
    }
}
