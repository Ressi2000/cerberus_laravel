<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Departamento;
use App\Models\Cargo;
use App\Models\Empresa;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UsuarioController extends Controller
{
    use AuthorizesRequests;

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
        $ubicaciones = Ubicacion::pluck('nombre', 'id');

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
        $ubicaciones = Ubicacion::pluck('nombre', 'id');
        $jefes = User::pluck('name', 'id');
        return view('admin.usuarios.create', compact('roles', 'departamentos', 'cargos', 'empresas', 'ubicaciones', 'jefes'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'cedula' => 'required|string|max:20|unique:users,cedula',
            'ficha' => 'required|string|max:100|unique:users,ficha',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:users,email',

            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id' => 'nullable|exists:cargos,id',
            'jefe_id' => 'nullable|exists:users,id',

            'ubicacion_id' => 'required|exists:ubicaciones,id',
            'empresa_id' => 'required|exists:empresas,id',

            'rol_id' => 'required|exists:roles,id',

            'empresa_ids' => 'nullable|array',
            'empresa_ids.*' => 'exists:empresas,id',

            'password' => 'nullable|min:6|confirmed',
            'estado' => 'nullable|in:Activo,Inactivo',
            'foto' => 'nullable|image|max:5000',
        ]);

        $rol = Role::findOrFail($data['rol_id']);

        /** 🔐 REGLAS POR ROL DEL ACTOR **/
        if ($actor->hasRole('Analista')) {
            // Analista SOLO crea Usuarios
            if ($rol->name !== 'Usuario') {
                abort(403, 'No puedes crear usuarios con ese rol.');
            }

            // Estado fijo
            $data['estado'] = 'Activo';
        }

        if ($actor->hasRole('Usuario')) {
            abort(403);
        }

        /** FOTO **/
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('users', 'public');
        }

        /** PASSWORD **/
        $password = $data['password'] ?? '12345678';

        try {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($password),

                'empresa_id' => $data['empresa_id'],
                'empresa_activa_id' => $data['empresa_id'],

                'ubicacion_id' => $data['ubicacion_id'],
                'departamento_id' => $data['departamento_id'] ?? null,
                'cargo_id' => $data['cargo_id'] ?? null,
                'jefe_id' => $data['jefe_id'] ?? null,

                'estado' => $data['estado'] ?? 'Activo',
                'telefono' => $data['telefono'] ?? null,
                'foto' => $fotoPath,
                'ficha' => $data['ficha'],
                'cedula' => $data['cedula'],
            ]);

            $user->assignRole($rol);

            /** Empresas asignadas SOLO Analista **/
            if ($rol->name === 'Analista') {
                $user->empresasAsignadas()->sync($data['empresa_ids'] ?? []);
            }

            return redirect()
                ->route('admin.usuarios.index')
                ->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error creando usuario: ' . $e->getMessage());

            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }

            return back()->withInput()->with('error', 'Ocurrió un error al crear el usuario.');
        }
    }


    public function edit(User $usuario)
    {
        $this->authorize('update', $usuario);

        $roles = Role::pluck('name', 'id');
        $departamentos = Departamento::pluck('nombre', 'id');
        $cargos = Cargo::pluck('nombre', 'id');
        $empresas = Empresa::pluck('nombre', 'id');
        $ubicaciones = Ubicacion::pluck('nombre', 'id');
        $jefes = User::pluck('name', 'id');
        $userRoles = $usuario->roles->first()?->id;
        return view('admin.usuarios.edit', compact('usuario', 'roles', 'departamentos', 'cargos', 'userRoles', 'empresas', 'ubicaciones', 'jefes'));
    }

    public function update(Request $request, User $usuario)
    {
        $this->authorize('update', $usuario);

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($usuario->id)],
            'cedula' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($usuario->id)],
            'ficha' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($usuario->id)],
            'telefono' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($usuario->id)],

            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id' => 'nullable|exists:cargos,id',
            'jefe_id' => 'nullable|exists:users,id',

            'ubicacion_id' => 'required|exists:ubicaciones,id',
            'empresa_id' => 'required|exists:empresas,id',

            'rol_id' => 'required|exists:roles,id',

            'empresa_ids' => 'nullable|array',
            'empresa_ids.*' => 'exists:empresas,id',

            'password' => 'nullable|min:6|confirmed',
            'estado' => 'nullable|in:Activo,Inactivo',
            'foto' => 'nullable|image|max:2048',
        ]);

        $rol = Role::findOrFail($data['rol_id']);

        /** 🔐 RESTRICCIONES **/
        if ($actor->hasRole('Analista')) {
            // NO puede cambiar rol ni estado
            unset($data['rol_id'], $data['estado']);

            // Solo puede editar Usuarios
            if (! $usuario->hasRole('Usuario')) {
                abort(403);
            }
        }

        if ($actor->hasRole('Usuario')) {
            abort(403);
        }

        try {
            /** FOTO **/
            if ($request->hasFile('foto')) {
                if ($usuario->foto && Storage::disk('public')->exists($usuario->foto)) {
                    Storage::disk('public')->delete($usuario->foto);
                }

                $data['foto'] = $request->file('foto')->store('users', 'public');
            } else {
                unset($data['foto']);
            }

            /** PASSWORD **/
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $usuario->update($data);

            /** ROL **/
            if ($actor->hasRole('Administrador')) {
                $usuario->syncRoles([$rol]);
            }

            /** EMPRESAS ASIGNADAS **/
            if ($rol->name === 'Analista') {
                $usuario->empresasAsignadas()->sync($data['empresa_ids'] ?? []);
            } else {
                $usuario->empresasAsignadas()->detach();
            }

            /** EMPRESA ACTIVA **/
            if ($usuario->empresa_id !== $data['empresa_id']) {
                $usuario->update(['empresa_activa_id' => $data['empresa_id']]);
            }

            return redirect()
                ->route('admin.usuarios.index')
                ->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error actualizando usuario: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Ocurrió un error al actualizar el usuario.');
        }
    }


    public function destroy(User $usuario)
    {
        $this->authorize('delete', $usuario);

        try {
            $usuario->delete();
            return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado.');
        } catch (\Exception $e) {
            Log::error('Error eliminando usuario: ' . $e->getMessage());
            return redirect()->route('admin.usuarios.index')->with('error', 'Ocurrió un error al eliminar el usuario.');
        }
    }

    public function show(User $usuario)
    {
        return redirect()->route('admin.usuarios.edit', $usuario);
    }
}
