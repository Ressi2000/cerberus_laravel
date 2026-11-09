<?php

namespace App\Livewire\Mantenimientos;

use App\Models\AtributoEquipo;
use App\Models\Deposito;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Services\ExtraccionPiezaService;
use App\Services\SustitucionPiezaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel de piezas de una reparación (Correctivo): retirar una pieza vieja o
 * dañada del equipo (queda trazada, va a Almacén si es reutilizable o a
 * Depósito si no) e instalar una pieza previamente rescatada, actualizando
 * los atributos del equipo receptor. Ver SustitucionPiezaService.
 */
class MantenimientoPiezasPanel extends Component
{
    // Locked: el id no se puede cambiar desde el navegador.
    #[Locked]
    public int $mantenimientoId;

    // ── Retirar pieza del equipo ─────────────────────────────────────────
    public bool   $retirarAbierto      = false;
    public ?int   $retirarIndex        = null;
    public string $retirarDestino      = PiezaExtraida::ESTADO_EN_ALMACEN;
    public ?int   $retirarDepositoId   = null;
    public string $retirarObservaciones = '';

    // ── Instalar pieza rescatada ─────────────────────────────────────────
    public bool   $instalarAbierto     = false;
    public ?int   $instalarPiezaId     = null;
    public string $instalarModo        = SustitucionPiezaService::MODO_SUSTITUIR;
    public ?int   $instalarInstanciaId = null;
    /** Al sustituir: a dónde va la pieza que sale del equipo. */
    public string $instalarDestinoSaliente   = PiezaExtraida::ESTADO_EN_ALMACEN;
    public ?int   $instalarDepositoSaliente  = null;

    public function mount(int $mantenimientoId): void
    {
        $this->mantenimientoId = $mantenimientoId;
    }

    #[On('mantenimientoActualizado')]
    public function refrescar(): void
    {
        unset($this->mantenimiento, $this->candidatosRetiro, $this->piezasDisponibles, $this->historial);
    }

    #[Computed]
    public function mantenimiento(): Mantenimiento
    {
        return Mantenimiento::with('equipo')->findOrFail($this->mantenimientoId);
    }

    #[Computed]
    public function candidatosRetiro(): array
    {
        return app(ExtraccionPiezaService::class)->candidatos($this->mantenimiento->equipo)
            ->map(fn ($c) => [
                'tipo'               => $c['tipo'],
                'atributo_id'        => $c['atributo']->id,
                'grupo_instancia_id' => $c['grupoInstancia']?->id,
                'descripcion'        => $c['descripcion'],
            ])
            ->values()
            ->toArray();
    }

    /**
     * Varias piezas en Almacén pueden tener exactamente la misma descripción
     * (ej. dos "RAM (8GB)" de equipos distintos) — sin más contexto, el
     * selector sería indistinguible. Se agrega de qué equipo vino y cuándo
     * se extrajo para que cada opción sea identificable por sí sola.
     */
    #[Computed]
    public function piezasDisponibles(): array
    {
        return app(SustitucionPiezaService::class)->piezasDisponibles($this->mantenimiento->equipo)
            ->map(fn (PiezaExtraida $p) => [
                'id'          => $p->id,
                'descripcion' => $p->atributo->describirValor($p->valor_extraido)
                    . ' — de ' . ($p->equipoOrigen->codigo_interno ?? 'equipo #' . $p->equipo_origen_id)
                    . ' (' . $p->created_at->format('d/m/Y') . ')',
                'es_grupo'    => $p->atributo->esGrupo(),
            ])
            ->values()
            ->toArray();
    }

    /** Instancias vigentes del atributo de la pieza seleccionada (para "sustituir" en un grupo). */
    #[Computed]
    public function instanciasParaSustituir(): array
    {
        if (! $this->instalarPiezaId) return [];

        $pieza = PiezaExtraida::with('atributo')->find($this->instalarPiezaId);
        if (! $pieza || ! $pieza->atributo?->esGrupo()) return [];

        return EquipoAtributoGrupoInstancia::where('equipo_id', $this->mantenimiento->equipo->id)
            ->where('atributo_id', $pieza->atributo_id)
            ->where('es_actual', true)
            ->orderBy('orden')
            ->get()
            ->mapWithKeys(fn ($i) => [$i->id => $pieza->atributo->describirValor($i->valores)])
            ->toArray();
    }

    /** Historial de piezas retiradas/instaladas durante esta reparación (kardex por mantenimiento). */
    #[Computed]
    public function historial(): \Illuminate\Support\Collection
    {
        return PiezaExtraidaMovimiento::where('mantenimiento_id', $this->mantenimientoId)
            ->with('pieza.atributo')
            ->latest()
            ->get();
    }

    // ── Retirar ───────────────────────────────────────────────────────────

