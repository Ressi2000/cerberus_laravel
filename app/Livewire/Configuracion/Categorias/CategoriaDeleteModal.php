<?php

namespace App\Livewire\Configuracion\Categorias;

use App\Models\CategoriaEquipo;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

/**
 * CategoriaDeleteModal — v2
 *
 * Cambios respecto a v1:
 *  - eliminar() → desactivar(): hace update(['activo' => false]) en vez de ->delete()
 *  - Cuenta solo equipos activos (activo = true), no todos
 *  - Agrega reactivar() para restaurar desde la tabla sin abrir otro modal
 */
class CategoriaDeleteModal extends Component
{
    public bool             $open         = false;
    public ?CategoriaEquipo $categoria    = null;
    public int              $totalEquipos = 0;

    // ── Abrir para desactivar ─────────────────────────────────────────────────

    #[On('openCategoriaEliminar')]
    public function abrir(int $id): void
    {
        $this->categoria = CategoriaEquipo::withCount([
            'equipos' => fn($q) => $q->where('activo', true),
        ])->findOrFail($id);

        $this->totalEquipos = $this->categoria->equipos_count;
        $this->open         = true;
    }

    // ── Desactivar ────────────────────────────────────────────────────────────

    public function desactivar(): void
    {
        if (! $this->categoria) return;

        if ($this->totalEquipos > 0) {
            $this->dispatch('toast', type: 'error',
                message: "No se puede desactivar: tiene {$this->totalEquipos} equipo(s) activo(s) asociado(s).");
            $this->close();
            return;
        }

        try {
            $nombre = $this->categoria->nombre;
            $this->categoria->update(['activo' => false]);

            $this->close();
            $this->dispatch('categoriaEliminada');
            $this->dispatch('toast', type: 'success', message: "Categoría «{$nombre}» desactivada.");
        } catch (\Exception $e) {
            Log::error('CategoriaDeleteModal@desactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al desactivar la categoría.');
            $this->close();
        }
    }

    // ── Reactivar (llamado desde la tabla, sin abrir modal) ───────────────────

    #[On('reactivarCategoria')]
    public function reactivar(int $id): void
    {
        try {
            $categoria = CategoriaEquipo::findOrFail($id);
            $categoria->update(['activo' => true]);

            $this->dispatch('categoriaEliminada'); // mismo evento para refrescar tabla
            $this->dispatch('toast', type: 'success',
                message: "Categoría «{$categoria->nombre}» reactivada.");
        } catch (\Exception $e) {
            Log::error('CategoriaDeleteModal@reactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al reactivar la categoría.');
        }
    }

    // ── Cerrar ────────────────────────────────────────────────────────────────

    public function close(): void
    {
        $this->open = false;
        $this->reset(['categoria', 'totalEquipos']);
    }

    public function render()
    {
        return view('livewire.configuracion.categorias.categoria-delete-modal');
    }
}