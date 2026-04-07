<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * DevolverUsuario
 *
 * Devolución de equipos centrada en el USUARIO — muestra todos
 * sus equipos activos (de cualquier asignación) en una sola pantalla.
 *
 * Diferencia con DevolverAsignacion:
 *   DevolverAsignacion → devuelve equipos de UNA asignación específica
 *   DevolverUsuario    → devuelve cualquier equipo del usuario sin importar
 *                        de qué asignación viene (vista unificada)
 *
 * Al confirmar:
 *   - Llama registrarDevolucion() en cada item seleccionado
 *   - Los items con hijos (periféricos) los devuelven en cascada
 *   - Recalcula estado de cada asignación afectada
 */
class DevolverUsuario extends Component
{
    public int $usuarioId;

    // IDs de AsignacionItem seleccionados para devolver
    public array $seleccionados = [];

    // Observaciones por item_id
    public array $observaciones = [];

    // Marcar todos
    public bool $seleccionarTodos = false;

    public function mount(User $usuario): void
    {
        $this->authorize('viewAny', Asignacion::class);
        $this->usuarioId = $usuario->id;

        // Inicializar observaciones vacías para cada item activo
        foreach ($this->itemsActivos as $item) {
            $this->observaciones[$item->id] = '';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function usuario(): User
    {
        return User::with(['cargo', 'empresaNomina', 'ubicacion'])->findOrFail($this->usuarioId);
    }

    /**
     * Solo items PRINCIPALES activos (los periféricos se muestran anidados
     * y se devuelven en cascada automáticamente con su padre).
     */
    #[Computed]
    public function itemsActivos()
    {
        return AsignacionItem::with([
            'equipo.categoria',
            'asignacion',
            'hijos.equipo.categoria',
        ])
            ->whereHas('asignacion', fn($q) =>
                $q->where('usuario_id', $this->usuarioId)->where('estado', 'Activa')
            )
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')   // solo principales
            ->orderBy('created_at')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function updatedSeleccionarTodos(bool $value): void
    {
        $this->seleccionados = $value
            ? $this->itemsActivos->pluck('id')->map(fn($id) => (string) $id)->toArray()
            : [];
    }

    public function updatedSeleccionados(): void
    {
        $this->seleccionarTodos = count($this->seleccionados) === $this->itemsActivos->count();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->authorize('viewAny', Asignacion::class);

        if (empty($this->seleccionados)) {
            $this->addError('seleccionados', 'Selecciona al menos un equipo para devolver.');
            return;
        }

        $this->validate(
            collect($this->observaciones)->mapWithKeys(
                fn($v, $k) => ["observaciones.{$k}" => 'nullable|string|max:1000']
            )->toArray()
        );

        try {
            DB::transaction(function () {
                $items = AsignacionItem::whereIn('id', $this->seleccionados)
                    ->whereHas('asignacion', fn($q) => $q->where('usuario_id', $this->usuarioId))
                    ->where('devuelto', false)
                    ->get();

                foreach ($items as $item) {
                    $obs = $this->observaciones[$item->id] ?? null;
                    // registrarDevolucion devuelve el item + sus hijos en cascada
                    $item->registrarDevolucion($obs ?: null);
                }
            });

            $cant = count($this->seleccionados);
            session()->flash('success', "Devolución registrada. {$cant} equipo(s) liberado(s).");
            $this->redirect(route('admin.asignaciones.historial', $this->usuarioId), navigate: true);

        } catch (\Exception $e) {
            Log::error('DevolverUsuario@confirmar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar la devolución.');
        }
    }

    public function render()
    {
        return view('livewire.asignaciones.devolver-usuario');
    }
}