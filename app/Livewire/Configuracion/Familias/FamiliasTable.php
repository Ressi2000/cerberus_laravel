<?php

namespace App\Livewire\Configuracion\Familias;

use App\Models\FamiliaComponente;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Listado de familias de piezas (RAM, Discos…) con su ficha técnica y las
 * categorías que la usan. Solo Administrador (igual que el resto de
 * Configuración).
 */
class FamiliasTable extends Component
{
    public string $search             = '';
    public bool   $mostrar_inactivas  = false;

    public function mount(): void
    {
        $this->autorizar();
    }

    #[On('familiaGuardada')]
    public function refrescar(): void
    {
        unset($this->familias);
    }

    #[Computed]
    public function familias()
    {
        return FamiliaComponente::with(['atributos.categoria'])
            ->when(! $this->mostrar_inactivas, fn ($q) => $q->where('activo', true))
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'familias'    => FamiliaComponente::where('activo', true)->count(),
            'vinculadas'  => \App\Models\AtributoEquipo::whereNotNull('familia_id')->count(),
            'inactivas'   => FamiliaComponente::where('activo', false)->count(),
        ];
    }

    /** Desactivar solo la oculta para nuevas vinculaciones; las que ya la usan siguen igual. */
    public function cambiarActivo(int $id): void
    {
        $this->autorizar();

        $familia = FamiliaComponente::findOrFail($id);
        $familia->update(['activo' => ! $familia->activo]);

        $this->dispatch('toast', type: 'success', message: $familia->activo
            ? "Familia «{$familia->nombre}» reactivada."
            : "Familia «{$familia->nombre}» desactivada: ya no se ofrece para vincular nuevas características.");
        $this->refrescar();
    }

    private function autorizar(): void
    {
        abort_unless(Auth::user()?->hasRole('Administrador'), 403);
    }

    public function render()
    {
        return view('livewire.configuracion.familias.familias-table');
    }
}
