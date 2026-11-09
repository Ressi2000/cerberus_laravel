<?php

namespace App\Livewire\Configuracion\Depositos;

use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\PiezaExtraida;
use App\Services\TrasladoDepositoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Contenido de un depósito: equipos archivados (dados de baja) y piezas
 * descartadas que quedaron guardadas ahí, con la acción de trasladar cada
 * uno a otro depósito de la misma empresa — ver TrasladoDepositoService.
 */
class DepositoContenido extends Component
{
    public Deposito $deposito;

    // ── Traslado ──────────────────────────────────────────────────────────
    public bool   $trasladarAbierto = false;
    public string $trasladarTipo    = ''; // 'equipo' | 'pieza'
    public ?int   $trasladarId      = null;
    public ?int   $destinoId        = null;

    public function mount(Deposito $deposito): void
    {
        $this->authorize('view', $deposito);
        $this->deposito = $deposito->load('empresa');
    }

    #[On('depositoContenidoActualizar')]
    public function refrescar(): void
    {
        unset($this->equipos, $this->piezas, $this->depositosDestino);
    }

    #[Computed]
    public function equipos()
    {
        return Equipo::with('categoria')
            ->where('deposito_id', $this->deposito->id)
            ->orderBy('codigo_interno')
            ->get();
    }

    #[Computed]
    public function piezas()
    {
        return PiezaExtraida::with(['atributo', 'equipoOrigen'])
            ->where('deposito_id', $this->deposito->id)
            ->where('estado', PiezaExtraida::ESTADO_EN_DEPOSITO)
            ->latest()
            ->get();
    }

    #[Computed]
    public function depositosDestino()
    {
        return Deposito::where('empresa_id', $this->deposito->empresa_id)
            ->where('id', '!=', $this->deposito->id)
            ->activos()
            ->orderBy('nombre')
            ->get();
    }

    public function abrirTrasladarEquipo(int $equipoId): void
    {
        $this->authorize('update', $this->deposito);
        $this->trasladarTipo = 'equipo';
        $this->trasladarId   = $equipoId;
        $this->destinoId     = null;
        $this->resetValidation();
        $this->trasladarAbierto = true;
    }

    public function abrirTrasladarPieza(int $piezaId): void
    {
        $this->authorize('update', $this->deposito);
        $this->trasladarTipo = 'pieza';
        $this->trasladarId   = $piezaId;
        $this->destinoId     = null;
        $this->resetValidation();
        $this->trasladarAbierto = true;
    }

    public function cerrarTrasladar(): void
    {
        $this->trasladarAbierto = false;
        $this->resetValidation();
    }

    public function trasladar(): void
    {
        $this->authorize('update', $this->deposito);

        $this->validate([
            'destinoId' => 'required|exists:depositos,id',
        ], [
            'destinoId.required' => 'Selecciona a qué depósito se traslada.',
        ]);

        $destino  = Deposito::findOrFail($this->destinoId);
        $servicio = app(TrasladoDepositoService::class);

        try {
            if ($this->trasladarTipo === 'equipo') {
                $equipo = Equipo::findOrFail($this->trasladarId);
                $servicio->trasladarEquipo($equipo, $destino, Auth::user());
                $this->dispatch('toast', type: 'success', message: "Equipo «{$equipo->codigo_interno}» trasladado a «{$destino->nombre}».");
            } else {
                $pieza = PiezaExtraida::findOrFail($this->trasladarId);
                $servicio->trasladarPieza($pieza, $destino, Auth::user());
                $this->dispatch('toast', type: 'success', message: "Pieza trasladada a «{$destino->nombre}».");
            }

            $this->cerrarTrasladar();
            $this->refrescar();
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Exception $e) {
            Log::error('DepositoContenido@trasladar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al trasladar.');
        }
    }

    public function render()
    {
        return view('livewire.configuracion.depositos.deposito-contenido');
    }
}
