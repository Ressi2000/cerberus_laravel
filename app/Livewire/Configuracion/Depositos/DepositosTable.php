<?php

namespace App\Livewire\Configuracion\Depositos;

use App\Models\Deposito;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DepositosTable extends Component
{
    public string $search            = '';
    public string $empresa_id        = '';
    public bool   $mostrar_inactivos = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Deposito::class);
    }

    #[On('depositoGuardado')]
    public function refrescar(): void
    {
        //
    }

    public function reactivar(int $id): void
    {
        $deposito = Deposito::findOrFail($id);
        $this->authorize('update', $deposito);
        $deposito->update(['activo' => true]);
        $this->dispatch('toast', type: 'success', message: "Depósito «{$deposito->nombre}» reactivado.");
    }

    public function desactivar(int $id): void
    {
        $deposito = Deposito::findOrFail($id);
        $this->authorize('delete', $deposito);
        $deposito->update(['activo' => false]);
        $this->dispatch('toast', type: 'success', message: "Depósito «{$deposito->nombre}» desactivado.");
    }

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        if ($actor->hasRole('Administrador')) {
            return Empresa::orderBy('nombre')->pluck('nombre', 'id');
        }

        return Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    #[Computed]
    public function total(): int
    {
        return Deposito::visiblePara(Auth::user())->where('activo', true)->count();
    }

    #[Computed]
    public function totalInactivos(): int
    {
        return Deposito::visiblePara(Auth::user())->where('activo', false)->count();
    }

    #[Computed]
    public function depositos()
    {
        return Deposito::visiblePara(Auth::user())
            ->with(['empresa', 'ubicacion'])
            ->when(! $this->mostrar_inactivos, fn ($q) => $q->where('activo', true))
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->get();
    }

    public function render()
    {
        return view('livewire.configuracion.depositos.depositos-table');
    }
}
