<?php

namespace App\Livewire\Almacen;

use App\Models\PiezaExtraida;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Ficha de una pieza (solo lectura): quién es (serial o código), qué es
 * (variante y ficha técnica), dónde está hoy y todo su recorrido — de qué
 * equipo salió, cuándo entró al almacén, en qué caso y en qué equipo se
 * instaló, si volvió a salir…
 *
 * Se abre con el evento openPiezaFicha desde Almacén, el caso y el
 * historial del equipo. Solo muestra piezas de las empresas que el usuario
 * puede ver (PiezaExtraida::visiblePara).
 */
class PiezaFichaModal extends Component
{
    public bool $open = false;

    // Locked: solo se fija en abrir(), después de comprobar que es visible.
    #[Locked]
    public ?int $piezaId = null;

    #[On('openPiezaFicha')]
    public function abrir(int $piezaId): void
    {
        // Pieza de otra empresa → 404, no se distingue de una que no existe.
        PiezaExtraida::visiblePara(Auth::user())->findOrFail($piezaId);

        $this->piezaId = $piezaId;
        unset($this->pieza, $this->recorrido);
        $this->open = true;
    }

    #[Computed]
    public function pieza(): ?PiezaExtraida
    {
        if (! $this->piezaId) {
            return null;
        }

        return PiezaExtraida::visiblePara(Auth::user())
            ->with(['componenteAlmacen.familia', 'atributo.familia', 'equipoOrigen', 'equipoDestino', 'deposito', 'extraidoPor', 'empresa'])
            ->find($this->piezaId);
    }

    /** Movimientos del más antiguo al más reciente: se lee como una historia. */
    #[Computed]
    public function recorrido()
    {
        if (! $this->pieza) {
            return collect();
        }

        return $this->pieza->movimientos()
            ->reorder()
            ->orderBy('created_at')
            ->orderBy('id')
            ->with(['equipoRelacionado', 'depositoRelacionado', 'mantenimiento', 'registradoPor'])
            ->get();
    }

    /**
     * Ficha técnica de la pieza como pares campo → valor (ej. Capacidad (GB)
     * → 8). Los nombres salen de la característica (o de su familia), así
     * que si se renombra un campo la ficha muestra el nombre nuevo.
     */
    #[Computed]
    public function fichaTecnica(): array
    {
        $pieza = $this->pieza;
        if (! $pieza || empty($pieza->valor_extraido) || ! $pieza->atributo) {
            return [];
        }

        if (! $pieza->atributo->esGrupo()) {
            $valor = $pieza->valor_extraido['valor'] ?? null;

            return $valor === null || $valor === '' ? [] : [$pieza->atributo->nombre => (string) $valor];
        }

        $campos = collect($pieza->atributo->sub_campos ?? [])->keyBy('id');

        return collect($pieza->valor_extraido)
            ->reject(fn ($v) => $v === null || $v === '')
            ->mapWithKeys(fn ($v, $id) => [($campos[$id]['nombre'] ?? 'Campo') => (string) $v])
            ->all();
    }

    /** Desde la ficha se puede saltar al historial de su variante. */
    public function verVariante(): void
    {
        $componenteId = $this->pieza?->componente_almacen_id;
        $this->close();

        if ($componenteId) {
            $this->dispatch('openComponentePiezas', componenteId: $componenteId);
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['piezaId']);
    }

    public function render()
    {
        return view('livewire.almacen.pieza-ficha-modal');
    }
}
