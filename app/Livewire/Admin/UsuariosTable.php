<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Departamento;
use App\Models\Cargo;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;

/**
 * UsuariosTable — Tabla reactiva de usuarios con filtros avanzados
 *
 * Filtros disponibles:
 *  - Búsqueda libre (nombre, email, username, cédula)
 *  - Empresa de nómina
 *  - Rol
 *  - Departamento
 *  - Cargo
 *  - Ubicación
 *  - Estado (Activo / Inactivo)
 *  - Foráneo (ubicación con es_estado = true)
 *  - Jefe directo
 *  - Rango de fecha de creación (desde / hasta)
 *
 * Visibilidad:
 *  - Administrador → ve todos los usuarios
 *  - Analista       → ve usuarios de su ubicación física + foráneos
 *  - Usuario        → solo se ve a sí mismo
 */
class UsuariosTable extends Component
{
    use WithPagination;

    // #[Url] sincroniza el filtro con la URL, útil para compartir/recargar
    #[Url(as: 'q')]
    public string $search = '';

    // Filtros principales
    public string $rol_id         = '';
    public string $empresa_id     = '';
    public string $departamento_id = '';
    public string $cargo_id       = '';
    public string $ubicacion_id   = '';
    public string $estado         = '';

    // Filtros adicionales
    public string $jefe_id       = '';   // Jefe directo
    public string $foraneo       = '';   // '1' = solo foráneos, '' = todos
    public string $fecha_desde   = '';   // Fecha creación desde
    public string $fecha_hasta   = '';   // Fecha creación hasta

    // Paginación
    public int $perPage = 10;

    // ─────────────────────────────────────────────────────────────────────────
    // Resetear paginación al cambiar cualquier filtro
    // ─────────────────────────────────────────────────────────────────────────
    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Limpiar todos los filtros
    // ─────────────────────────────────────────────────────────────────────────
    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'rol_id',
            'empresa_id',
            'departamento_id',
            'cargo_id',
            'ubicacion_id',
            'estado',
            'jefe_id',
            'foraneo',
            'fecha_desde',
            'fecha_hasta',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Conteo de filtros activos (para el badge visual)
    // ─────────────────────────────────────────────────────────────────────────
    public function getActiveFiltersCountProperty(): int
    {
        return collect([
            $this->search,
            $this->rol_id,
            $this->empresa_id,
            $this->departamento_id,
            $this->cargo_id,
            $this->ubicacion_id,
            $this->estado,
            $this->jefe_id,
            $this->foraneo,
            $this->fecha_desde,
            $this->fecha_hasta,
        ])->filter()->count();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Parámetros de filtros para pasarlos al botón de exportación
    // ─────────────────────────────────────────────────────────────────────────
    public function getFilterParamsProperty(): array
    {
        return [
            'search'          => $this->search,
            'rol_id'          => $this->rol_id,
            'empresa_id'      => $this->empresa_id,
            'departamento_id' => $this->departamento_id,
            'cargo_id'        => $this->cargo_id,
            'ubicacion_id'    => $this->ubicacion_id,
            'estado'          => $this->estado,
            'jefe_id'         => $this->jefe_id,
            'foraneo'         => $this->foraneo,
            'fecha_desde'     => $this->fecha_desde,
            'fecha_hasta'     => $this->fecha_hasta,
        ];
    }

    #[Computed()]
    public function ubicaciones()
    {
        $user = Auth::user();

        if ($user->hasRole('Administrador')) {
            return Ubicacion::orderBy('nombre')->pluck('nombre', 'id');
        }

        // Analista: solo la ubicación de su empresa activa + foráneos
        return Ubicacion::where(function ($q) use ($user) {
            $q->where('empresa_id', $user->empresa_activa_id)
                ->orWhere('es_estado', true);
        })
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render: construir query y pasar datos a la vista
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        /** @var User $actor */
        $actor = Auth::user();

        // ── Query base con eager loading ──────────────────────────────────────
        $query = User::query()
            ->with([
                'roles',
                'empresaNomina',
                'departamento',
                'cargo',
                'ubicacion',
            ])
            // ── Reglas de visibilidad según rol del actor ─────────────────────
            ->where(function ($q) use ($actor) {

                if ($actor->hasRole('Administrador')) {
                    // Admin ve absolutamente todo
                    return;
                }

                if ($actor->hasRole('Usuario')) {
                    // Usuario normal solo se ve a sí mismo
                    $q->where('id', $actor->id);
                    return;
                }

                if ($actor->hasRole('Analista')) {
                    // Analista ve:
                    //  1. Usuarios en la misma ubicación física que su empresa activa
                    //  2. Usuarios foráneos (ubicacion.es_estado = true)
                    $q->where(function ($sub) use ($actor) {
                        $sub->where('ubicacion_id', $actor->empresa_activa_id)
                            ->orWhereHas('ubicacion',
                                fn($u) => $u->where('es_estado', true)
                            );
                    });
                }
            });

        // ── Filtros aplicados por el usuario ──────────────────────────────────

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name',     'like', "%{$s}%")
                  ->orWhere('email',    'like', "%{$s}%")
                  ->orWhere('username', 'like', "%{$s}%")
                  ->orWhere('cedula',   'like', "%{$s}%");
            });
        }

        if ($this->rol_id) {
            $query->whereHas('roles',
                fn($q) => $q->where('id', $this->rol_id)
            );
        }

        if ($this->empresa_id) {
            $query->where('empresa_id', $this->empresa_id);
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

        // Filtro: jefe directo
        if ($this->jefe_id) {
            $query->where('jefe_id', $this->jefe_id);
        }

        // Filtro: solo foráneos (ubicacion.es_estado = true)
        if ($this->foraneo === '1') {
            $query->whereHas('ubicacion',
                fn($q) => $q->where('es_estado', true)
            );
        }

        // Filtros de fecha de creación
        if ($this->fecha_desde) {
            $query->whereDate('created_at', '>=', $this->fecha_desde);
        }

        if ($this->fecha_hasta) {
            $query->whereDate('created_at', '<=', $this->fecha_hasta);
        }

        return view('livewire.admin.usuarios-table', [
            'usuarios'    => $query->paginate($this->perPage),

            // Datos para los selects de filtros
            'roles'       => Role::pluck('name', 'id'),
            'empresas'    => Empresa::pluck('nombre', 'id'),
            'departamentos' => Departamento::pluck('nombre', 'id'),
            'cargos'      => Cargo::pluck('nombre', 'id'),
            'ubicaciones' => $this->ubicaciones,

            // Jefes disponibles para el filtro de jefe directo
            'jefesDisponibles' => User::seleccionables()->pluck('name', 'id'),
        ]);
    }
}