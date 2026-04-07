<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * HistorialUsuario
 *
 * Página dedicada: /admin/asignaciones/historial/{user}
 * Sección 1 → Equipos activos actuales (principales + periféricos anidados)
 * Sección 2 → Timeline de todas las asignaciones
 * Sección 3 → Descarga de planillas
 */
class HistorialUsuario extends Component
{
    public int $usuarioId;

    public function mount(User $usuario): void
    {
        $this->authorize('viewAny', Asignacion::class);

        /** @var User $actor */
        $actor = Auth::user();

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            $visible = Asignacion::where('usuario_id', $usuario->id)
                ->where('empresa_id', $actor->empresa_activa_id)
                ->exists();
            abort_unless($visible, 403);
        }

        $this->usuarioId = $usuario->id;
    }

    #[Computed]
    public function usuario(): User
    {
        return User::with(['cargo', 'departamento', 'empresaNomina', 'ubicacion', 'jefe'])
            ->findOrFail($this->usuarioId);
    }

    /** Items principales activos (periféricos anidados via hijos) */
    #[Computed]
    public function equiposActivos()
    {
        return AsignacionItem::with([
            'equipo.categoria',
            'equipo.atributosActuales.atributo',
            'asignacion',
            'hijos.equipo.categoria',
        ])
            ->whereHas('asignacion', fn($q) =>
                $q->where('usuario_id', $this->usuarioId)->where('estado', 'Activa')
            )
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->orderBy('created_at')
            ->get();
    }

    /** Timeline completo */
    #[Computed]
    public function asignaciones()
    {
        return Asignacion::with([
            'analista',
            'empresa',
            'items' => fn($q) => $q->whereNull('equipo_padre_id')->with([
                'equipo.categoria',
                'hijos.equipo.categoria',
                'devueltoPor',
            ]),
        ])
            ->where('usuario_id', $this->usuarioId)
            ->orderByDesc('fecha_asignacion')
            ->get();
    }

    public function render()
    {
        return view('livewire.asignaciones.historial-usuario');
    }
}