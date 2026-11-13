<?php

namespace App\Livewire\MonitorRed;

use App\Models\Empresa;
use App\Models\MonitorHost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Alta masiva: una línea por host → "ip, nombre, tipo, proveedor, puerto"
 * (solo la IP es obligatoria; separador coma, punto y coma o tabulador,
 * así se puede pegar directo desde Excel). Los hosts que ya existen en la
 * empresa se omiten en vez de duplicarse.
 */
class ImportarHostsModal extends Component
{
    public bool   $open       = false;
    public string $empresa_id = '';
    public string $texto      = '';

    /** @var list<string> */
    public array $fallos = [];

    public int $importados = 0;

    private const MAX_LINEAS = 500;

    #[On('openImportarHosts')]
    public function abrir(): void
    {
        $this->authorize('create', MonitorHost::class);
        $this->reset(['texto', 'fallos', 'importados']);
        $this->resetValidation();

        $actor = Auth::user();
        $this->empresa_id = $actor->hasRole('Administrador') ? '' : (string) ($actor->empresa_activa_id ?? '');

        if ($this->empresa_id === '' && $this->empresasOpciones->count() === 1) {
            $this->empresa_id = (string) $this->empresasOpciones->keys()->first();
        }

        $this->open = true;
    }

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        return $actor->hasRole('Administrador')
            ? Empresa::activas()->orderBy('nombre')->pluck('nombre', 'id')
            : Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    public function importar(): void
    {
        $this->authorize('create', MonitorHost::class);
        $this->validate(
            ['empresa_id' => 'required|exists:empresas,id', 'texto' => 'required|string'],
            ['empresa_id.required' => 'Debes seleccionar una empresa.', 'texto.required' => 'Pega al menos una línea.']
        );

        $actor = Auth::user();
        if (! $actor->hasRole('Administrador') && (int) $this->empresa_id !== (int) $actor->empresa_activa_id) {
            $this->addError('empresa_id', 'Solo puedes importar hosts en tu empresa activa.');
            return;
        }

        $lineas = collect(preg_split('/\R/', $this->texto))->map(fn ($l) => trim($l))->filter()->values();

        if ($lineas->count() > self::MAX_LINEAS) {
            $this->addError('texto', 'Máximo ' . self::MAX_LINEAS . ' líneas por importación.');
            return;
        }

        $existentes = MonitorHost::where('empresa_id', $this->empresa_id)->get(['host', 'puerto_tcp'])
            ->map(fn ($h) => mb_strtolower($h->host) . ':' . ($h->puerto_tcp ?? ''))->flip();

        $ok = 0;
        $fallos = [];

        DB::transaction(function () use ($lineas, $existentes, $actor, &$ok, &$fallos) {
            foreach ($lineas as $linea) {
                $c      = array_map('trim', preg_split('/[,;\t]/', $linea));
                $host   = $c[0] ?? '';
                $puerto = ($c[4] ?? '') === '' ? null : $c[4];

                if (! MonitorHost::hostValido($host)) {
                    $fallos[] = "{$linea} → IP o DNS no válido";
                    continue;
                }

                if ($puerto !== null && (! ctype_digit($puerto) || (int) $puerto < 1 || (int) $puerto > 65535)) {
                    $fallos[] = "{$linea} → puerto no válido";
                    continue;
                }

                $clave = mb_strtolower($host) . ':' . ($puerto ?? '');
                if ($existentes->has($clave)) {
                    $fallos[] = "{$linea} → ya existe, omitido";
                    continue;
                }

                MonitorHost::create([
                    'empresa_id' => (int) $this->empresa_id,
                    'host'       => $host,
                    'nombre'     => mb_substr(($c[1] ?? '') ?: $host, 0, 100),
                    'tipo'       => mb_substr(($c[2] ?? '') ?: 'Servicio', 0, 40),
                    'proveedor'  => mb_substr($c[3] ?? '', 0, 60) ?: null,
                    'puerto_tcp' => $puerto !== null ? (int) $puerto : null,
                    'creado_por' => $actor->id,
                ]);

                $existentes->put($clave, true);
                $ok++;
            }
        });

        $this->importados = $ok;
        $this->fallos     = $fallos;

        if ($ok > 0) {
            $this->dispatch('monitorHostsImportados');
            $this->dispatch('toast', type: 'success', message: "{$ok} host(s) importados.");
        }

        if (! $fallos) {
            $this->close();
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['texto', 'fallos', 'importados', 'empresa_id']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.monitor-red.importar-hosts-modal');
    }
}
