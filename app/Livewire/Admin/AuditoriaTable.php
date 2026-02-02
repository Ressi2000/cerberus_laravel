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

    // Variables de estado
    // public $selectedLog = null;      // Registro actual mostrado en el modal
    public $expandedLogId = null;    // Para fila expandible
    // public $showModal = false;       // Modal cerrado por defecto
    public $loading = false;         // Indicador de carga

    protected $paginationTheme = 'tailwind';

    public bool $isProfileView = false;

    // Filtros
    public $usuario_id = '';
    public $accion = '';
    public $tabla = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $perPage = 10;

    /**
     * Resetea la paginación al actualizar filtros
     */
    public function updated($property)
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    /**
     * Resetea todos los filtros
     */
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

    /**
     * Contador de filtros activos
     */
    public function getActiveFiltersCountProperty(): int
    {
        return collect([
            $this->usuario_id,
            $this->accion,
            $this->tabla,
            $this->fecha_desde,
            $this->fecha_hasta,
        ])->filter()->count();
    }

    /**
     * Array de filtros para pasar al view o export
     */
    public function getFilterParamsProperty(): array
    {
        return [
            'usuario_id'   => $this->usuario_id,
            'accion'       => $this->accion,
            'tabla'        => $this->tabla,
            'fecha_desde'  => $this->fecha_desde,
            'fecha_hasta'  => $this->fecha_hasta,
        ];
    }

    /**
     * Abre modal con el registro seleccionado
     */
    // public function openModal(int $logId): void
    // {
    //     $log = Auditoria::with('usuario')->find($logId);

    //     $this->selectedLog = [
    //         'id'      => $log->id,
    //         'usuario' => $log->usuario->name ?? 'Sistema',
    //         'tabla'   => $log->tabla,
    //         'accion'  => $log->accion,
    //         'fecha'   => \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s'),
    //         'cambios' => $log->cambios,
    //     ];

    //     $this->showModal = true;
    // }

    /**
     * Cierra modal
     */
    // public function closeModal(): void
    // {
    //     $this->selectedLog = null;
    //     $this->showModal = false;
    // }

    /**
     * Alterna la fila expandible
     */
    public function toggleDetails(int $logId): void
    {
        $this->expandedLogId = $this->expandedLogId === $logId ? null : $logId;
    }

    /**
     * Renderiza la vista
     */
    public function render()
    {
        $query = Auditoria::with('usuario')
            ->visiblePara(Auth::user())
            ->latest();

        if ($this->isProfileView) {
            $query->where('usuario_id', Auth::id());
        } elseif ($this->usuario_id) {
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
            'usuarios'   => User::pluck('name', 'id'),
            'acciones'   => Auditoria::select('accion')->distinct()->pluck('accion', 'accion'),
            'tablas'     => Auditoria::select('tabla')->distinct()->pluck('tabla', 'tabla'),
        ]);
    }
}
