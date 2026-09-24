<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class MantenimientoEvidenciasPanel extends Component
{
    use WithFileUploads;

    public int $mantenimientoId;

    public $foto = null;
    public string $tipoFoto = 'Durante';

    public function mount(int $mantenimientoId): void
    {
        $this->mantenimientoId = $mantenimientoId;
    }

    #[Computed]
    public function mantenimiento(): Mantenimiento
    {
        return Mantenimiento::with(['evidencias.subidoPor'])->findOrFail($this->mantenimientoId);
    }

    public function subir(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        $this->validate([
            'foto'     => 'required|image|max:5120',
            'tipoFoto' => 'required|in:Antes,Durante,Después',
        ]);

        try {
            $path = $this->foto->store('mantenimientos', 'public');

            $m->evidencias()->create([
                'ruta_archivo'  => $path,
                'tipo'          => $this->tipoFoto,
                'subido_por_id' => Auth::id(),
            ]);

            $this->reset('foto');
            $this->dispatch('toast', type: 'success', message: 'Foto agregada.');
            $this->dispatch('evidenciaSubida');
        } catch (\Exception $e) {
            Log::error('MantenimientoEvidenciasPanel@subir: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al subir la foto.');
        }
    }

    public function render()
    {
        return view('livewire.mantenimientos.mantenimiento-evidencias-panel');
    }
}
