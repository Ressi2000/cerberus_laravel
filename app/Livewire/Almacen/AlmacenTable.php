<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\PiezaExtraida;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AlmacenTable extends Component
{
    use WithPagination;

    #[Url(as: 'empresa')]
    public string $empresa_id = '';

    public string $search            = '';
    public bool   $mostrar_inactivos = false;

    #[On('componenteGuardado')]
    #[On('stockActualizado')]
    #[On('componenteEliminado')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $actor = Auth::user();
        $this->reset(['search', 'mostrar_inactivos']);
        $this->empresa_id = $actor->hasRole('Administrador') ? '' : (string) ($actor->empresa_activa_id ?? '');
        $this->resetPage();
    }

    public function mount(): void
    {
        $actor = Auth::user();
        if (! $actor->hasRole('Administrador')) {
            $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
        }
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
    public function activeFiltersCount(): int
    {
        $actor = Auth::user();

        return collect([
            $this->search !== '',
            $this->mostrar_inactivos,
            $actor->hasRole('Administrador') && $this->empresa_id !== '',
        ])->filter()->count();
    }

    #[Computed]
    public function totalComponentes(): int
    {
        return ComponenteAlmacen::visiblePara(Auth::user())->activos()->count();
    }

    /** Familias con al menos un componente activo visible. */
    #[Computed]
    public function totalFamilias(): int
    {
        return ComponenteAlmacen::visiblePara(Auth::user())->activos()->whereNotNull('familia_id')->distinct()->count('familia_id');
    }

    /** Piezas individuales (con serial o código) que hay hoy en el almacén. */
    #[Computed]
    public function totalPiezasEnAlmacen(): int
    {
        return PiezaExtraida::visiblePara(Auth::user())->enAlmacen()->count();
    }

    /** Unidades en stock de familias a granel. */
    #[Computed]
    public function totalUnidadesAGranel(): int
    {
        return (int) ComponenteAlmacen::visiblePara(Auth::user())->activos()
            ->whereHas('familia', fn ($f) => $f->where('control', \App\Models\FamiliaComponente::CONTROL_GRANEL))
            ->sum('stock_actual');
    }

    /** Componentes pedidos por casos abiertos que siguen sin entregarse (bandeja de solicitudes). */
    #[Computed]
    public function totalSolicitudesPendientes(): int
    {
        return \App\Models\MantenimientoComponente::pendientesVisiblesPara(Auth::user())->count();
    }

    #[Computed]
    public function totalSinStock(): int
    {
        return ComponenteAlmacen::visiblePara(Auth::user())->activos()->where('stock_actual', 0)->count();
    }

    #[Computed]
    public function totalUnidadesEnStock(): int
    {
        return (int) ComponenteAlmacen::visiblePara(Auth::user())->activos()->sum('stock_actual');
    }

    #[On('componenteDesactivarConfirmado')]
    public function desactivar(int $id): void
    {
        $componente = ComponenteAlmacen::findOrFail($id);
        $this->authorize('delete', $componente);

        try {
            $componente->desactivar();
            $this->dispatch('toast', type: 'success', message: "«{$componente->nombre}» desactivado.");
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function reactivar(int $id): void
    {
        $componente = ComponenteAlmacen::findOrFail($id);
        $this->authorize('delete', $componente);
        $componente->update(['activo' => true]);
        $this->dispatch('toast', type: 'success', message: "«{$componente->nombre}» reactivado.");
    }

    /**
     * Cuántas de las unidades en stock de cada componente son nuevas vs
     * reutilizadas (ver PiezaManualService/ExtraccionPiezaService) — para
     * que la tabla principal ya diga algo sin tener que entrar a "Ver
     * piezas en stock" cada vez. Lo que no cuadra con nuevas+reutilizadas
     * es stock viejo sin trazar (de antes de que existiera esta función).
     */
    /**
     * Componentes visibles con los filtros, agrupados por familia (las
     * familias en orden alfabético; los que aún no tienen familia, al final
     * para asignarles una). Cada grupo trae sus totales.
     */
    #[Computed]
    public function grupos()
    {
        $componentes = ComponenteAlmacen::with(['empresa', 'familia'])
            ->visiblePara(Auth::user())
            ->when(! $this->mostrar_inactivos, fn ($q) => $q->where('activo', true))
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%")
                  ->orWhereHas('familia', fn ($f) => $f->where('nombre', 'like', "%{$this->search}%"))
                  // Buscar un serial muestra también la variante a la que pertenece.
                  ->orWhereHas('piezasExtraidas', fn ($p) => $p->where('identificador', 'like', "%{$this->search}%"));
            }))
            ->withCount([
                'piezasExtraidas as nuevas_en_stock' => fn ($q) => $q
                    ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
                    ->where('condicion', PiezaExtraida::CONDICION_NUEVO),
                'piezasExtraidas as reutilizadas_en_stock' => fn ($q) => $q
                    ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
                    ->where('condicion', PiezaExtraida::CONDICION_REUTILIZADO),
            ])
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return $componentes
            ->groupBy(fn ($c) => $c->familia_id ?? 0)
            ->map(fn ($items) => [
                'familia'     => $items->first()->familia,
                'componentes' => $items,
                'variantes'   => $items->count(),
                'stock'       => $items->sum('stock_actual'),
                'sin_stock'   => $items->where('activo', true)->where('stock_actual', 0)->count(),
            ])
            ->sortBy(fn ($g) => $g['familia'] ? '0' . mb_strtolower($g['familia']->nombre) : '1')
            ->values();
    }

    /**
     * Piezas cuyo serial o código coincide con la búsqueda, estén donde
     * estén (en almacén, instaladas, en depósito…): para encontrar una pieza
     * física y abrir su ficha. Desde 3 caracteres, para no listar de todo.
     */
    #[Computed]
    public function piezasEncontradas()
    {
        $texto = trim($this->search);
        if (mb_strlen($texto) < 3) {
            return collect();
        }

        return PiezaExtraida::visiblePara(Auth::user())
            ->with(['componenteAlmacen', 'atributo', 'equipoDestino', 'deposito'])
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            // El QR de una pieza sin serial dice "PIEZA-{n.º interno}".
            ->where(fn ($q) => preg_match('/^PIEZA-(\d+)$/i', $texto, $m)
                ? $q->whereKey((int) $m[1])
                : $q->where('identificador', 'like', "%{$texto}%"))
            ->orderByRaw('identificador = ? desc', [$texto])
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    /** Familias para abrir una nueva variante desde el grupo vacío. */
    #[Computed]
    public function hayComponentesSinFamilia(): bool
    {
        return ComponenteAlmacen::visiblePara(Auth::user())->whereNull('familia_id')->where('activo', true)->exists();
    }

    public function render()
    {
        return view('livewire.almacen.almacen-table');
    }
}
