<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\PiezaExtraida;
use App\Services\TrasladoAlmacenService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Trasladar stock de una variante al almacén de otra empresa (ver
 * TrasladoAlmacenService). Solo el Administrador: el Analista no ve ni
 * maneja los almacenes de otras empresas.
 */
class TrasladoAlmacenModal extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $componenteId = null;

    public string $empresaDestinoId = '';
    /** @var array<int|string> piezas (con serial) que se trasladan */
    public array  $piezaIds         = [];
    /** Unidades sin serial, o la cantidad si es a granel. */
    public ?int   $sinSerial        = 0;
    public string $observaciones    = '';

    #[On('openTrasladoAlmacen')]
    public function abrir(int $componenteId): void
    {
        $this->autorizar();
        $componente = ComponenteAlmacen::findOrFail($componenteId);

        $this->componenteId = $componente->id;
        $this->reset(['empresaDestinoId', 'piezaIds', 'observaciones']);
        $this->sinSerial = 0;
        unset($this->componente, $this->piezas);
        $this->resetValidation();
        $this->open = true;
    }

    #[Computed]
    public function componente(): ?ComponenteAlmacen
    {
        return $this->componenteId ? ComponenteAlmacen::with(['empresa', 'familia'])->find($this->componenteId) : null;
    }

    /** Piezas con serial que hay en el almacén de esta variante. */
    #[Computed]
    public function piezas()
    {
        return $this->componente && ! $this->componente->esAGranel()
            ? $this->componente->piezasEnAlmacen()->orderBy('identificador')->get()
            : collect();
    }

    #[Computed]
    public function empresas()
    {
        return Empresa::activas()
            ->whereKeyNot($this->componente?->empresa_id)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    public function guardar(): void
    {
        $this->autorizar();
        $componente = $this->componente;
        if (! $componente) {
            $this->close();
            return;
        }

        $this->validate([
            'empresaDestinoId' => 'required|exists:empresas,id',
            'sinSerial'        => 'nullable|integer|min:0|max:100000',
            'observaciones'    => 'nullable|string|max:500',
        ], [
            'empresaDestinoId.required' => 'Elige a qué empresa va.',
        ]);

        // Solo piezas de ESTA variante (los ids llegan del navegador).
        $ids    = collect($this->piezaIds)->map(fn ($id) => (int) $id)->unique();
        $piezas = PiezaExtraida::where('componente_almacen_id', $componente->id)->whereIn('id', $ids)->get();
        if ($piezas->count() !== $ids->count()) {
            $this->addError('piezaIds', 'Alguna de las piezas elegidas ya no es de esta variante.');
            return;
        }

        try {
            $destino = app(TrasladoAlmacenService::class)->trasladar(
                $componente,
                Empresa::findOrFail($this->empresaDestinoId),
                Auth::user(),
                $piezas,
                (int) $this->sinSerial,
                trim($this->observaciones) ?: null,
            );

            $n = $piezas->count() + (int) $this->sinSerial;
            $this->close();
            $this->dispatch('stockActualizado');
            $this->dispatch('toast', type: 'success', message: "Se trasladaron {$n} a «{$destino->nombre}» de {$destino->empresa->nombre}.");
        } catch (\InvalidArgumentException $e) {
            $this->addError('piezaIds', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('TrasladoAlmacenModal@guardar: ' . $e->getMessage());
            $this->addError('piezaIds', 'Ocurrió un error y no se trasladó nada. Inténtalo de nuevo.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetValidation();
    }

    private function autorizar(): void
    {
        abort_unless(Auth::user()?->hasRole('Administrador'), 403);
    }

    public function render()
    {
        return view('livewire.almacen.traslado-almacen-modal');
    }
}
