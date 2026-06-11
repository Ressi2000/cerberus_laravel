<?php

namespace App\Livewire\Traslados;

use App\Models\Equipo;
use App\Models\Traslado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class RevertirTrasladoModal extends Component
{
    public bool $open             = false;
    public ?int $trasladoId       = null;
    public ?Traslado $traslado    = null;
    public string $motivoReversion = '';

    #[On('revertirTraslado')]
    public function abrir(int $id): void
    {
        $traslado = Traslado::with(['ubicacionOrigen', 'ubicacionDestino'])->findOrFail($id);

        $this->authorize('revertir', $traslado);

        $this->traslado        = $traslado;
        $this->trasladoId      = $id;
        $this->motivoReversion = '';
        $this->open            = true;
    }

    public function confirmar(): void
    {
        if (! $this->traslado) return;

        $this->authorize('revertir', $this->traslado);

        $this->validate([
            'motivoReversion' => 'required|min:10',
        ], [
            'motivoReversion.required' => 'El motivo de la reversión es obligatorio.',
            'motivoReversion.min'      => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        try {
            DB::transaction(function () {
                $origenId = $this->traslado->ubicacion_origen_id;

                // Devolver cada equipo a la ubicación de origen
                foreach ($this->traslado->items as $item) {
                    Equipo::where('id', $item->equipo_id)
                        ->update(['ubicacion_id' => $origenId]);
                }

                $this->traslado->update([
                    'estado'           => 'revertido',
                    'motivo_reversion' => $this->motivoReversion,
                    'revertido_por_id' => auth()->id(),
                    'revertido_at'     => now(),
                ]);
            });

            $numero = $this->traslado->numero;
            $this->cerrar();
            $this->dispatch('toast', type: 'success', message: "Traslado {$numero} revertido. Equipos devueltos al origen.");
            $this->dispatch('trasladoRevertido');
        } catch (\Exception $e) {
            Log::error('RevertirTrasladoModal@confirmar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Ocurrió un error al revertir el traslado.');
        }
    }

    public function cerrar(): void
    {
        $this->reset(['open', 'traslado', 'trasladoId', 'motivoReversion']);
    }

    public function render()
    {
        return view('livewire.traslados.revertir-traslado-modal');
    }
}
