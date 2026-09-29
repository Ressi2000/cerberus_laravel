<?php

namespace App\Livewire\Equipos;

use App\Models\AsignacionItem;
use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\PiezaExtraida;
use App\Models\User;
use App\Notifications\EquipoDadoDeBajaNotification;
use App\Services\ExtraccionPiezaService;
use App\Services\ObsolescenciaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class EquipoDeleteModal extends Component
{
    public bool $open = false;
    public ?Equipo $equipo = null;

    /** @var array<int, array{tipo:string, atributo_id:int, grupo_instancia_id:?int, descripcion:string}> */
    public array $candidatos = [];
    /** @var array<int, array{extraer:bool, destino:string}> */
    public array $seleccion = [];
    public ?int $depositoId = null;
    public string $observaciones = '';

    #[On('openEquipoDelete')]
    public function openEquipoDelete(int $id): void
    {
        $equipo = Equipo::with(['categoria', 'estado'])->findOrFail($id);

        $this->authorize('delete', $equipo);

        // ── Bloquear si el equipo está asignado ──────────────────────────────
        $tieneAsignacionActiva = AsignacionItem::where('equipo_id', $id)
            ->where('devuelto', false)
            ->whereHas('asignacion', fn($q) => $q->where('estado', 'Activa'))
            ->exists();

        if ($tieneAsignacionActiva) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'No se puede dar de baja: el equipo tiene una asignación activa. Primero realiza la devolución.'
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

        $this->seleccion = collect($this->candidatos)
            ->mapWithKeys(fn ($c, $i) => [$i => ['extraer' => true, 'destino' => PiezaExtraida::ESTADO_EN_ALMACEN]])
            ->toArray();

        $this->resetValidation();
        $this->open = true;
    }

    public function desactivar(): void
    {
        if (! $this->equipo) return;

        $this->authorize('delete', $this->equipo);

        $this->validate([
            'depositoId' => 'required|exists:depositos,id',
        ], [
            'depositoId.required' => 'Selecciona en qué depósito quedará guardado el equipo.',
        ]);

        $equipo   = $this->equipo;
        $deposito = Deposito::findOrFail($this->depositoId);
        $codigo   = $equipo->codigo_interno;

        try {
            app(ObsolescenciaService::class)->darDeBaja(
                $equipo,
                $deposito,
                $this->piezasAExtraer(),
                Auth::user(),
                observaciones: $this->observaciones ?: null,
            );

            $this->close();
            $this->dispatch('toast', type: 'success', message: "Equipo «{$codigo}» dado de baja correctamente.");
            $this->dispatch('equipoActualizado');
        } catch (\Exception $e) {
            Log::error('Error desactivando equipo: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Ocurrió un error al dar de baja el equipo.');
            return;
        }

        rescue(function () use ($equipo) {
            $notif = new EquipoDadoDeBajaNotification($equipo, auth()->user());
            User::role('Administrador')->each(fn($admin) => $admin->notify($notif));
        }, report: true);
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
        $this->reset(['open', 'equipo', 'candidatos', 'seleccion', 'depositoId', 'observaciones']);
        $this->resetValidation();
    }

    public function render()
    {
        $depositos = $this->equipo
            ? Deposito::where('empresa_id', $this->equipo->empresa_id)->activos()->orderBy('nombre')->get()
            : collect();

        return view('livewire.equipos.equipo-delete-modal', [
            'depositos' => $depositos,
        ]);
    }
}
