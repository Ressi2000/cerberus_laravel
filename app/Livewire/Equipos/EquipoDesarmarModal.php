<?php

namespace App\Livewire\Equipos;

use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Services\DesarmePiezaService;
use App\Services\ExtraccionPiezaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class EquipoDesarmarModal extends Component
{
    public bool $open = false;
    public ?Equipo $equipo = null;

    /** @var array<int, array{tipo:string, atributo_id:int, grupo_instancia_id:?int, descripcion:string}> */
    public array $candidatos = [];
    /** @var array<int, array{extraer:bool, destino:string}> */
    public array $seleccion = [];
    public ?int $depositoId = null;
    public string $observaciones = '';
    public ?Mantenimiento $mantenimientoAbierto = null;

    #[On('openEquipoDesarmar')]
    public function openEquipoDesarmar(int $id): void
    {
        $equipo = Equipo::with(['categoria', 'estado'])->findOrFail($id);

        $this->authorize('desarmar', $equipo);

        $this->mantenimientoAbierto = Mantenimiento::where('equipo_id', $id)->abiertos()->first();

        if ($this->mantenimientoAbierto) {
            $this->dispatch('toast', type: 'error', message:
                "Este equipo tiene un caso de mantenimiento/reparación abierto (#{$this->mantenimientoAbierto->id}). Retira la pieza desde ese caso."
            );
            return;
        }

        $this->equipo = $equipo;
        $this->depositoId = null;
        $this->observaciones = '';

        $this->candidatos = app(ExtraccionPiezaService::class)->candidatos($equipo)
            ->map(fn ($c) => [
                'tipo'               => $c['tipo'],
                'atributo_id'        => $c['atributo']->id,
                'grupo_instancia_id' => $c['grupoInstancia']?->id,
                'descripcion'        => $c['descripcion'],
            ])
            ->values()
            ->toArray();

        // A diferencia de la baja (donde el equipo entero se archiva y tiene
        // sentido ofrecer rescatar todo por defecto), acá el analista debe
        // elegir a propósito qué pieza puntual va a sacarle a un equipo que
        // sigue funcionando — nada viene pre-marcado.
        $this->seleccion = collect($this->candidatos)
            ->mapWithKeys(fn ($c, $i) => [$i => ['extraer' => false, 'destino' => PiezaExtraida::ESTADO_EN_ALMACEN]])
            ->toArray();

        $this->resetValidation();
        $this->open = true;
    }

    public function desarmar(): void
    {
        if (! $this->equipo) return;

        $this->authorize('desarmar', $this->equipo);

        $piezas = $this->piezasAExtraer();

        if (empty($piezas)) {
            $this->addError('seleccion', 'Selecciona al menos una pieza a desarmar.');
            return;
        }

        $necesitaDeposito = collect($piezas)->contains(fn ($p) => $p['destino'] === PiezaExtraida::ESTADO_EN_DEPOSITO);

        $this->validate([
            'depositoId' => $necesitaDeposito ? 'required|exists:depositos,id' : 'nullable|exists:depositos,id',
        ], [
            'depositoId.required' => 'Marcaste al menos una pieza como dañada → Depósito: indica en cuál.',
        ]);

        $equipo   = $this->equipo;
        $deposito = $this->depositoId ? Deposito::findOrFail($this->depositoId) : null;
        $codigo   = $equipo->codigo_interno;

        try {
            app(DesarmePiezaService::class)->desarmar(
                $equipo,
                $piezas,
                Auth::user(),
                deposito: $deposito,
                observaciones: $this->observaciones ?: null,
            );

            $this->close();
            $this->dispatch('toast', type: 'success', message: "Pieza(s) desarmada(s) del equipo «{$codigo}». El equipo sigue activo.");
            $this->dispatch('equipoActualizado');
        } catch (\Exception $e) {
            Log::error('Error desarmando equipo: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: $e->getMessage() ?: 'Ocurrió un error al desarmar la pieza.');
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
        $this->reset(['open', 'equipo', 'candidatos', 'seleccion', 'depositoId', 'observaciones', 'mantenimientoAbierto']);
        $this->resetValidation();
    }

    public function render()
    {
        $depositos = $this->equipo
            ? Deposito::where('empresa_id', $this->equipo->empresa_id)->activos()->orderBy('nombre')->get()
            : collect();

        return view('livewire.equipos.equipo-desarmar-modal', [
            'depositos' => $depositos,
        ]);
    }
}
