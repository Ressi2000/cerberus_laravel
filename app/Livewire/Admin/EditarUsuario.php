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

    public User $usuario;

    // ── Datos personales ──────────────────────────────────────────────────────
    public string $name            = '';
    public string $username        = '';
    public string $cedula          = '';
    public string $ficha           = '';
    public string $telefono        = '';
    public string $email           = '';
    public $foto                   = null;   // nueva foto (Livewire upload)
    public ?string $fotoActual     = null;   // path de la foto existente

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
    public array  $empresa_ids     = [];

    // ── Mount: cargar datos del usuario ───────────────────────────────────────
    public function mount(User $usuario): void
    {
        $this->authorize('update', $usuario);

        $this->usuario = $usuario->load(['roles', 'empresasAsignadas']);

        $this->name            = $usuario->name;
        $this->username        = $usuario->username;
        $this->cedula          = $usuario->cedula ?? '';
        $this->ficha           = $usuario->ficha ?? '';
        $this->telefono        = $usuario->telefono ?? '';
        $this->email           = $usuario->email ?? '';
        $this->fotoActual      = $usuario->foto;

        $this->empresa_id      = (string) ($usuario->empresa_id ?? '');
        $this->departamento_id = (string) ($usuario->departamento_id ?? '');
        $this->cargo_id        = (string) ($usuario->cargo_id ?? '');
        $this->ubicacion_id    = (string) ($usuario->ubicacion_id ?? '');
        $this->jefe_id         = (string) ($usuario->jefe_id ?? '');

        $this->rol_id          = (string) ($usuario->roles->first()?->id ?? '');
        $this->estado          = $usuario->estado;
        $this->empresa_ids     = $usuario->empresasAsignadas->pluck('id')->map(fn($id) => (string) $id)->toArray();
    }

    // ── Cascada ───────────────────────────────────────────────────────────────
    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
        $this->cargo_id        = '';
        $this->empresa_ids     = [];
        unset($this->departamentos, $this->cargos);
    }

    public function updatedDepartamentoId(): void
    {
        $this->cargo_id = '';
        unset($this->cargos);
    }

    // ── Computed properties ───────────────────────────────────────────────────
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
            ->where('id', '!=', $this->usuario->id)  // no puede ser su propio jefe
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
        // Si hay una foto nueva subida, mostrar preview temporal
        if ($this->foto) {
            return $this->foto->temporaryUrl();
        }
        // Si no, mostrar la foto actual del usuario
        return $this->usuario->foto_url;
    }

    // ── Eliminar foto actual ──────────────────────────────────────────────────
    public function eliminarFoto(): void
    {
        $this->foto        = null;
        $this->fotoActual  = null;
    }

    // ── Validación ────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $id = $this->usuario->id;

        $rules = [
            'name'           => 'required|string|max:255',
            'username'       => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($id)],
            'cedula'         => ['required', 'string', 'max:20', Rule::unique('users', 'cedula')->ignore($id)],
            'ficha'          => ['required', 'string', 'max:100', Rule::unique('users', 'ficha')->ignore($id)],
            'telefono'       => 'nullable|string|max:20',
            'email'          => ['nullable', 'email', Rule::unique('users', 'email')->ignore($id)],
            'empresa_id'     => 'required|exists:empresas,id',
            'departamento_id'=> 'nullable|exists:departamentos,id',
            'cargo_id'       => 'nullable|exists:cargos,id',
            'ubicacion_id'   => 'required|exists:ubicaciones,id',
            'jefe_id'        => ['nullable', 'exists:users,id'],
            'rol_id'         => 'required|exists:roles,id',
            'empresa_ids'    => 'nullable|array',
            'empresa_ids.*'  => 'exists:empresas,id',
            'password'       => 'nullable|min:6|confirmed',
            'foto'           => 'nullable|image|max:5120',
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

    // ── Actualizar ────────────────────────────────────────────────────────────
    public function actualizar(): void
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $this->authorize('update', $this->usuario);
        $this->validate();

        $rol = Role::findOrFail($this->rol_id);

        // Analista solo puede editar Usuarios
        if ($actor->hasRole('Analista') && ! $this->usuario->hasRole('Usuario')) {
            abort(403);
        }

        try {
            // ── Foto ──────────────────────────────────────────────────────────
            $fotoPath = $this->usuario->foto; // mantener la actual por defecto

            if ($this->foto) {
                // Subir nueva foto y borrar la anterior
                if ($fotoPath) {
                    Storage::disk('public')->delete($fotoPath);
                }
                $fotoPath = $this->foto->store('users', 'public');

            } elseif ($this->fotoActual === null && $this->usuario->foto) {
                // Usuario pidió eliminar la foto
                Storage::disk('public')->delete($this->usuario->foto);
                $fotoPath = null;
            }

            // ── Datos base ────────────────────────────────────────────────────
            $data = [
                'name'           => $this->name,
                'username'       => $this->username,
                'email'          => $this->email ?: null,
                'empresa_id'     => $this->empresa_id,
                'departamento_id'=> $this->departamento_id ?: null,
                'cargo_id'       => $this->cargo_id ?: null,
                'ubicacion_id'   => $this->ubicacion_id,
                'jefe_id'        => $this->jefe_id ?: null,
                'telefono'       => $this->telefono ?: null,
                'foto'           => $fotoPath,
                'ficha'          => $this->ficha,
                'cedula'         => $this->cedula,
            ];

            // Solo Admin puede cambiar estado y rol
            if ($actor->hasRole('Administrador')) {
                $data['estado'] = $this->estado;
            }

            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            $this->usuario->update($data);

            // ── Rol ───────────────────────────────────────────────────────────
            if ($actor->hasRole('Administrador')) {
                $this->usuario->syncRoles([$rol]);
            }

            // ── Empresas asignadas ────────────────────────────────────────────
            if ($rol->name === 'Analista') {
                $this->usuario->empresasAsignadas()->sync($this->empresa_ids);
            } else {
                $this->usuario->empresasAsignadas()->detach();
            }

            // ── Empresa activa: sincronizar si cambió la empresa nómina ───────
            if ((string) $this->usuario->empresa_id !== (string) $this->empresa_id) {
                $this->usuario->update(['empresa_activa_id' => $this->empresa_id]);
            }

            session()->flash('success', "Usuario {$this->usuario->name} actualizado correctamente.");
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
