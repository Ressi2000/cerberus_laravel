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
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

class CrearUsuario extends Component
{
    use WithFileUploads;

    // ── Datos personales ──────────────────────────────────────────────────────
    public string $name            = '';
    public string $username        = '';
    public string $cedula          = '';
    public string $ficha           = '';
    public string $telefono        = '';
    public string $email           = '';
    public $foto                   = null;

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

    // ── Cascada: limpiar al cambiar empresa ───────────────────────────────────
    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
        $this->cargo_id        = '';
        // $this->empresa_ids     = [];
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
        return User::seleccionables()->pluck('name', 'id');
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

    // ── Validación ────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $rules = [
            'name'           => 'required|string|max:255',
            'username'       => 'required|string|max:50|unique:users,username',
            'cedula'         => 'required|string|max:20|unique:users,cedula',
            'ficha'          => 'required|string|max:100|unique:users,ficha',
            'telefono'       => 'nullable|string|max:20',
            'email'          => 'nullable|email|unique:users,email',
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

    // ── Guardar ───────────────────────────────────────────────────────────────
    public function guardar(): void
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $this->validate();

        $rol = Role::findOrFail($this->rol_id);

        if ($actor->hasRole('Analista') && $rol->name !== 'Usuario') {
            $this->addError('rol_id', 'No puedes crear usuarios con ese rol.');
            return;
        }

        $fotoPath = $this->foto
            ? $this->foto->store('users', 'public')
            : null;

        try {
            $user = User::create([
                'name'              => $this->name,
                'username'          => $this->username,
                'email'             => $this->email ?: null,
                'password'          => Hash::make($this->password ?: '12345678'),
                'empresa_id'        => $this->empresa_id,
                'empresa_activa_id' => $this->empresa_id,
                'departamento_id'   => $this->departamento_id ?: null,
                'cargo_id'          => $this->cargo_id ?: null,
                'ubicacion_id'      => $this->ubicacion_id,
                'jefe_id'           => $this->jefe_id ?: null,
                'estado'            => $actor->hasRole('Analista') ? 'Activo' : ($this->estado ?: 'Activo'),
                'telefono'          => $this->telefono ?: null,
                'foto'              => $fotoPath,
                'ficha'             => $this->ficha,
                'cedula'            => $this->cedula,
            ]);

            $user->assignRole($rol);

            if ($rol->name === 'Analista' && ! empty($this->empresa_ids)) {
                $user->empresasAsignadas()->sync($this->empresa_ids);
            }

            session()->flash('success', "Usuario {$user->name} creado correctamente.");
            $this->redirect(route('admin.usuarios.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('Error creando usuario: ' . $e->getMessage());
            if ($fotoPath) Storage::disk('public')->delete($fotoPath);
            $this->addError('general', 'Ocurrió un error al crear el usuario. Inténtalo de nuevo.');
        }
    }

    public function render()
    {
        return view('livewire.admin.crear-usuario');
    }
}
