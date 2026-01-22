<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditoriaTable extends Component
{
    use WithPagination;

    public int $page = 1;

    #[Url] public $usuario_id = '';
    #[Url] public $accion = '';
    #[Url] public $tabla = '';
    #[Url] public $fecha_desde = '';
    #[Url] public $fecha_hasta = '';

    #[Url] public $perPage = 10;

    public function loadData()
    {
        // incluso si está vacío
    }

    public $loading = false;

    protected $queryString = [
        'page' => ['except' => 1],
        'usuario_id' => ['except' => ''],
        'accion' => ['except' => ''],
        'tabla' => ['except' => ''],
        'fecha_desde' => ['except' => ''],
        'fecha_hasta' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updating($field)
    {
        if (!in_array($field, ['page', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->reset([
            'usuario_id',
            'accion',
            'tabla',
            'fecha_desde',
            'fecha_hasta',
        ]);
    }

    public function getActiveFiltersCountProperty()
    {
        return collect([
            $this->usuario_id,
            $this->accion,
            $this->tabla,
            $this->fecha_desde,
            $this->fecha_hasta,
        ])->filter()->count();
    }

    public function getFilterParamsProperty()
    {
        return [
            'usuario_id' => $this->usuario_id,
            'accion' => $this->accion,
            'tabla' => $this->tabla,
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
        ];
    }

    public function render()
    {
        $query = Auditoria::with('usuario')
            ->visiblePara(Auth::user())
            ->latest();


        if ($this->usuario_id) {
            $query->where('usuario_id', $this->usuario_id);
        }

        if ($this->accion) {
            $query->where('accion', $this->accion);
        }

        if ($this->tabla) {
            $query->where('tabla', $this->tabla);
        }

        if ($this->fecha_desde) {
            $query->whereDate('created_at', '>=', $this->fecha_desde);
        }

        if ($this->fecha_hasta) {
            $query->whereDate('created_at', '<=', $this->fecha_hasta);
        }

        return view('livewire.admin.auditoria-table', [
            'auditorias' => $query->paginate($this->perPage),
            'usuarios' => User::pluck('name', 'id'),
            'acciones' => Auditoria::select('accion')->distinct()->pluck('accion', 'accion'),
            'tablas' => Auditoria::select('tabla')->distinct()->pluck('tabla', 'tabla'),
        ]);
    }
}
