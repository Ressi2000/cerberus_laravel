<?php

namespace App\Livewire\Asignaciones;

use App\Models\AsignacionItem;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * VincularPerifericoModal
 *
 * Modal que permite asociar un periférico (item huérfano o ya vinculado)
 * a un equipo principal activo del mismo receptor, incluso si ese principal
 * pertenece a una asignación diferente.
 *
 * ── Flujo ────────────────────────────────────────────────────────────────────
 *  1. La tabla/vista de asignaciones dispara el evento 'openVincularPeriferico'
 *     con el ID del AsignacionItem que se quiere vincular.
 *  2. El modal carga el item y busca todos los principales activos del mismo
 *     receptor (en cualquier asignación).
 *  3. El analista selecciona el padre y confirma.
 *  4. Se llama a $item->vincularAPadre($padre) que contiene todas las
 *     validaciones de negocio.
 *  5. Se emite 'perifericoVinculado' para que la tabla se refresque.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class VincularPerifericoModal extends Component
{
    public bool $open = false;

    /** ID del AsignacionItem periférico que se va a vincular */
    public ?int $itemId = null;

    /** ID del AsignacionItem principal seleccionado como padre */
    public ?int $padreId = null;

    // ─────────────────────────────────────────────────────────────────────────
    // Apertura del modal
    // ─────────────────────────────────────────────────────────────────────────

    #[On('openVincularPeriferico')]
    public function abrir(int $id): void
    {
        $this->reset(['itemId', 'padreId']);
        $this->resetValidation();

        $item = AsignacionItem::with('asignacion')->find($id);

        // Guardia: item inexistente o ya devuelto
        if (! $item || $item->devuelto) {
            session()->flash('error', 'El item no existe o ya fue devuelto.');
            return;
        }

        $this->itemId = $item->id;
        $this->open   = true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed: item periférico actual
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * El item que se está vinculando, con sus relaciones cargadas.
     * Se recarga en cada request para reflejar el estado actual de BD.
     */
    public function getItemProperty(): ?AsignacionItem
    {
        if (! $this->itemId) return null;

        return AsignacionItem::with([
            'equipo.categoria',
            'asignacion.usuario',
            'asignacion.areaDepartamento',
            'asignacion.areaEmpresa',
            'padre.equipo.categoria',
        ])->find($this->itemId);
    }

    /**
     * Lista de items principales activos disponibles para ser el padre.
     *
     * Criterios:
     *   - esPrincipal()      → equipo_padre_id IS NULL
     *   - activo             → devuelto = false
     *   - mismo receptor     → mismo usuario_id o misma área
     *   - distinto de sí mismo → excluye el propio item
     */
    public function getPrincipalesDisponiblesProperty(): \Illuminate\Support\Collection
    {
        if (! $this->item) return collect();

        $asignacionPropia = $this->item->asignacion;

        // Base: principales activos, excluyendo este mismo item
        $query = AsignacionItem::with(['equipo.categoria', 'asignacion'])
            ->whereNull('equipo_padre_id')
            ->where('devuelto', false)
            ->where('id', '!=', $this->itemId);

        // Filtrar por mismo receptor
        if ($asignacionPropia->tipoReceptor() === 'personal') {
            // Asignaciones personales del mismo usuario
            $query->whereHas('asignacion', function ($q) use ($asignacionPropia) {
                $q->where('usuario_id', $asignacionPropia->usuario_id)
                  ->whereNull('deleted_at');
            });
        } else {
            // Asignaciones de área con mismo departamento y empresa
            $query->whereHas('asignacion', function ($q) use ($asignacionPropia) {
                $q->where('area_empresa_id',    $asignacionPropia->area_empresa_id)
                  ->where('area_departamento_id', $asignacionPropia->area_departamento_id)
                  ->whereNull('deleted_at');
            });
        }

        return $query->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acción principal: confirmar vinculación
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->validate([
            'padreId' => 'required|integer|exists:asignacion_items,id',
        ], [
            'padreId.required' => 'Debes seleccionar un equipo principal.',
            'padreId.exists'   => 'El equipo seleccionado no es válido.',
        ]);

        try {
            $item  = AsignacionItem::with('asignacion')->findOrFail($this->itemId);
            $padre = AsignacionItem::with('asignacion')->findOrFail($this->padreId);

            // El modelo hace todas las validaciones de negocio
            $item->vincularAPadre($padre);

            $this->cerrar();
            $this->dispatch('perifericoVinculado');
            session()->flash('success', 'Periférico vinculado correctamente.');

        } catch (\InvalidArgumentException $e) {
            // Error de negocio esperado → mostrar al usuario
            $this->addError('padreId', $e->getMessage());

        } catch (\Throwable $e) {
            Log::error('VincularPerifericoModal::confirmar — error inesperado', [
                'item_id'  => $this->itemId,
                'padre_id' => $this->padreId,
                'error'    => $e->getMessage(),
            ]);
            session()->flash('error', 'Ocurrió un error inesperado. Intenta de nuevo.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acción secundaria: quitar vínculo (desvincular sin devolver)
    // ─────────────────────────────────────────────────────────────────────────

    public function desvincular(): void
    {
        try {
            $item = AsignacionItem::with('asignacion')->findOrFail($this->itemId);
            $item->desvincularDePadre();

            $this->cerrar();
            $this->dispatch('perifericoVinculado');
            session()->flash('success', 'Periférico desvinculado. Queda como equipo independiente.');

        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

        } catch (\Throwable $e) {
            Log::error('VincularPerifericoModal::desvincular — error inesperado', [
                'item_id' => $this->itemId,
                'error'   => $e->getMessage(),
            ]);
            session()->flash('error', 'Ocurrió un error inesperado. Intenta de nuevo.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cierre
    // ─────────────────────────────────────────────────────────────────────────

    public function cerrar(): void
    {
        $this->reset(['open', 'itemId', 'padreId']);
        $this->resetValidation();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.asignaciones.vincular-periferico-modal', [
            'item'                  => $this->item,
            'principalesDisponibles' => $this->principalesDisponibles,
        ]);
    }
}