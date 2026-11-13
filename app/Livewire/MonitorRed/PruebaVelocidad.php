<?php

namespace App\Livewire\MonitorRed;

use App\Models\Empresa;
use App\Models\MonitorEnlace;
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
 * contra speed.cloudflare.com, que también devuelve la IP pública y el ASN
 * por los que salió. Con eso el resultado se atribuye al enlace de la
 * localidad (por IP pública, o por ASN si es único), o al enlace que eligió
 * a mano quien midió — p. ej. estando en sitio y cambiando la puerta de
 * enlace del equipo para probar cada proveedor.
 */
class PruebaVelocidad extends Component
{
    use WithPagination;

    public string $empresa_id = '';
    public string $enlace_filtro = '';

    public function mount(): void
    {
        $this->authorize('viewAny', MonitorHost::class);

        $actor = Auth::user();
        $this->empresa_id = (string) ($actor->empresa_activa_id
            ?? ($actor->hasRole('Administrador') ? Empresa::activas()->orderBy('nombre')->value('id') : ''));
    }

    public function updated(string $property): void
    {
        if ($property === 'empresa_id') {
            $this->enlace_filtro = '';
            unset($this->enlaces);
        }

        if ($property !== 'page') $this->resetPage();
    }

    #[Computed]
    public function empresasOpciones()
    {
        return Auth::user()->hasRole('Administrador')
            ? Empresa::activas()->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
    }

    /** Empresa en la que se guardan los tests: la activa del analista, o la elegida por el admin. */
    private function empresaDestino(): int
    {
        $actor = Auth::user();

        return $actor->hasRole('Administrador') ? (int) $this->empresa_id : (int) $actor->empresa_activa_id;
    }

    #[Computed]
    public function enlaces()
    {
        return MonitorEnlace::visiblePara(Auth::user())
            ->where('empresa_id', $this->empresaDestino())
            ->activos()
            ->with('ultimaPrueba')
            ->orderByDesc('principal')->orderBy('nombre')
            ->get();
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
        $empresaId = $this->empresaDestino();

        if (! $empresaId) {
            $this->dispatch('toast', type: 'error', message: 'Selecciona una empresa para guardar el resultado.');
            return ['ok' => false];
        }

        $num = fn (string $k, float $max) => is_numeric($r[$k] ?? null) ? max(0, min($max, round((float) $r[$k], 2))) : null;
        $txt = fn (string $k, int $len) => mb_substr(trim(strip_tags((string) ($r[$k] ?? ''))), 0, $len) ?: null;

        $ip  = filter_var($r['ip'] ?? null, FILTER_VALIDATE_IP) ?: null;
        $asn = MonitorEnlace::normalizarAsn($r['asn'] ?? null);

        // Enlace: el elegido a mano (si es de esta empresa) o el detectado por IP/ASN.
        $enlace = null;
        $por    = null;
        if (! empty($r['enlace_id'])) {
            $enlace = MonitorEnlace::where('empresa_id', $empresaId)->find((int) $r['enlace_id']);
            $por    = $enlace ? 'manual' : null;
        }
        if (! $enlace) {
            [$enlace, $por] = MonitorEnlace::detectar($empresaId, $ip, $asn);
        }

        $anterior = MonitorPruebaVelocidad::where('empresa_id', $empresaId)->latest()->first();

        $prueba = MonitorPruebaVelocidad::create([
            'empresa_id'          => $empresaId,
            'usuario_id'          => $actor->id,
            'enlace_id'           => $enlace?->id,
            'enlace_asignado_por' => $por,
            'bajada_mbps'         => $num('bajada', 100000),
            'subida_mbps'         => $num('subida', 100000),
            'ping_ms'             => $num('ping', 60000),
            'jitter_ms'           => $num('jitter', 60000),
            'isp'                 => $txt('isp', 120),
            'asn'                 => $asn,
            'ip_publica'          => $ip,
            'ciudad'              => $txt('ciudad', 80),
            'pais'                => $txt('pais', 80),
        ]);

        $cambioProveedor = $anterior && $anterior->isp && $prueba->isp
            && ! $anterior->mismoProveedorQue($prueba->isp, $prueba->asn);

        // Elegido a mano pero la conexión salió por otro ISP: probablemente no se cambió la puerta de enlace.
        $noCoincide = $por === 'manual' && $enlace->asn && $asn && MonitorEnlace::normalizarAsn($enlace->asn) !== $asn;

        $this->resetPage();
        unset($this->enlaces);

        $msg = 'Resultado guardado' . ($enlace ? " en «{$enlace->nombre}»" : ' (sin enlace asociado)') . '.';
        if ($noCoincide) {
            $msg .= " Ojo: la conexión salió por AS{$asn} ({$prueba->isp}), no por el ASN del enlace elegido. ¿Cambiaste la puerta de enlace?";
        } elseif ($cambioProveedor) {
            $msg .= " Cambió el proveedor respecto al test anterior: {$anterior->isp} → {$prueba->isp}.";
        }

        $this->dispatch('toast', type: $noCoincide || $cambioProveedor ? 'warning' : 'success', message: $msg);

        return ['ok' => true, 'enlace' => $enlace?->nombre, 'cambioProveedor' => $cambioProveedor, 'noCoincide' => $noCoincide];
    }

    public function render()
    {
        $enlacesJs = $this->enlaces->map(fn (MonitorEnlace $e) => [
            'id'        => $e->id,
            'nombre'    => $e->nombre,
            'proveedor' => $e->proveedor,
            'gateway'   => $e->gateway,
            'ip'        => $e->ip_publica,
            'asn'       => $e->asn,
            'bajada'    => $e->bajada_contratada_mbps,
            'subida'    => $e->subida_contratada_mbps,
            'cuota'     => $e->con_cuota,
        ])->values();

        return view('livewire.monitor-red.prueba-velocidad', [
            'pruebas'   => $this->base()
                ->with('usuario:id,name', 'empresa:id,nombre', 'enlace:id,nombre')
                ->when($this->enlace_filtro === 'sin', fn ($q) => $q->whereNull('enlace_id'))
                ->when(ctype_digit($this->enlace_filtro), fn ($q) => $q->where('enlace_id', (int) $this->enlace_filtro))
                ->latest()->paginate(10),
            'ultima'    => $this->base()->latest()->first(),
            'mejor'     => [
                'bajada' => (float) $this->base()->max('bajada_mbps'),
                'subida' => (float) $this->base()->max('subida_mbps'),
            ],
            'enlacesJs' => $enlacesJs,
        ]);
    }
}
