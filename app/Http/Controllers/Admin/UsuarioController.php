<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Departamento;
use App\Models\Cargo;
use App\Models\Empresa;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        // Filtros dinámicos (los implementamos en la siguiente sección)
        $query = User::with('roles','departamento','cargo','empresa','ubicacion');

        /** FILTRO BÚSQUEDA */
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('username', 'LIKE', "%{$request->search}%")
                ->orWhere('ficha', 'like', "%{$request->search}%");
            });
        }

        /** FILTROS AVANZADOS */
        if ($request->filled('rol_id')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('id', $request->rol_id);
            });
        }

        if ($request->filled('cargo_id')) {
            $query->where('cargo_id', $request->cargo_id);
        }

        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->departamento_id);
        }

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('ubicacion_id')) {
            $query->where('ubicacion_id', $request->ubicacion_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        /** Obtener usuarios paginados */
        $perPage = $request->get('per_page', 10);
        $usuarios = $query->paginate($perPage)->appends($request->query());

        /** Estadísticas */
        $usuariosActivos = User::where('estado', 'Activo')->count();
        $usuariosInactivos = User::where('estado', 'Inactivo')->count();
        $admins = User::role('Administrador')->count();

        /** Filtros para Selects */
        $roles = Role::pluck('name', 'id');
        $cargos = Cargo::pluck('nombre', 'id');
        $departamentos = Departamento::pluck('nombre', 'id');
        $empresas = Empresa::pluck('nombre', 'id');
        $ubicaciones = Ubicacion::pluck('nombre', 'id');

        return view('admin.usuarios.index', compact(
            'usuarios',
            'usuariosActivos',
            'usuariosInactivos',
            'admins',
            'roles','cargos','departamentos','empresas','ubicaciones'
        ));
    }

    public function create()
    {
        $roles = Role::pluck('name', 'id');
        $departamentos = Departamento::pluck('nombre', 'id');
        $cargos = Cargo::pluck('nombre', 'id');
        $empresas = Empresa::pluck('nombre', 'id');
        $ubicaciones = Ubicacion::pluck('nombre', 'id');
        $jefes = User::pluck('name', 'id');
        return view('admin.usuarios.create', compact('roles','departamentos','cargos', 'empresas', 'ubicaciones', 'jefes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'cedula' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'ficha' => 'nullable|string|max:100',

            'empresa_id' => 'nullable|exists:empresas,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id' => 'nullable|exists:cargos,id',
            'ubicacion_id' => 'nullable|exists:ubicaciones,id',
            'jefe_id' => 'nullable|exists:users,id',

            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',

            'password' => 'required|min:6|confirmed',
            'estado' => 'nullable|boolean',
            'foto' => 'nullable|image|max:2048',
        ]);

        // handle foto upload if present
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('fotos', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'empresa_id' => $data['empresa_id'] ?? null,
            'departamento_id' => $data['departamento_id'] ?? null,
            'cargo_id' => $data['cargo_id'] ?? null,
            'ubicacion_id' => $data['ubicacion_id'] ?? null,
            'jefe_id' => $data['jefe_id'] ?? null,
            'estado' => $data['estado'] ?? true,
            'telefono' => $data['telefono'] ?? null,
            'foto' => $fotoPath,
            'ficha' => $data['ficha'] ?? null,
            'cedula' => $data['cedula'] ?? null,
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return redirect()->route('admin.usuarios.index')->with('success','Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        $roles = Role::pluck('name', 'id');
        $departamentos = Departamento::pluck('nombre', 'id');
        $cargos = Cargo::pluck('nombre', 'id');
        $empresas = Empresa::pluck('nombre', 'id');
        $ubicaciones = Ubicacion::pluck('nombre', 'id');
        $jefes = User::pluck('name', 'id');
        $userRoles = $usuario->roles->pluck('name')->toArray();
        return view('admin.usuarios.edit', compact('usuario','roles','departamentos','cargos','userRoles', 'empresas', 'ubicaciones', 'jefes'));
    }

    public function update(Request $request, User $usuario)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => [
                'required','string','max:255',
                Rule::unique('users','username')->ignore($usuario->id)
            ],
            'cedula' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => [
                'required','email',
                Rule::unique('users','email')->ignore($usuario->id)
            ],
            'ficha' => 'nullable|string|max:100',

            'empresa_id' => 'nullable|exists:empresas,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id' => 'nullable|exists:cargos,id',
            'ubicacion_id' => 'nullable|exists:ubicaciones,id',
            'jefe_id' => 'nullable|exists:users,id',

            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',

            'password' => 'nullable|min:6|confirmed',
            'estado' => 'nullable|boolean',
            'foto' => 'nullable|image|max:2048',
        ]);

        $usuario->name = $data['name'];
        $usuario->username = $data['username'];
        $usuario->email = $data['email'];
        $usuario->empresa_id = $data['empresa_id'] ?? null;
        $usuario->departamento_id = $data['departamento_id'] ?? null;
        $usuario->cargo_id = $data['cargo_id'] ?? null;
        $usuario->ubicacion_id = $data['ubicacion_id'] ?? null;
        $usuario->jefe_id = $data['jefe_id'] ?? null;
        $usuario->telefono = $data['telefono'] ?? null;
        $usuario->ficha = $data['ficha'] ?? null;
        $usuario->cedula = $data['cedula'] ?? null;
        if (isset($data['estado'])) {
            $usuario->estado = (bool)$data['estado'];
        }

        // handle foto upload if present
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('fotos', 'public');
            $usuario->foto = $fotoPath;
        }

        if (!empty($data['password'])) {
            $usuario->password = Hash::make($data['password']);
        }

        $usuario->save();
        $usuario->syncRoles($data['roles'] ?? []);

        return redirect()->route('admin.usuarios.index')->with('success','Usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        $usuario->delete();
        return redirect()->route('admin.usuarios.index')->with('success','Usuario eliminado.');
    }

    public function show(User $usuario)
    {
        return redirect()->route('admin.usuarios.edit', $usuario);
    }
}
