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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        // Estadísticas rápidas
        $usuariosActivos = User::where('estado', 'Activo')->count();
        $usuariosInactivos = User::where('estado', 'Inactivo')->count();
        $admins = User::role('Administrador')->count();
        $analistas = User::role('Analista')->count();

        // Filtros para selects (cachea para rendimiento)
        $roles = Role::pluck('name', 'id');
        $cargos = Cargo::pluck('nombre', 'id');
        $departamentos = Departamento::pluck('nombre', 'id');
        $empresas = Empresa::pluck('nombre', 'id');
        $ubicaciones = Empresa::pluck('nombre', 'id');

        // Livewire se encarga del filtrado real
        return view('admin.usuarios.index', compact(
            'usuariosActivos',
            'usuariosInactivos',
            'admins',
            'analistas',
            'roles',
            'cargos',
            'departamentos',
            'empresas',
            'ubicaciones'
        ));
    }

    public function create()
    {
        $roles = Role::pluck('name', 'id');
        $departamentos = Departamento::pluck('nombre', 'id');
        $cargos = Cargo::pluck('nombre', 'id');
        $empresas = Empresa::pluck('nombre', 'id');
        $ubicaciones = Empresa::pluck('nombre', 'id');
        $jefes = User::pluck('name', 'id');
        return view('admin.usuarios.create', compact('roles', 'departamentos', 'cargos', 'empresas', 'ubicaciones', 'jefes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'cedula' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:users,email',
            'ficha' => 'nullable|string|max:100',
            'empresa_id' => 'nullable|exists:empresas,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id' => 'nullable|exists:cargos,id',
            'ubicacion_id' => 'nullable|exists:empresas,id',
            'jefe_id' => 'nullable|exists:users,id',

            // Un único rol
            'rol_id' => 'nullable|exists:roles,id',

            'password' => 'required|min:6|confirmed',

            // viene como string en tu formulario
            'estado' => 'nullable|string|in:Activo,Inactivo',

            // imagen
            'foto' => 'nullable|image|max:2048',
        ]);


        // handle foto upload if present
        $fotoPath = null;

        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')
                ->store('users', 'public');
        }


        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'empresa_id' => $data['empresa_id'] ?? null,
            'departamento_id' => $data['departamento_id'] ?? null,
            'cargo_id' => $data['cargo_id'] ?? null,
            'ubicacion_id' => $data['ubicacion_id'] ?? null,
            'jefe_id' => $data['jefe_id'] ?? null,
            'estado' => $data['estado'] ?? 'Activo',
            'telefono' => $data['telefono'] ?? null,
            'foto' => $fotoPath,
            'ficha' => $data['ficha'] ?? null,
            'cedula' => $data['cedula'] ?? null,
        ]);

        if ($request->filled('rol_id')) {
            $role = Role::findOrFail($request->rol_id);
            $user->assignRole($role);
        }


        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        $roles = Role::pluck('name', 'id');
        $departamentos = Departamento::pluck('nombre', 'id');
        $cargos = Cargo::pluck('nombre', 'id');
        $empresas = Empresa::pluck('nombre', 'id');
        $ubicaciones = Empresa::pluck('nombre', 'id');
        $jefes = User::pluck('name', 'id');
        $userRoles = $usuario->roles->first()?->id;
        return view('admin.usuarios.edit', compact('usuario', 'roles', 'departamentos', 'cargos', 'userRoles', 'empresas', 'ubicaciones', 'jefes'));
    }

    public function update(Request $request, User $usuario)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($usuario->id)],
            'cedula' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($usuario->id)],
            'ficha' => 'nullable|string|max:100',
            'empresa_id' => 'nullable|exists:empresas,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id' => 'nullable|exists:cargos,id',
            'ubicacion_id' => 'nullable|exists:empresas,id',
            'jefe_id' => 'nullable|exists:users,id',
            'rol_id' => 'nullable|exists:roles,id',
            'password' => 'nullable|min:6|confirmed',
            'estado' => 'nullable|string|in:Activo,Inactivo',
            'foto' => 'nullable|image|max:2048',
        ]);

        // FOTO
        if ($request->hasFile('foto')) {
            if ($usuario->foto && Storage::disk('public')->exists($usuario->foto)) {
                Storage::disk('public')->delete($usuario->foto);
            }

            $data['foto'] = $request->file('foto')->store('users', 'public');
        } else {
            unset($data['foto']); // ← CLAVE
        }


        // PASSWORD
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']); // evita sobreescribir con null
        }
        
        $usuario->update($data);

        // ROL - convertir ID a objeto Role
        if (!empty($data['rol_id'])) {
            $role = Role::find($data['rol_id']);
            $usuario->syncRoles([$role]);
        }

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        $usuario->delete();
        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado.');
    }

    public function show(User $usuario)
    {
        return redirect()->route('admin.usuarios.edit', $usuario);
    }
}
