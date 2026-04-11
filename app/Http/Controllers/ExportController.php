<?php

namespace App\Http\Controllers;

use App\Exports\AtributosExport;
use App\Exports\CategoriasExport;
use App\Exports\CargosExport;
use App\Exports\DepartamentosExport;
use App\Exports\EmpresasExport;
use App\Exports\EquiposExport;
use App\Exports\EstadosExport;
use App\Exports\UbicacionesExport;
use App\Exports\UsuariosExport;
use App\Exports\AuditoriaExport;
use App\Models\AtributoEquipo;
use App\Models\Auditoria;
use App\Models\CategoriaEquipo;
use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
class ExportController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Usuarios
    // ─────────────────────────────────────────────────────────────────────────
    public function usuarios(Request $request)
    {
        $format = $request->input('format', 'xlsx');

        // Eager load de todas las relaciones necesarias para la exportación
        $query = User::with([
            'empresaNomina',
            'empresaActiva',
            'empresasAsignadas',
            'departamento',
            'cargo',
            'ubicacion',
            'jefe',
            'roles',
        ])->visiblePara(Auth::user());

        // ── Filtros (los mismos que usa UsuariosTable Livewire) ──────────────
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name',     'like', "%{$search}%")
                    ->orWhere('email',    'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('cedula',   'like', "%{$search}%");
            });
        }

        if ($request->rol_id) {
            $query->whereHas('roles', fn($q) => $q->where('id', $request->rol_id));
        }

        if ($request->empresa_id) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->departamento_id) {
            $query->where('departamento_id', $request->departamento_id);
        }

        if ($request->cargo_id) {
            $query->where('cargo_id', $request->cargo_id);
        }

        if ($request->ubicacion_id) {
            $query->where('ubicacion_id', $request->ubicacion_id);
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        // Nuevos filtros
        if ($request->fecha_desde) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->fecha_hasta) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        if ($request->jefe_id) {
            $query->where('jefe_id', $request->jefe_id);
        }

        if ($request->foraneo) {
            $query->whereHas('ubicacion', fn($q) => $q->where('es_estado', true));
        }

        $filename = 'usuarios_cerberus_' . now()->format('Ymd_His') . '.' . $format;

        return Excel::download(new UsuariosExport($query), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Equipos
    // ─────────────────────────────────────────────────────────────────────────
    public function equipos(Request $request)
    {
        $format = $request->input('format', 'xlsx');

        // Eager load para evitar N+1 queries
        $query = Equipo::with([
            'categoria',
            'estado',
            'empresa',
            'ubicacion',
            'creadoPor',
            'atributosActuales.atributo',
        ])->visiblePara(Auth::user());

        // ── Filtros (los mismos que usa EquiposTable Livewire) ───────────────
        if ($request->search) {
            $query->where('codigo_interno', 'like', "%{$request->search}%");
        }

        if ($request->serial) {
            $query->where('serial', 'like', "%{$request->serial}%");
        }

        if ($request->categoria_id) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->estado_id) {
            $query->where('estado_id', $request->estado_id);
        }

        if ($request->ubicacion_id) {
            $query->where('ubicacion_id', $request->ubicacion_id);
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->fecha_adq_desde) {
            $query->whereDate('fecha_adquisicion', '>=', $request->fecha_adq_desde);
        }

        if ($request->fecha_adq_hasta) {
            $query->whereDate('fecha_adquisicion', '<=', $request->fecha_adq_hasta);
        }

        if ($request->garantia_vencida) {
            $query->where('fecha_garantia_fin', '<', now()->toDateString());
        }

        // Filtros EAV dinámicos
        if ($request->filtros && is_array($request->filtros)) {
            foreach ($request->filtros as $atributoId => $valor) {
                if ($valor === null || $valor === '') continue;

                $query->whereExists(function ($sub) use ($atributoId, $valor) {
                    $sub->selectRaw(1)
                        ->from('equipo_atributo_valores as eav')
                        ->whereColumn('eav.equipo_id', 'equipos.id')
                        ->where('eav.atributo_id', $atributoId)
                        ->where('eav.es_actual', true)
                        ->where('eav.valor', 'like', "%{$valor}%");
                });
            }
        }

        $filename = 'equipos_cerberus_' . now()->format('Ymd_His') . '.' . $format;

        return Excel::download(new EquiposExport($query), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Auditoría
    // ─────────────────────────────────────────────────────────────────────────
    public function auditoria(Request $request)
    {
        $format = $request->input('format', 'xlsx');

        $query = Auditoria::with('usuario');

        if ($request->usuario_id) {
            $query->where('usuario_id', $request->usuario_id);
        }
        if ($request->accion) {
            $query->where('accion', $request->accion);
        }
        if ($request->tabla) {
            $query->where('tabla', $request->tabla);
        }
        if ($request->fecha_desde) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->fecha_hasta) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $filename = 'auditoria_cerberus_' . now()->format('Ymd_His') . '.' . $format;

        return Excel::download(new AuditoriaExport($query), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Categorías  (solo Administrador — ver rutas)
    // ─────────────────────────────────────────────────────────────────────────
    public function categorias(Request $request)
    {
        $format = $request->input('format', 'xlsx');

        $query = CategoriaEquipo::withCount(['atributos', 'equipos'])
            ->orderBy('nombre');

        if ($request->search) {
            $query->where(
                fn($q) =>
                $q->where('nombre',      'like', "%{$request->search}%")
                    ->orWhere('descripcion', 'like', "%{$request->search}%")
            );
        }

        if ($request->asignable !== null && $request->asignable !== '') {
            $query->where('asignable', (bool) $request->asignable);
        }

        $filename = 'categorias_cerberus_' . now()->format('Ymd_His') . '.' . $format;
        return Excel::download(new CategoriasExport($query), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Estados  (solo Administrador)
    // ─────────────────────────────────────────────────────────────────────────
    public function estados(Request $request)
    {
        $format = $request->input('format', 'xlsx');

        $query = EstadoEquipo::withCount('equipos')
            ->orderBy('nombre');

        if ($request->search) {
            $query->where('nombre', 'like', "%{$request->search}%");
        }

        $filename = 'estados_cerberus_' . now()->format('Ymd_His') . '.' . $format;
        return Excel::download(new EstadosExport($query), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ubicaciones  (solo Administrador)
    // ─────────────────────────────────────────────────────────────────────────
    public function ubicaciones(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $query  = Ubicacion::with('empresa')
            ->withCount([
                'usuarios' => fn($q) => $q->where('estado', 'Activo'),
                'equipos'  => fn($q) => $q->where('activo', true),
            ])
            ->orderBy('nombre');

        if ($request->search) {
            $query->where(
                fn($q) =>
                $q->where('nombre', 'like', "%{$request->search}%")
                    ->orWhere('descripcion', 'like', "%{$request->search}%")
            );
        }
        if ($request->empresa_id) $query->where('empresa_id', $request->empresa_id);
        if ($request->es_estado !== null && $request->es_estado !== '') {
            $query->where('es_estado', (bool) $request->es_estado);
        }

        return Excel::download(
            new UbicacionesExport($query),
            'ubicaciones_cerberus_' . now()->format('Ymd_His') . '.' . $format
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cargos  (solo Administrador)
    // ─────────────────────────────────────────────────────────────────────────
    public function cargos(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $query  = Cargo::with(['empresa', 'departamento'])
            ->withCount(['usuarios' => fn($q) => $q->where('estado', 'Activo')])
            ->orderBy('nombre');

        if ($request->search) {
            $query->where('nombre', 'like', "%{$request->search}%");
        }
        if ($request->empresa_id)      $query->where('empresa_id', $request->empresa_id);
        if ($request->departamento_id) $query->where('departamento_id', $request->departamento_id);

        return Excel::download(
            new CargosExport($query),
            'cargos_cerberus_' . now()->format('Ymd_His') . '.' . $format
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Departamentos  (solo Administrador)
    // ─────────────────────────────────────────────────────────────────────────
    public function departamentos(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $query  = Departamento::with('empresa')
            ->withCount([
                'cargos'   => fn($q) => $q->where('activo', true),
                'usuarios' => fn($q) => $q->where('estado', 'Activo'),
            ])
            ->orderBy('nombre');

        if ($request->search) {
            $query->where(
                fn($q) =>
                $q->where('nombre', 'like', "%{$request->search}%")
                    ->orWhere('descripcion', 'like', "%{$request->search}%")
            );
        }
        if ($request->empresa_id) $query->where('empresa_id', $request->empresa_id);
        if ($request->tipo === 'global')  $query->whereNull('empresa_id');
        if ($request->tipo === 'empresa') $query->whereNotNull('empresa_id');

        return Excel::download(
            new DepartamentosExport($query),
            'departamentos_cerberus_' . now()->format('Ymd_His') . '.' . $format
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Empresas  (solo Administrador)
    // ─────────────────────────────────────────────────────────────────────────
    public function empresas(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $query  = Empresa::withCount([
            'usuarios'    => fn($q) => $q->where('estado', 'Activo'),
            'equipos'     => fn($q) => $q->where('activo', true),
            'ubicaciones' => fn($q) => $q->where('activo', true),
        ])->orderBy('nombre');

        if ($request->search) {
            $query->where(
                fn($q) =>
                $q->where('nombre', 'like', "%{$request->search}%")
                    ->orWhere('rif', 'like', "%{$request->search}%")
            );
        }

        return Excel::download(
            new EmpresasExport($query),
            'empresas_cerberus_' . now()->format('Ymd_His') . '.' . $format
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Atributos  (solo Administrador)
    // ─────────────────────────────────────────────────────────────────────────
    public function atributos(Request $request)
    {
        $format = $request->input('format', 'xlsx');

        $query = AtributoEquipo::with('categoria')
            ->withCount('valores')
            ->orderBy('categoria_id')
            ->orderBy('orden');

        if ($request->search) {
            $query->where('nombre', 'like', "%{$request->search}%");
        }

        if ($request->categoria_id) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        $filename = 'atributos_cerberus_' . now()->format('Ymd_His') . '.' . $format;
        return Excel::download(new AtributosExport($query), $filename);
    }
}
