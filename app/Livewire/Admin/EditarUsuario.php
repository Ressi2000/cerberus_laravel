<?php

namespace App\Livewire\Admin;

use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

class EditarUsuario extends Component
{
    use WithFileUploads;

    // El modelo se guarda como ID para evitar problemas de serialización de Livewire
    public int $usuarioId;
    public ?User $usuario = null;

    // ── Datos personales ──────────────────────────────────────────────────────
    public string $name            = '';
    public string $username        = '';
    public string $cedula          = '';
    public string $ficha           = '';
    public string $telefono        = '';
    public string $email           = '';
    public $foto                   = null;
    public ?string $fotoActual     = null;

    // ── Datos laborales ───────────────────────────────────────────────────────
    public string $empresa_id      = '';
    public string $departamento_id = '';
    public string $cargo_id        = '';
    public string $ubicacion_id    = '';
    public string $jefe_id         = '';

    // ── Acceso ────────────────────────────────────────────────────────────────
    public string $rol_id          = '';
    public string $estado          = 'Activo';
    public string $password        = '';
    public string $password_confirmation = '';

    // ── Empresas asignadas (solo Analistas) ───────────────────────────────────
    // ¡NO se resetea al cambiar empresa_id! Solo depende del rol.
    public array $empresa_ids = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Mount: recibir ID, buscar el usuario internamente
    // ─────────────────────────────────────────────────────────────────────────
    public function mount(int $usuarioId): void
    {
        $this->usuarioId = $usuarioId;
        $this->usuario = User::with(['roles', 'empresasAsignadas'])->findOrFail($usuarioId);

        $this->authorize('update', $this->usuario);

        // Hidratar todos los campos
        $this->name            = $this->usuario->name;
        $this->username        = $this->usuario->username;
        $this->cedula          = $this->usuario->cedula ?? '';
        $this->ficha           = $this->usuario->ficha ?? '';
        $this->telefono        = $this->usuario->telefono ?? '';
        $this->email           = $this->usuario->email ?? '';
        $this->fotoActual      = $this->usuario->foto;

        $this->empresa_id      = (string) ($this->usuario->empresa_id ?? '');
        $this->departamento_id = (string) ($this->usuario->departamento_id ?? '');
        $this->cargo_id        = (string) ($this->usuario->cargo_id ?? '');
        $this->ubicacion_id    = (string) ($this->usuario->ubicacion_id ?? '');
        $this->jefe_id         = (string) ($this->usuario->jefe_id ?? '');

        $this->rol_id          = (string) ($this->usuario->roles->first()?->id ?? '');
        $this->estado          = $this->usuario->estado;

        // Empresas asignadas — se carga UNA SOLA VEZ en mount, NO se toca al cambiar empresa_id
        $this->empresa_ids = $this->usuario->empresasAsignadas
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cascada empresa → departamento → cargo
    // IMPORTANTE: empresa_ids NO se toca aquí
    // ─────────────────────────────────────────────────────────────────────────
    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
        $this->cargo_id        = '';
        // ❌ NO limpiar empresa_ids aquí — son independientes de la empresa nómina
    }

