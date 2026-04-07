<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * DevolverAsignacion
 *
 * Gestiona la devolución total o parcial de una asignación.
 *
 * Flujo:
 *   1. Recibe el ID de la asignación vía mount() (route model binding).
 *   2. Muestra todos los items activos (no devueltos) con checkbox individual.
 *   3. El analista selecciona cuáles devuelve y puede agregar observaciones por item.
 *   4. Al confirmar, se ejecuta una transacción que:
 *        · Llama a AsignacionItem::registrarDevolucion() por cada item seleccionado
 *        · registrarDevolucion() actualiza el equipo a "Disponible" y recalcula estado
 *        · La auditoría se registra automáticamente via trait Auditable
 *
 * Decisión de diseño: página dedicada en lugar de modal
 *   Los items con observaciones individuales y checkboxes necesitan espacio.
 *   Un modal sería demasiado estrecho y frágil para esta interacción.
 *
 * La lógica de negocio real (devolver equipo, recalcular estado)
 * vive en AsignacionItem::registrarDevolucion() — no aquí.
 * Este componente solo orquesta la UI y valida la selección.
 */
class DevolverAsignacion extends Component
{
    // ── Asignación en curso ───────────────────────────────────────────────────
    public int $asignacionId;

    // ── Estado del formulario ─────────────────────────────────────────────────
    // IDs de items que el analista marcó para devolver
    public array $seleccionados = [];

    // Observaciones individuales por item_id
    // Estructura: [ item_id => 'texto observación' ]
    public array $observaciones = [];

    // Marcar/desmarcar todos
    public bool $seleccionarTodos = false;

    // ─────────────────────────────────────────────────────────────────────────
    // Mount
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

        // Inicializar observaciones vacías para cada item activo
        foreach ($asignacion->itemsActivos as $item) {
            $this->observaciones[$item->id] = '';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed Properties
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function asignacion(): Asignacion
    {
        return Asignacion::with([
            'usuario.cargo',
            'ubicacionDestino',
            'analista',
            'empresa',
            'itemsActivos.equipo.categoria',
            'itemsActivos.equipo.estado',
        ])->findOrFail($this->asignacionId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Selección de items
    // ─────────────────────────────────────────────────────────────────────────

    public function updatedSeleccionarTodos(bool $value): void
    {
        if ($value) {
            $this->seleccionados = $this->asignacion
                ->itemsActivos
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->seleccionados = [];
        }
    }

    /**
     * Cuando cambia la selección individual, sincroniza el estado del "todos".
     */
    public function updatedSeleccionados(): void
    {
        $totalActivos = $this->asignacion->itemsActivos->count();
        $this->seleccionarTodos = count($this->seleccionados) === $totalActivos;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Confirmar devolución
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->authorize('devolver', $this->asignacion);

        // Validar que al menos un item esté seleccionado
        if (empty($this->seleccionados)) {
            $this->addError('seleccionados', 'Debes seleccionar al menos un equipo para devolver.');
            return;
        }

        // Validar observaciones (opcionales, pero si se escriben no deben superar 1000 chars)
        $this->validate(
            collect($this->observaciones)->mapWithKeys(
                fn($v, $k) => ["observaciones.{$k}" => 'nullable|string|max:1000']
            )->toArray(),
            collect($this->observaciones)->mapWithKeys(
                fn($v, $k) => ["observaciones.{$k}.max" => 'La observación no puede superar 1000 caracteres.']
            )->toArray()
        );

        try {
            DB::transaction(function () {
                $itemsSeleccionados = AsignacionItem::whereIn('id', $this->seleccionados)
                    ->where('asignacion_id', $this->asignacionId)
                    ->where('devuelto', false)
                    ->get();

                foreach ($itemsSeleccionados as $item) {
                    $obs = $this->observaciones[$item->id] ?? null;
                    // Toda la lógica vive en el modelo: libera equipo + recalcula estado asignación
                    $item->registrarDevolucion($obs ?: null);
                }
            });

            $cantDevueltos = count($this->seleccionados);
            session()->flash('success', "Devolución registrada correctamente. {$cantDevueltos} equipo(s) liberado(s).");

            $this->redirect(route('admin.asignaciones.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('DevolverAsignacion@confirmar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar la devolución. Por favor, inténtalo de nuevo.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.asignaciones.devolver-asignacion');
    }
}