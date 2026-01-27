<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class UsuariosTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public $search = '';

    #[Url]
    public $rol_id = '';

    #[Url]
    public $empresa_id = '';

    #[Url]
    public $departamento_id = '';

    #[Url]
    public $cargo_id = '';

    #[Url]
    public $ubicacion_id = '';

    #[Url]
    public $estado = '';

    #[Url]
    public $perPage = 10;

    public function loadData()
    {
        // incluso si está vacío
    }

    public $loading = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'rol_id' => ['except' => ''],
        'empresa_id' => ['except' => ''],
        'departamento_id' => ['except' => ''],
        'cargo_id' => ['except' => ''],
        'ubicacion_id' => ['except' => ''],
        'estado' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updating($field)
    {
        // reinicia siempre la paginación en la página 1
        if ($field !== 'perPage') {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'rol_id',
            'empresa_id',
            'departamento_id',
            'cargo_id',
            'ubicacion_id',
            'estado',
        ]);
    }

    public function getActiveFiltersCountProperty()
    {
        return collect([
            $this->search,
            $this->rol_id,
            $this->empresa_id,
            $this->departamento_id,
            $this->cargo_id,
            $this->ubicacion_id,
            $this->estado,
        ])->filter()->count();
    }

    public function getFilterParamsProperty()
    {
        return [
            'search' => $this->search,
            'rol_id' => $this->rol_id,
            'empresa_id' => $this->empresa_id,
            'departamento_id' => $this->departamento_id,
            'cargo_id' => $this->cargo_id,
            'ubicacion_id' => $this->ubicacion_id,
            'estado' => $this->estado,
        ];
    }

    public function render()
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $query = User::query()
            ->with(['roles', 'empresasAsignadas', 'departamento', 'cargo', 'ubicacion', 'empresaNomina'])
            ->where(function ($q) use ($actor) {

                // ADMIN → ve todo
                if ($actor->hasRole('Administrador')) {
                    return;
                }

                // USUARIO → solo se ve a sí mismo
                if ($actor->hasRole('Usuario')) {
                    $q->where('id', $actor->id);
                    return;
                }

                // ANALISTA → reglas de ubicación física
                if ($actor->hasRole('Analista')) {
                    $q->where(function ($sub) use ($actor) {
                        $sub
                            // misma ubicación física
                            ->where('ubicacion_id', $actor->empresa_activa_id)

                            // o foráneos (estado)
                            ->orWhereHas(
                                'ubicacion',
                                fn($u) =>
                                $u->where('es_estado', true)
                            );
                    });
                        // opcional pero recomendado: ocultar admins
                        // ->whereDoesntHave(
                        //     'roles',
                        //     fn($r) =>
                        //     $r->where('name', 'Administrador')
                        // );
                }
            });

        // filtros dinámicos
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%");
            });
        }

        if ($this->rol_id) {
            $query->whereHas('roles', fn($q) => $q->where('id', $this->rol_id));
        }

        if ($this->empresa_id) {
            $query->whereHas('empresaNomina', function ($q) {
                $q->where('empresas.id', $this->empresa_id);
            });
        }

        if ($this->departamento_id) {
            $query->where('departamento_id', $this->departamento_id);
        }

        if ($this->cargo_id) {
            $query->where('cargo_id', $this->cargo_id);
        }

        if ($this->ubicacion_id) {
            $query->where('ubicacion_id', $this->ubicacion_id);
        }

        if ($this->estado) {
            $query->where('estado', $this->estado);
        }

        return view('livewire.admin.usuarios-table', [
            'usuarios' => $query->paginate($this->perPage),
            'roles' => Role::pluck('name', 'id'),
            'empresas' => Empresa::pluck('nombre', 'id'),
            'departamentos' => Departamento::pluck('nombre', 'id'),
            'cargos' => Cargo::pluck('nombre', 'id'),
            'ubicaciones' => Ubicacion::pluck('nombre', 'id'),
        ]);
    }
}
