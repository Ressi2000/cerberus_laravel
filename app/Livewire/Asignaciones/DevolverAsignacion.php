<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * DevolverAsignacion — v3
 *
 * ── CAMBIOS v3 ──────────────────────────────────────────────────────────────
 *
 * Selección granular: muestra TODOS los items activos (principales Y periféricos)
 * cada uno con su propio checkbox. El analista elige exactamente qué devuelve.
 *
 * Aviso de huérfanos: si se devuelve un principal con periféricos activos que
 * NO se seleccionaron, la UI avisa que esos periféricos serán promovidos
 * automáticamente a principales (Opción A).
 *
 * Sin cascada automática: registrarDevolucion() en el modelo ya no devuelve
 * hijos automáticamente. Cada item se gestiona de forma independiente.
 * ────────────────────────────────────────────────────────────────────────────
 */
class DevolverAsignacion extends Component
{
    public int $asignacionId;

    public array $seleccionados    = [];
    public array $observaciones    = [];
    public bool  $seleccionarTodos = false;

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(Asignacion $asignacion): void
    {
        $this->authorize('devolver', $asignacion);

        if ($asignacion->estado === 'Cerrada') {
            session()->flash('error', 'Esta asignación ya está cerrada y no admite devoluciones.');
            $this->redirect(route('admin.asignaciones.index'), navigate: true);
            return;
        }

        $this->asignacionId = $asignacion->id;

        foreach ($this->todosLosItemsActivos as $item) {
            $this->observaciones[$item->id] = '';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function asignacion(): Asignacion
    {
        return Asignacion::with([
            'empresa', 'analista',
            'usuario.cargo', 'usuario.ubicacion',
            'areaEmpresa', 'areaDepartamento', 'areaResponsable.cargo',
        ])->findOrFail($this->asignacionId);
    }

    /**
     * Todos los items activos (principales Y periféricos), ordenados para
     * que cada periférico aparezca inmediatamente después de su padre.
     */
    #[Computed]
    public function todosLosItemsActivos()
    {
        return AsignacionItem::with([
            'equipo.categoria',
            'padre.equipo',
            'asignacion.empresa',
            'hijosActivos.equipo.categoria',
        ])
            ->where('asignacion_id', $this->asignacionId)
            ->where('devuelto', false)
            ->orderByRaw('COALESCE(equipo_padre_id, id)')
            ->orderBy('equipo_padre_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Principales seleccionados que tienen periféricos activos NO seleccionados.
     * Estos periféricos se promoverán a principales al confirmar (Opción A).
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
        $this->authorize('devolver', $this->asignacion);

        if (empty($this->seleccionados)) {
            $this->addError('seleccionados', 'Debes seleccionar al menos un equipo para devolver.');
            return;
        }

        $this->validate(
            collect($this->observaciones)->mapWithKeys(
                fn ($v, $k) => ["observaciones.{$k}" => 'nullable|string|max:1000']
            )->toArray(),
            collect($this->observaciones)->mapWithKeys(
                fn ($v, $k) => ["observaciones.{$k}.max" => 'La observación no puede superar 1000 caracteres.']
            )->toArray()
        );

        try {
            DB::transaction(function () {
                $items = AsignacionItem::whereIn('id', $this->seleccionados)
                    ->where('asignacion_id', $this->asignacionId)
                    ->where('devuelto', false)
                    ->get();

                foreach ($items as $item) {
                    $item->registrarDevolucion($this->observaciones[$item->id] ?: null);
                }
            });

            $cant = count($this->seleccionados);
            session()->flash('success', "Devolución registrada correctamente. {$cant} equipo(s) liberado(s).");
            $this->redirect(route('admin.asignaciones.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('DevolverAsignacion@confirmar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar la devolución. Por favor, inténtalo de nuevo.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.asignaciones.devolver-asignacion');
    }
}