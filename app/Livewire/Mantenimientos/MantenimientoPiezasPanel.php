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
 * Panel de piezas de un caso: retirar una pieza del equipo (queda trazada,
 * va a Almacén si está buena o a Depósito si está dañada) y ver el
 * historial de piezas del caso. Para INSTALAR una pieza del almacén se usa
 * "Usar del almacén" (UsarDelAlmacenModal).
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
    /** Si hay varias piezas idénticas instaladas, cuál sale (por su serial). */
    public string $retirarPiezaId      = '';
    /** Serial de la pieza que sale, si no tenía uno registrado (opcional). */
    public string $retirarSerial       = '';

    public function mount(int $mantenimientoId): void
    {
        $this->mantenimientoId = $mantenimientoId;
    }

    #[On('mantenimientoActualizado')]
    public function refrescar(): void
    {
        unset($this->mantenimiento, $this->candidatosRetiro, $this->historial);
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
     * Piezas con serial que podrían ser la que se retira (entraron desde el
     * almacén con esos mismos valores). Con más de una, se pregunta cuál es.
     */
    #[Computed]
    public function piezasDelRetiro()
    {
        $candidato = $this->retirarIndex !== null ? ($this->candidatosRetiro[$this->retirarIndex] ?? null) : null;
        if (! $candidato) {
            return collect();
        }

        $equipo = $this->mantenimiento->equipo;
        $atributo = AtributoEquipo::find($candidato['atributo_id']);
        if (! $atributo) {
            return collect();
        }

        $valores = $candidato['tipo'] === 'grupo'
            ? (EquipoAtributoGrupoInstancia::find($candidato['grupo_instancia_id'])?->valores ?? [])
            : ['valor' => \App\Models\EquipoAtributoValor::where('equipo_id', $equipo->id)->where('atributo_id', $atributo->id)->where('es_actual', true)->value('valor')];

        return app(ExtraccionPiezaService::class)->piezasInstaladasCoincidentes($equipo, $atributo, $valores);
    }

    public function updatedRetirarIndex(): void
    {
        $this->retirarPiezaId = '';
        unset($this->piezasDelRetiro);
    }

    /** Historial de piezas retiradas/instaladas durante esta reparación (kardex por mantenimiento). */
    #[Computed]
    public function historial(): \Illuminate\Support\Collection
    {
        return PiezaExtraidaMovimiento::where('mantenimiento_id', $this->mantenimientoId)
            ->with(['pieza.atributo', 'pieza.componenteAlmacen'])
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

        $this->reset(['retirarIndex', 'retirarDepositoId', 'retirarObservaciones', 'retirarPiezaId', 'retirarSerial']);
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

        // Varias piezas idénticas instaladas: hay que decir cuál sale.
        if ($this->piezasDelRetiro->count() > 1 && $this->retirarPiezaId === '') {
            $this->addError('retirarPiezaId', 'Hay varias iguales en el equipo: elige cuál sale por su serial.');
            return;
        }
        $piezaId = $this->retirarPiezaId !== '' ? (int) $this->retirarPiezaId : null;
        $serial  = mb_substr(trim($this->retirarSerial), 0, 100) ?: null;

        $servicio = app(SustitucionPiezaService::class);

        try {
            if ($candidato['tipo'] === 'grupo') {
                $instancia = EquipoAtributoGrupoInstancia::findOrFail($candidato['grupo_instancia_id']);
                $servicio->retirarDeGrupo($m->equipo, $instancia, $this->retirarDestino, Auth::user(), $deposito, $m, $this->retirarObservaciones ?: null, $piezaId, $serial);
            } else {
                $atributo = AtributoEquipo::findOrFail($candidato['atributo_id']);
                $servicio->retirar($m->equipo, $atributo, $this->retirarDestino, Auth::user(), $deposito, $m, $this->retirarObservaciones ?: null, $piezaId, $serial);
            }

            $this->dispatch('toast', type: 'success', message: "«{$candidato['descripcion']}» retirada del equipo.");
            $this->cerrarRetirar();
            unset($this->mantenimiento, $this->candidatosRetiro, $this->historial, $this->piezasDelRetiro);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Exception $e) {
            Log::error('MantenimientoPiezasPanel@retirar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al retirar la pieza.');
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