    public function abrirRetirar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'Solo se pueden retirar piezas mientras el caso está «En reparación».');
            return;
        }

        $this->reset(['retirarIndex', 'retirarDepositoId', 'retirarObservaciones']);
        $this->retirarDestino = PiezaExtraida::ESTADO_EN_ALMACEN;
        $this->resetValidation();
        $this->retirarAbierto = true;
    }

    public function cerrarRetirar(): void
    {
        $this->retirarAbierto = false;
        $this->resetValidation();
    }

    public function retirar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        // Se revisa aquí y no solo en abrirRetirar(): retirar() se puede
        // invocar directamente desde el navegador.
        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'No se pueden retirar piezas en el estado actual del caso.');
            return;
        }

        $this->validate([
            'retirarIndex'      => 'required|integer',
            'retirarDepositoId' => $this->retirarDestino === PiezaExtraida::ESTADO_EN_DEPOSITO ? 'required|exists:depositos,id' : 'nullable',
        ], [
            'retirarDepositoId.required' => 'Selecciona a qué depósito va esta pieza.',
        ]);

        $candidato = $this->candidatosRetiro[$this->retirarIndex] ?? null;
        if (! $candidato) {
            $this->dispatch('toast', type: 'error', message: 'Esa pieza ya no está disponible para retirar.');
            $this->cerrarRetirar();
            return;
        }

        // Solo depósitos activos de la empresa del caso.
        $deposito = null;
        if ($this->retirarDestino === PiezaExtraida::ESTADO_EN_DEPOSITO) {
            $deposito = Deposito::activos()
                ->where('empresa_id', $m->empresa_id)
                ->find($this->retirarDepositoId);

            if (! $deposito) {
                $this->addError('retirarDepositoId', 'Ese depósito no está disponible para este caso.');
                return;
            }
        }

        $servicio = app(SustitucionPiezaService::class);

        try {
            if ($candidato['tipo'] === 'grupo') {
                $instancia = EquipoAtributoGrupoInstancia::findOrFail($candidato['grupo_instancia_id']);
                $servicio->retirarDeGrupo($m->equipo, $instancia, $this->retirarDestino, Auth::user(), $deposito, $m, $this->retirarObservaciones ?: null);
            } else {
                $atributo = AtributoEquipo::findOrFail($candidato['atributo_id']);
                $servicio->retirar($m->equipo, $atributo, $this->retirarDestino, Auth::user(), $deposito, $m, $this->retirarObservaciones ?: null);
            }

            $this->dispatch('toast', type: 'success', message: "«{$candidato['descripcion']}» retirada del equipo.");
            $this->cerrarRetirar();
            unset($this->mantenimiento, $this->candidatosRetiro, $this->piezasDisponibles, $this->historial);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Exception $e) {
            Log::error('MantenimientoPiezasPanel@retirar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al retirar la pieza.');
        }
    }

    // ── Instalar ──────────────────────────────────────────────────────────

    public function abrirInstalar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'Solo se pueden instalar piezas mientras el caso está «En reparación».');
            return;
        }

        $this->reset(['instalarPiezaId', 'instalarInstanciaId', 'instalarDepositoSaliente']);
        $this->instalarDestinoSaliente = PiezaExtraida::ESTADO_EN_ALMACEN;
        $this->instalarModo = SustitucionPiezaService::MODO_SUSTITUIR;
        $this->resetValidation();
        $this->instalarAbierto = true;
    }

    public function cerrarInstalar(): void
    {
        $this->instalarAbierto = false;
        $this->resetValidation();
    }

    public function updatedInstalarPiezaId(): void
    {
        $this->instalarInstanciaId = null;
        unset($this->instanciasParaSustituir);

        $pieza = $this->instalarPiezaId ? PiezaExtraida::with('atributo')->find($this->instalarPiezaId) : null;
        $this->instalarModo = ($pieza && $pieza->atributo?->esGrupo())
            ? SustitucionPiezaService::MODO_AGREGAR
            : SustitucionPiezaService::MODO_SUSTITUIR;
    }

    public function instalar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'No se pueden instalar piezas en el estado actual del caso.');
            return;
        }

        $this->validate([
            'instalarPiezaId' => 'required|exists:piezas_extraidas,id',
        ]);

        if ($this->instalarModo === SustitucionPiezaService::MODO_SUSTITUIR && ! empty($this->instanciasParaSustituir) && ! $this->instalarInstanciaId) {
            $this->addError('instalarInstanciaId', 'Selecciona cuál pieza actual reemplaza.');
            return;
        }

        // Solo piezas de la empresa del caso.
        $pieza = PiezaExtraida::with('atributo')
            ->where('empresa_id', $m->empresa_id)
            ->find($this->instalarPiezaId);

        if (! $pieza) {
            $this->addError('instalarPiezaId', 'Esa pieza no está disponible para este caso.');
            return;
        }

        // Pieza que sale al sustituir: a un depósito activo de la empresa del caso.
        $depositoSaliente = null;
        if ($this->instalarModo === SustitucionPiezaService::MODO_SUSTITUIR
            && $this->instalarDestinoSaliente === PiezaExtraida::ESTADO_EN_DEPOSITO) {
            $depositoSaliente = Deposito::activos()
                ->where('empresa_id', $m->empresa_id)
                ->find($this->instalarDepositoSaliente);

            if (! $depositoSaliente) {
                $this->addError('instalarDepositoSaliente', 'Selecciona a qué depósito va la pieza que sale.');
                return;
            }
        }

        try {
            app(SustitucionPiezaService::class)->instalar(
                $pieza, $m->equipo, $this->instalarModo, Auth::user(), $m, $this->instalarInstanciaId,
                $this->instalarDestinoSaliente, $depositoSaliente,
            );

            $this->dispatch('toast', type: 'success', message: "«{$pieza->atributo->describirValor($pieza->valor_extraido)}» instalada en el equipo.");
            $this->cerrarInstalar();
            unset($this->mantenimiento, $this->candidatosRetiro, $this->piezasDisponibles, $this->historial);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Exception $e) {
            Log::error('MantenimientoPiezasPanel@instalar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al instalar la pieza.');
        }
    }

    public function render()
    {
        $depositos = Deposito::where('empresa_id', $this->mantenimiento->empresa_id)->activos()->orderBy('nombre')->get();

        return view('livewire.mantenimientos.mantenimiento-piezas-panel', [
            'depositos' => $depositos,
        ]);
    }
}
