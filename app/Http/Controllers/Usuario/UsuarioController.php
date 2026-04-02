<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    use AuthorizesRequests;

    /**
     * Listado — stats para las cards, filtros para los selects.
     * La tabla reactiva la maneja UsuariosTable (Livewire).
     */
    public function index()
    {
        return view('usuarios.index', [
            'usuariosActivos'   => User::where('estado', 'Activo')->count(),
            'usuariosInactivos' => User::where('estado', 'Inactivo')->count(),
            'admins'            => User::role('Administrador')->count(),
            'analistas'         => User::role('Analista')->count(),
        ]);
    }

    /**
     * Formulario de creación — ahora es Livewire.
     */
    public function create()
    {
        return view('usuarios.usuarios-create');
    }

    /**
     * Formulario de edición — ahora es Livewire.
     */
    public function edit(User $usuario)
    {
        $this->authorize('update', $usuario);

        return view('usuarios.usuarios-edit', compact('usuario'));
    }

    /**
     * show() redirige a edit.
     */
    public function show(User $usuario)
    {
        return redirect()->route('usuarios.usuarios-edit', $usuario);
    }

    /**
     * Inactivar usuario (soft delete lógico).
     * El store() y update() ya no existen aquí — los maneja Livewire.
     */
    public function destroy(User $usuario)
    {
        $this->authorize('delete', $usuario);

        try {
            $usuario->update(['estado' => 'Inactivo']);
            return redirect()
                ->route('usuarios.index')
                ->with('success', 'Usuario inactivado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error inactivando usuario: ' . $e->getMessage());
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'Ocurrió un error al inactivar el usuario.');
        }
    }
}
