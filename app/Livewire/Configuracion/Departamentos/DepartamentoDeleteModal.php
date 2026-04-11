<?php

namespace App\Livewire\Configuracion\Departamentos;

use App\Models\Departamento;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class DepartamentoDeleteModal extends Component
{
    public bool          $open          = false;
    public ?Departamento $departamento  = null;
    public int           $totalUsuarios = 0;
    public int           $totalCargos   = 0;

    #[On('openDepartamentoEliminar')]
    public function abrir(int $id): void
    {
        $this->departamento = Departamento::withCount([
            'usuarios' => fn($q) => $q->where('estado', 'Activo'),
            'cargos'   => fn($q) => $q->where('activo', true),
        ])->findOrFail($id);

        $this->totalUsuarios = $this->departamento->usuarios_count;
        $this->totalCargos   = $this->departamento->cargos_count;
        $this->open          = true;
    }

    public function desactivar(): void
    {
        if (! $this->departamento) return;

        if ($this->totalUsuarios > 0) {
            $this->dispatch('toast', type: 'error',
                message: "No se puede desactivar: tiene {$this->totalUsuarios} usuario(s) activo(s) en este departamento.");
            $this->close();
            return;
        }

        try {
            $nombre = $this->departamento->nombre;

            // Desactivar también los cargos hijos sin usuarios activos
            $this->departamento->cargos()
                ->where('activo', true)
                ->whereDoesntHave('usuarios', fn($q) => $q->where('estado', 'Activo'))
                ->update(['activo' => false]);

            $this->departamento->update(['activo' => false]);

            $this->close();
            $this->dispatch('departamentoEliminado');
            $this->dispatch('toast', type: 'success', message: "Departamento «{$nombre}» desactivado.");
        } catch (\Exception $e) {
            Log::error('DepartamentoDeleteModal@desactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al desactivar el departamento.');
            $this->close();
        }
    }

    #[On('reactivarDepartamento')]
    public function reactivar(int $id): void
    {
        try {
            $departamento = Departamento::findOrFail($id);
            $departamento->update(['activo' => true]);

            $this->dispatch('departamentoEliminado');
            $this->dispatch('toast', type: 'success',
                message: "Departamento «{$departamento->nombre}» reactivado.");
        } catch (\Exception $e) {
            Log::error('DepartamentoDeleteModal@reactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al reactivar el departamento.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['departamento', 'totalUsuarios', 'totalCargos']);
    }

    public function render()
    {
        return view('livewire.configuracion.departamentos.departamento-delete-modal');
    }
}