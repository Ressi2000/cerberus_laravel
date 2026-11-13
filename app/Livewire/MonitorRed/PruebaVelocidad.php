<?php

namespace App\Livewire\MonitorRed;

use App\Models\Empresa;
use App\Models\MonitorHost;
use App\Models\MonitorPruebaVelocidad;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Test de velocidad de internet.
 *
 * La medición la hace el NAVEGADOR (resources/js/modules/monitor-red.js)
 * contra speed.cloudflare.com, que también identifica al proveedor (ISP/ASN),
 * así que refleja la conexión de la sede desde donde se abre la página.
 * Aquí solo se valida y guarda el resultado, asociado a la empresa activa.
 */
class PruebaVelocidad extends Component
{
    use WithPagination;

    public string $empresa_id = '';

    public function mount(): void
    {
        $this->authorize('viewAny', MonitorHost::class);

        $actor = Auth::user();
        $this->empresa_id = (string) ($actor->empresa_activa_id
            ?? ($actor->hasRole('Administrador') ? Empresa::activas()->orderBy('nombre')->value('id') : ''));
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    #[Computed]
    public function empresasOpciones()
    {
        return Auth::user()->hasRole('Administrador')
            ? Empresa::activas()->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
    }

    private function base()
    {
        return MonitorPruebaVelocidad::visiblePara(Auth::user())
            ->when($this->empresa_id !== '', fn ($q) => $q->where('empresa_id', $this->empresa_id));
    }

    /** Recibe el resultado medido en el navegador. */
    public function guardar(array $r): array
    {
        $this->authorize('viewAny', MonitorHost::class);

        $actor     = Auth::user();
        $empresaId = $actor->hasRole('Administrador') ? (int) $this->empresa_id : (int) $actor->empresa_activa_id;

        if (! $empresaId) {
            $this->dispatch('toast', type: 'error', message: 'Selecciona una empresa para guardar el resultado.');
            return ['ok' => false];
        }

        $num = fn (string $k, float $max) => is_numeric($r[$k] ?? null) ? max(0, min($max, round((float) $r[$k], 2))) : null;
        $txt = fn (string $k, int $len) => mb_substr(trim(strip_tags((string) ($r[$k] ?? ''))), 0, $len) ?: null;

        $anterior = $this->base()->where('empresa_id', $empresaId)->latest()->first();

        $prueba = MonitorPruebaVelocidad::create([
            'empresa_id'  => $empresaId,
            'usuario_id'  => $actor->id,
            'bajada_mbps' => $num('bajada', 100000),
            'subida_mbps' => $num('subida', 100000),
            'ping_ms'     => $num('ping', 60000),
            'jitter_ms'   => $num('jitter', 60000),
            'isp'         => $txt('isp', 120),
            'asn'         => preg_replace('/\D/', '', (string) ($r['asn'] ?? '')) ?: null,
            'ip_publica'  => filter_var($r['ip'] ?? null, FILTER_VALIDATE_IP) ?: null,
            'ciudad'      => $txt('ciudad', 80),
            'pais'        => $txt('pais', 80),
        ]);

        $cambioProveedor = $anterior && $anterior->isp && $prueba->isp
            && ! $anterior->mismoProveedorQue($prueba->isp, $prueba->asn);

        $this->resetPage();
        $this->dispatch('toast', type: $cambioProveedor ? 'warning' : 'success', message: $cambioProveedor
            ? "Resultado guardado. Cambió el proveedor: {$anterior->isp} → {$prueba->isp}"
            : 'Resultado guardado.');

        return ['ok' => true, 'cambioProveedor' => $cambioProveedor, 'anterior' => $anterior?->isp];
    }

    public function render()
    {
        $ultima = $this->base()->latest()->first();

        return view('livewire.monitor-red.prueba-velocidad', [
            'pruebas' => $this->base()->with('usuario:id,name', 'empresa:id,nombre')->latest()->paginate(10),
            'ultima'  => $ultima,
            'mejor'   => [
                'bajada' => (float) $this->base()->max('bajada_mbps'),
                'subida' => (float) $this->base()->max('subida_mbps'),
            ],
        ]);
    }
}
