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

/**
 * DevolverUsuario — v2
 *
 * ── CAMBIOS v2 ──────────────────────────────────────────────────────────────
 *
 * Ahora muestra TODOS los items activos del usuario (principales Y periféricos)
 * con checkbox individual. Misma lógica granular que DevolverAsignacion v3.
 *
 * El analista puede devolver un ratón sin devolver la laptop, o viceversa.
 * ────────────────────────────────────────────────────────────────────────────
 */

class DevolverUsuario extends Component
{
    public int $usuarioId;
 
    public array $seleccionados    = [];
    public array $observaciones    = [];
    public bool  $seleccionarTodos = false;
 
    public function mount(User $usuario): void
    {
        $this->authorize('viewAny', Asignacion::class);
        $this->usuarioId = $usuario->id;
 
        foreach ($this->todosLosItemsActivos as $item) {
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
     * Todos los items activos del usuario: principales Y periféricos,
     * de cualquier asignación activa, ordenados agrupando cada periférico
     * junto a su padre.
     */
    #[Computed]
    public function todosLosItemsActivos()
    {
        return AsignacionItem::with([
            'equipo.categoria',
            'asignacion',
            'padre.equipo',
            'hijosActivos.equipo.categoria',
        ])
            ->whereHas('asignacion', fn ($q) =>
                $q->where('usuario_id', $this->usuarioId)->where('estado', 'Activa')
            )
            ->where('devuelto', false)
            ->orderByRaw('COALESCE(equipo_padre_id, id)')
            ->orderBy('equipo_padre_id')
            ->orderBy('id')
            ->get();
    }
 
    /**
     * Principales seleccionados con periféricos activos no seleccionados.
     */
    #[Computed]
    public function principalesConHuerfanos()
    {
        $selIds = collect($this->seleccionados)->map(fn ($id) => (int) $id);
 
        return $this->todosLosItemsActivos
            ->whereNull('equipo_padre_id')
            ->filter(fn ($item) => $selIds->contains($item->id))
            ->filter(fn ($item) =>
                $item->hijosActivos->isNotEmpty() &&
                $item->hijosActivos->contains(fn ($hijo) => ! $selIds->contains($hijo->id))
            );
    }
 
    // ─────────────────────────────────────────────────────────────────────────
 
    public function updatedSeleccionarTodos(bool $value): void
    {
        $this->seleccionados = $value
            ? $this->todosLosItemsActivos->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }
 
    public function updatedSeleccionados(): void
    {
        $total = $this->todosLosItemsActivos->count();
        $this->seleccionarTodos = $total > 0 && count($this->seleccionados) === $total;
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
                fn ($v, $k) => ["observaciones.{$k}" => 'nullable|string|max:1000']
            )->toArray()
        );
 
        try {
            DB::transaction(function () {
                $items = AsignacionItem::whereIn('id', $this->seleccionados)
                    ->whereHas('asignacion', fn ($q) => $q->where('usuario_id', $this->usuarioId))
                    ->where('devuelto', false)
                    ->get();
 
                foreach ($items as $item) {
                    $item->registrarDevolucion($this->observaciones[$item->id] ?: null);
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
 
    // ─────────────────────────────────────────────────────────────────────────
 
    public function render()
    {
        return view('livewire.asignaciones.devolver-usuario');
    }
}