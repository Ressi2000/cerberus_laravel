<?php

namespace App\Livewire\Admin;

use App\Models\Auditoria;
use App\Services\AuditoriaResolverService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * AuditoriaModal
 *
 * Modal de detalle de auditoría.
 * - NO guarda el modelo Auditoria como propiedad (evita problemas de serialización
 *   y el error "Attempt to read property on null" en el render inicial).
 * - Compatible con AuditDiff::diff() que devuelve ['campo' => ['before' => x, 'after' => y]].
 * - Usa AuditoriaResolverService para traducir IDs a nombres legibles.
 */
class AuditoriaModal extends Component
{
    // ── Estado del modal ──────────────────────────────────────────────────────
    public bool $open = false;

    // ── Meta del log (propiedades simples, no el modelo) ─────────────────────
    public ?string $logTabla      = null;
    public ?string $logAccion     = null;
    public ?int    $logRegistroId = null;
    public ?string $logFecha      = null;
    public ?string $logUsuario    = null;

    // ── Datos resueltos para la vista ─────────────────────────────────────────
    /** [['campo', 'etiqueta', 'antes', 'despues'], ...] */
    public array $cambiosResueltos = [];

    /** [['campo', 'etiqueta', 'valor'], ...] — para acción CREAR */
    public array $valoresCreacion = [];

    /** [['campo', 'etiqueta', 'valor'], ...] — para acción ELIMINAR */
    public array $valoresEliminacion = [];

    // ─────────────────────────────────────────────────────────────────────────

    #[On('openAuditoriaModal')]
    public function abrir(int $logId): void
    {
        $log = Auditoria::with('usuario')->findOrFail($logId);

        // Guardar solo datos primitivos (Livewire los serializa sin problemas)
        $this->logTabla      = $log->tabla;
        $this->logAccion     = $log->accion;
        $this->logRegistroId = (int) $log->registro_id;
        $this->logFecha      = $log->created_at
            ? Carbon::parse($log->created_at)->format('d/m/Y H:i')
            : '—';
        $this->logUsuario    = $log->usuario?->name ?? 'Sistema';

        $resolver = app(AuditoriaResolverService::class);
        $tabla    = $log->tabla;
        $accion   = strtoupper($log->accion);

        // Decodificar JSON de previos/nuevos
        $previos = $this->decodificarJson($log->valores_previos);
        $nuevos  = $this->decodificarJson($log->valores_nuevos);

        // ── Limpiar resultados anteriores ─────────────────────────────────────
        $this->cambiosResueltos   = [];
        $this->valoresCreacion    = [];
        $this->valoresEliminacion = [];

        // ── CREAR: no hay previos, solo nuevos ────────────────────────────────
        if (in_array($accion, ['CREAR', 'CREATED'])) {
            $this->valoresCreacion = $nuevos
                ? $this->resolverListaValores($resolver, $tabla, $nuevos)
                : [];
            $this->open = true;
            return;
        }

        // ── ELIMINAR: no hay nuevos, solo previos ─────────────────────────────
        if (in_array($accion, ['ELIMINAR', 'DELETED'])) {
            $this->valoresEliminacion = $previos
                ? $this->resolverListaValores($resolver, $tabla, $previos)
                : [];
            $this->open = true;
            return;
        }

        // ── ACTUALIZAR / LOGIN / LOGOUT / otros ───────────────────────────────
        // Reutiliza el accessor getCambiosAttribute() que ya existe en el modelo
        // que devuelve: ['campo' => ['before' => x, 'after' => y]]
        $cambiosCrudos = $log->cambios;

        $this->cambiosResueltos = $this->resolverCambios($resolver, $tabla, $cambiosCrudos);

        $this->open = true;
    }

    public function cerrar(): void
    {
        $this->reset([
            'open',
            'logTabla',
            'logAccion',
            'logRegistroId',
            'logFecha',
            'logUsuario',
            'cambiosResueltos',
            'valoresCreacion',
            'valoresEliminacion',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.auditoria-modal');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convierte el resultado de AuditDiff::diff() en cambios con labels legibles.
     *
     * Entrada:  ['campo' => ['before' => x, 'after' => y]]
     * Salida:   [['campo', 'etiqueta', 'antes', 'despues'], ...]
     */
    private function resolverCambios(AuditoriaResolverService $resolver, string $tabla, array $cambios): array
    {
        if (empty($cambios)) {
            return [];
        }

        // Extraer arrays planos para pasar al resolver
        $previosPlano = array_map(fn($v) => $v['before'] ?? null, $cambios);
        $nuevosPlano  = array_map(fn($v) => $v['after']  ?? null, $cambios);

        $previosResueltos = $resolver->resolver($tabla, $previosPlano);
        $nuevosResueltos  = $resolver->resolver($tabla, $nuevosPlano);

        $resultado = [];

        foreach ($cambios as $campo => $vals) {
            $labelAntes   = $previosResueltos[$campo . '__label']
                ?? $this->formatearValor($vals['before'] ?? null);
            $labelDespues = $nuevosResueltos[$campo . '__label']
                ?? $this->formatearValor($vals['after'] ?? null);

            $resultado[] = [
                'campo'   => $campo,
                'etiqueta' => $resolver->etiquetaCampo($campo),
                'antes'   => $labelAntes,
                'despues' => $labelDespues,
            ];
        }

        return $resultado;
    }

    /**
     * Resuelve un array plano de valores para mostrar en lista (crear/eliminar).
     * Retorna: [['campo', 'etiqueta', 'valor'], ...]
     */
    private function resolverListaValores(AuditoriaResolverService $resolver, string $tabla, array $valores): array
    {
        $excluir = [
            'updated_at',
            'created_at',
            'deleted_at',
            'remember_token',
            'email_verified_at',
            'password',
        ];

        $resueltos = $resolver->resolver($tabla, $valores);
        $resultado = [];

        foreach ($valores as $campo => $valor) {
            if (in_array($campo, $excluir)) {
                continue;
            }

            $resultado[] = [
                'campo'   => $campo,
                'etiqueta' => $resolver->etiquetaCampo($campo),
                'valor'   => $resueltos[$campo . '__label'] ?? $this->formatearValor($valor),
            ];
        }

        return $resultado;
    }

    private function decodificarJson(mixed $valor): ?array
    {
        if ($valor === null) return null;
        if (is_array($valor)) return $valor;
        $decoded = json_decode($valor, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function formatearValor(mixed $valor): string
    {
        if ($valor === null || $valor === '') return '—';
        if ($valor === true  || $valor === 1 || $valor === '1') return 'Sí';
        if ($valor === false || $valor === 0 || $valor === '0') return 'No';
        return (string) $valor;
    }
}
