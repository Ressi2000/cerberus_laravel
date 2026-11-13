<?php

namespace App\Livewire\MonitorRed;

use App\Models\Empresa;
use App\Models\MonitorEnlace;
use App\Models\MonitorHost;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Enlaces de internet por localidad. Se lista agrupado por empresa: el
 * Analista solo ve la suya; el Administrador ve todas (o filtra una).
 */
class EnlacesTable extends Component
{
    #[Url(as: 'empresa')]
    public string $empresa_id = '';

    public bool $mostrar_inactivos = false;

    public function mount(): void
    {
        $this->authorize('viewAny', MonitorEnlace::class);
    }

    #[On('enlaceGuardado')]
    public function refrescar(): void {}

    #[Computed]
    public function empresasOpciones()
    {
        return Auth::user()->hasRole('Administrador')
            ? Empresa::orderBy('nombre')->pluck('nombre', 'id')
            : collect();
    }

    /** Monitorea la IP pública del enlace como un host más (disponibilidad por proveedor). */
    public function monitorearWan(int $id): void
    {
        $enlace = MonitorEnlace::findOrFail($id);
        $this->authorize('update', $enlace);
        $this->authorize('create', MonitorHost::class);

        if (! $enlace->ip_publica) {
            $this->dispatch('toast', type: 'error', message: 'Registra primero la IP pública del enlace.');
            return;
        }

        $host = $enlace->asegurarHostWan(Auth::user());
        $this->dispatch('toast', type: 'success', message: "IP pública {$host->host} en monitoreo. Se revisará en el próximo minuto.");
    }

    #[On('enlaceEliminarConfirmado')]
    public function eliminar(int $id): void
    {
        $enlace = MonitorEnlace::findOrFail($id);
        $this->authorize('delete', $enlace);

        $enlace->delete(); // soft delete: los tests y hosts vinculados conservan la referencia histórica
        $this->dispatch('toast', type: 'success', message: "Enlace «{$enlace->nombre}» eliminado.");
    }

    public function render()
    {
        $enlaces = MonitorEnlace::visiblePara(Auth::user())
            ->with(['empresa:id,nombre', 'hosts', 'ultimaPrueba'])
            ->when($this->empresa_id !== '', fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when(! $this->mostrar_inactivos, fn ($q) => $q->where('activo', true))
            ->orderByDesc('principal')
            ->orderBy('nombre')
            ->get();

        return view('livewire.monitor-red.enlaces-table', [
            'grupos' => $enlaces->groupBy(fn ($e) => $e->empresa?->nombre ?? '—')->sortKeys(),
            'total'  => $enlaces->count(),
            'conWan' => $enlaces->filter(fn ($e) => $e->hostWan())->count(),
            'caidos' => $enlaces->filter(fn ($e) => $e->hostWan()?->estaCaido())->count(),
        ]);
    }
}
