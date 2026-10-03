<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\PiezaExtraida;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * ComponentePiezasModal — solo lectura.
 *
 * ComponenteAlmacen es un bucket agregado ("RAM (8GB)", stock 3) sin
 * identidad propia; la identidad de cada unidad vive en PiezaExtraida (ver
 * su docblock). Este modal responde "de estas 3 unidades en stock, ¿cuál es
 * cuál?" — de qué equipo salió cada una y cuándo, para poder identificarlas
 * antes de instalar una en otro equipo (ver MantenimientoPiezasPanel, que ya
 * muestra este mismo dato en el selector de instalación).
 */
class ComponentePiezasModal extends Component
{
    public bool $open = false;
    public ?int $componenteId = null;

    #[On('openComponentePiezas')]
    public function abrir(int $componenteId): void
    {
        $this->componenteId = $componenteId;
        $this->open = true;
    }

    #[Computed]
    public function componente(): ?ComponenteAlmacen
    {
        return $this->componenteId ? ComponenteAlmacen::find($this->componenteId) : null;
    }

    /** Unidades físicas que suman el stock de este bucket, con su origen. */
    #[Computed]
    public function piezas()
    {
        if (! $this->componenteId) {
            return collect();
        }

        return PiezaExtraida::where('componente_almacen_id', $this->componenteId)
            ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
            ->with(['equipoOrigen', 'extraidoPor'])
            ->latest()
            ->get();
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['componenteId']);
    }

    public function render()
    {
        return view('livewire.almacen.componente-piezas-modal');
    }
}
