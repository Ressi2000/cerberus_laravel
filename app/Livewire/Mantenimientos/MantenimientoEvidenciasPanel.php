<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Evidencia fotográfica del caso. La foto "Antes" se captura obligatoria al
 * crear el caso (CrearMantenimiento) y no se sube desde aquí. Este panel
 * solo permite "Durante" (mientras el caso sigue abierto) y "Después"
 * (una vez que ya está técnicamente resuelto: Reparado para correctivo,
 * En proceso para preventivo — el último estado activo antes del cierre).
 */
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

    /** El caso pudo cerrarse/avanzar desde el panel de Acciones (otro componente). */
    #[On('mantenimientoActualizado')]
    public function refrescar(): void
    {
        unset($this->mantenimiento);
    }

    #[Computed]
    public function mantenimiento(): Mantenimiento
    {
        return Mantenimiento::with(['evidencias.subidoPor'])->findOrFail($this->mantenimientoId);
    }

    /** Momentos que corresponde poder subir según el estado actual del caso. */
    #[Computed]
    public function momentosDisponibles(): array
    {
        $m = $this->mantenimiento;

        if (! $m->estaAbierto()) {
            return [];
        }

        $ultimoEstadoActivo = $m->esCorrectivo() ? 'Reparado' : 'En proceso';

        if ($m->estado === $ultimoEstadoActivo) {
            return ['Durante' => 'Durante', 'Después' => 'Después'];
        }

        return ['Durante' => 'Durante'];
    }

    public function subir(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        $opciones = array_keys($this->momentosDisponibles);

        if (! in_array($this->tipoFoto, $opciones, true)) {
            $this->tipoFoto = $opciones[0] ?? 'Durante';
        }

        $this->validate([
            'foto'     => 'required|image|max:5120',
            'tipoFoto' => 'required|in:' . implode(',', $opciones ?: ['Durante']),
        ]);

        try {
            $path = $this->foto->store('mantenimientos', 'public');

            $m->evidencias()->create([
                'ruta_archivo'  => $path,
                'tipo'          => $this->tipoFoto,
                'subido_por_id' => Auth::id(),
            ]);

            $this->reset('foto');
            unset($this->mantenimiento);
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
