<?php

namespace App\Livewire\MonitorRed\Concerns;

use App\Models\Empresa;
use App\Models\MonitorHost;
use App\Services\MonitorRed\MonitorRedService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * Filtro de empresa (solo Administrador) y botón "Revisar ahora",
 * compartidos por el dashboard y la tabla de hosts.
 */
trait RevisaHosts
{
    public string $empresa_id = '';

    protected function hostsBase(): Builder
    {
        return MonitorHost::visiblePara(Auth::user())
            ->when($this->empresa_id !== '', fn ($q) => $q->where('empresa_id', $this->empresa_id));
    }

    #[Computed]
    public function empresasOpciones()
    {
        return Auth::user()->hasRole('Administrador')
            ? Empresa::orderBy('nombre')->pluck('nombre', 'id')
            : collect();
    }

    /** Revisión inmediata de los hosts visibles (no espera al scheduler). */
    public function revisarAhora(MonitorRedService $servicio): void
    {
        $this->authorize('revisar', MonitorHost::class);

        $resumen = $servicio->revisar($this->hostsBase()->monitoreables()->get());

        if ($resumen === null) {
            $this->dispatch('toast', type: 'warning', message: 'Ya hay una revisión en curso, los datos se actualizarán en unos segundos.');
            return;
        }

        $this->dispatch('toast', type: 'success', message: "Revisión completa: {$resumen['revisados']} host(s).");
    }
}
