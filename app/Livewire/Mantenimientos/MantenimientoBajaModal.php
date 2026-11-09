<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Deposito;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Services\ExtraccionPiezaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dar de baja un equipo porque la reparación no tiene solución. Decisión
 * patrimonial e irreversible — reservada al Administrador
 * (MantenimientoPolicy::aprobarBaja). Si el equipo tenía una asignación
 * activa, se cierra en el mismo paso. Antes de archivar el equipo se
 * ofrece rescatar sus piezas reutilizables — mismo flujo que
 * EquipoDeleteModal, ver ObsolescenciaService::extraerPiezas().
 */
class MantenimientoBajaModal extends Component
{
    public bool $open = false;
    public ?int $mantenimientoId = null;
    public string $motivo = '';

    /** @var array<int, array{tipo:string, atributo_id:int, grupo_instancia_id:?int, descripcion:string}> */
    public array $candidatos = [];
    /** @var array<int, array{extraer:bool, destino:string}> */
    public array $seleccion = [];
    public ?int $depositoId = null;

    #[On('openMantenimientoBaja')]
    public function abrir(int $mantenimientoId): void
    {
        $m = Mantenimiento::with('equipo')->findOrFail($mantenimientoId);
        $this->authorize('aprobarBaja', $m);

        $this->mantenimientoId = $mantenimientoId;
        $this->motivo = '';
        $this->depositoId = null;

        $this->candidatos = app(ExtraccionPiezaService::class)->candidatos($m->equipo)
            ->map(fn ($c) => [
                'tipo'               => $c['tipo'],
                'atributo_id'        => $c['atributo']->id,
                'grupo_instancia_id' => $c['grupoInstancia']?->id,
                'descripcion'        => $c['descripcion'],
            ])
            ->values()
            ->toArray();

        $this->seleccion = collect($this->candidatos)
            ->mapWithKeys(fn ($c, $i) => [$i => ['extraer' => true, 'destino' => PiezaExtraida::ESTADO_EN_ALMACEN]])
            ->toArray();

        $this->resetValidation();
        $this->open = true;
    }

    public function confirmar(): void
    {
        $m = Mantenimiento::with('equipo')->findOrFail($this->mantenimientoId);
        $this->authorize('aprobarBaja', $m);

        $this->validate([
            'motivo'     => 'required|string|max:1000',
            'depositoId' => 'required|exists:depositos,id',
        ], [
            'depositoId.required' => 'Selecciona en qué depósito quedará guardado el equipo.',
        ]);

        $deposito = Deposito::findOrFail($this->depositoId);

        try {
            $m->marcarDadoDeBaja(Auth::user(), $this->motivo, $deposito, $this->piezasAExtraer());
            $this->dispatch('toast', type: 'success', message: 'Equipo dado de baja.');
            $this->dispatch('mantenimientoActualizado');
            $this->close();
        } catch (\Exception $e) {
            Log::error('MantenimientoBajaModal@confirmar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al dar de baja el equipo.');
        }
    }

    /** @return array<int, array{tipo:string, atributo_id:int, grupo_instancia_id:?int, destino:string}> */
    private function piezasAExtraer(): array
    {
        return collect($this->seleccion)
            ->filter(fn ($s) => $s['extraer'])
            ->map(fn ($s, $i) => [
                'tipo'               => $this->candidatos[$i]['tipo'],
                'atributo_id'        => $this->candidatos[$i]['atributo_id'],
                'grupo_instancia_id' => $this->candidatos[$i]['grupo_instancia_id'],
                'destino'            => $s['destino'],
            ])
            ->values()
            ->toArray();
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['mantenimientoId', 'motivo', 'candidatos', 'seleccion', 'depositoId']);
        $this->resetValidation();
    }

    public function render()
    {
        $depositos = collect();

        if ($this->mantenimientoId && ($m = Mantenimiento::find($this->mantenimientoId))) {
            $depositos = Deposito::where('empresa_id', $m->empresa_id)->activos()->orderBy('nombre')->get();
        }

        return view('livewire.mantenimientos.mantenimiento-baja-modal', [
            'depositos' => $depositos,
        ]);
    }
}
