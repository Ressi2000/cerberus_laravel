<?php

namespace App\Livewire\Configuracion\Empresas;

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * EmpresaDeleteModal — v3 (corregido)
 *
 * Comportamiento corregido para ser coherente con el resto de tablas maestras:
 * NO permite desactivar una empresa si tiene:
 *   - Usuarios activos (estado = 'Activo') con esta empresa como nómina
 *   - Equipos activos (activo = true) registrados en esta empresa
 *
 * Esto evita inconsistencias en el sistema (usuarios y equipos sin empresa válida).
 * Si necesita desactivarse, primero hay que mover o inactivar sus recursos.
 */
class EmpresaDeleteModal extends Component
{
    public bool     $open          = false;
    public ?Empresa $empresa       = null;
    public int      $totalUsuarios = 0;
    public int      $totalEquipos  = 0;

    #[On('openEmpresaEliminar')]
    public function abrir(int $id): void
    {
        $this->empresa = Empresa::withCount([
            'usuarios' => fn($q) => $q->where('estado', 'Activo'),
            'equipos'  => fn($q) => $q->where('activo', true),
        ])->findOrFail($id);

        $this->totalUsuarios = $this->empresa->usuarios_count;
        $this->totalEquipos  = $this->empresa->equipos_count;
        $this->open          = true;
    }

    public function desactivar(): void
    {
        if (! $this->empresa) return;

        // Bloquear si tiene recursos activos vinculados
        $tieneRecursos = $this->totalUsuarios > 0 || $this->totalEquipos > 0;

        if ($tieneRecursos) {
            $partes = array_filter([
                $this->totalUsuarios > 0 ? "{$this->totalUsuarios} usuario(s) activo(s)" : null,
                $this->totalEquipos  > 0 ? "{$this->totalEquipos} equipo(s) activo(s)"   : null,
            ]);
            $this->dispatch('toast', type: 'error',
                message: 'No se puede desactivar: tiene ' . implode(' y ', $partes) . '.');
            $this->close();
            return;
        }

        try {
            $nombre = $this->empresa->nombre;
            $this->empresa->update(['activo' => false]);

            $this->close();
            $this->dispatch('empresaEliminada');
            $this->dispatch('toast', type: 'success', message: "Empresa «{$nombre}» desactivada.");
        } catch (\Exception $e) {
            Log::error('EmpresaDeleteModal@desactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al desactivar la empresa.');
            $this->close();
        }
    }

    #[On('reactivarEmpresa')]
    public function reactivar(int $id): void
    {
        try {
            $empresa = Empresa::findOrFail($id);
            $empresa->update(['activo' => true]);

            $this->dispatch('empresaEliminada');
            $this->dispatch('toast', type: 'success', message: "Empresa «{$empresa->nombre}» reactivada.");
        } catch (\Exception $e) {
            Log::error('EmpresaDeleteModal@reactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al reactivar la empresa.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['empresa', 'totalUsuarios', 'totalEquipos']);
    }

    public function render()
    {
        return view('livewire.configuracion.empresas.empresa-delete-modal');
    }
}