    public function updatedDepartamentoId(): void
    {
        $this->cargo_id = '';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed properties
    // ─────────────────────────────────────────────────────────────────────────
    #[Computed]
    public function empresas()
    {
        return Empresa::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function departamentos()
    {
        return Departamento::where(function ($q) {
            $q->whereNull('empresa_id');
            if ($this->empresa_id) {
                $q->orWhere('empresa_id', $this->empresa_id);
            }
        })
        ->orderBy('nombre')
        ->pluck('nombre', 'id');
    }

    #[Computed]
    public function cargos()
    {
        if (! $this->departamento_id) return collect();

        return Cargo::where('departamento_id', $this->departamento_id)
            ->where(function ($q) {
                $q->whereNull('empresa_id');
                if ($this->empresa_id) {
                    $q->orWhere('empresa_id', $this->empresa_id);
                }
            })
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    #[Computed]
    public function ubicaciones()
    {
        return Ubicacion::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function jefes()
    {
        return User::seleccionables()
            ->where('id', '!=', $this->usuarioId)
            ->pluck('name', 'id');
    }

    #[Computed]
    public function roles()
    {
        if (Auth::user()->hasRole('Analista')) {
            return Role::where('name', 'Usuario')->pluck('name', 'id');
        }
        return Role::orderBy('name')->pluck('name', 'id');
    }

    #[Computed]
    public function rolNombre(): string
    {
        if (! $this->rol_id) return '';
        return Role::find($this->rol_id)?->name ?? '';
    }

    #[Computed]
    public function fotoPreviewUrl(): ?string
    {
        if ($this->foto) {
            return $this->foto->temporaryUrl();
        }
        // Recargar el usuario para obtener la foto actual
        return User::find($this->usuarioId)?->foto_url;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acciones
    // ─────────────────────────────────────────────────────────────────────────
    public function eliminarFoto(): void
    {
        $this->foto       = null;
        $this->fotoActual = null;
    }

    protected function rules(): array
    {
        $id = $this->usuarioId;

        $rules = [
            'name'            => 'required|string|max:255',
            'username'        => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($id)],
            'cedula'          => ['required', 'string', 'max:20',  Rule::unique('users', 'cedula')->ignore($id)],
            'ficha'           => ['required', 'string', 'max:100', Rule::unique('users', 'ficha')->ignore($id)],
            'telefono'        => 'nullable|string|max:20',
            'email'           => ['nullable', 'email', Rule::unique('users', 'email')->ignore($id)],
            'empresa_id'      => 'required|exists:empresas,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id'        => 'nullable|exists:cargos,id',
            'ubicacion_id'    => 'required|exists:ubicaciones,id',
            'jefe_id'         => ['nullable', 'exists:users,id'],
            'rol_id'          => 'required|exists:roles,id',
            'empresa_ids'     => 'nullable|array',
            'empresa_ids.*'   => 'exists:empresas,id',
            'password'        => 'nullable|min:6|confirmed',
            'foto'            => 'nullable|image|max:5120',
        ];

        if (Auth::user()->hasRole('Administrador')) {
            $rules['estado'] = 'required|in:Activo,Inactivo';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'name.required'          => 'El nombre es obligatorio.',
            'username.required'      => 'El nombre de usuario es obligatorio.',
            'username.unique'        => 'Ese nombre de usuario ya está en uso.',
            'cedula.required'        => 'La cédula es obligatoria.',
            'cedula.unique'          => 'Esa cédula ya está registrada.',
            'ficha.required'         => 'La ficha es obligatoria.',
            'ficha.unique'           => 'Esa ficha ya está registrada.',
            'email.unique'           => 'Ese correo ya está en uso.',
            'empresa_id.required'    => 'La empresa es obligatoria.',
            'ubicacion_id.required'  => 'La ubicación es obligatoria.',
            'rol_id.required'        => 'El rol es obligatorio.',
            'foto.image'             => 'El archivo debe ser una imagen.',
            'foto.max'               => 'La imagen no debe superar 5MB.',
            'password.confirmed'     => 'Las contraseñas no coinciden.',
            'password.min'           => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }

    public function actualizar(): void
    {
        $actor = Auth::user();

        // Recargar el usuario fresco para operar sobre él
        $usuario = User::findOrFail($this->usuarioId);

        $this->authorize('update', $usuario);
        $this->validate();

        $rol = Role::findOrFail($this->rol_id);

        if ($actor->hasRole('Analista') && ! $usuario->hasRole('Usuario')) {
            abort(403);
        }

        try {
            // ── Foto ──────────────────────────────────────────────────────────
            $fotoPath = $usuario->foto;

            if ($this->foto) {
                if ($fotoPath) {
                    Storage::disk('public')->delete($fotoPath);
                }
                $fotoPath = $this->foto->store('users', 'public');
            } elseif ($this->fotoActual === null && $usuario->foto) {
                Storage::disk('public')->delete($usuario->foto);
                $fotoPath = null;
            }

            // ── Datos base ────────────────────────────────────────────────────
            $data = [
                'name'            => $this->name,
                'username'        => $this->username,
                'email'           => $this->email ?: null,
                'empresa_id'      => $this->empresa_id,
                'departamento_id' => $this->departamento_id ?: null,
                'cargo_id'        => $this->cargo_id ?: null,
                'ubicacion_id'    => $this->ubicacion_id,
                'jefe_id'         => $this->jefe_id ?: null,
                'telefono'        => $this->telefono ?: null,
                'foto'            => $fotoPath,
                'ficha'           => $this->ficha,
                'cedula'          => $this->cedula,
            ];

            if ($actor->hasRole('Administrador')) {
                $data['estado'] = $this->estado;
            }

            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            $usuario->update($data);

            // ── Rol ───────────────────────────────────────────────────────────
            if ($actor->hasRole('Administrador')) {
                $usuario->syncRoles([$rol]);
            }

            // ── Empresas asignadas — SOLO para Analistas ──────────────────────
            if ($rol->name === 'Analista') {
                $usuario->empresasAsignadas()->sync($this->empresa_ids);
            } else {
                // Si cambió de Analista a otro rol, limpiar empresas asignadas
                $usuario->empresasAsignadas()->detach();
            }

            session()->flash('success', "Usuario {$usuario->name} actualizado correctamente.");
            $this->redirect(route('admin.usuarios.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('Error actualizando usuario: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al actualizar el usuario.');
        }
    }

    public function render()
    {
        return view('livewire.admin.editar-usuario');
    }
}
