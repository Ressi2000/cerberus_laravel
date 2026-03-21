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
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

/**
 * CrearUsuario — Componente Livewire 3
 *
 * Responsabilidades:
 *  - Formulario de creación de usuario con cascada empresa → depto → cargo
 *  - Subida y recorte de foto de perfil
 *  - Restricción de roles según quién crea (Analista solo crea Usuarios)
 *  - Asignación de empresas múltiples para rol Analista
 *
 * Flujo principal:
 *  mount() → usuario llena form → guardar() → redirect index
 */
class CrearUsuario extends Component
{
    use WithFileUploads;

    // ── Datos personales ──────────────────────────────────────────────────────
    public string $name     = '';
    public string $username = '';
    public string $cedula   = '';   // Formato: V-12345678 o E-12345678
    public string $ficha    = '';   // Código numérico de nómina
    public string $telefono = '';
    public string $email    = '';
    public $foto            = null; // Livewire WithFileUploads

    // ── Datos laborales ───────────────────────────────────────────────────────
    public string $empresa_id      = '';
    public string $departamento_id = '';  // Se limpia al cambiar empresa
    public string $cargo_id        = '';  // Se limpia al cambiar departamento
    public string $ubicacion_id    = '';
    public string $jefe_id         = '';

    // ── Acceso al sistema ─────────────────────────────────────────────────────
    public string $rol_id               = '';
    public string $estado               = 'Activo';
    public string $password             = '';
    public string $password_confirmation = '';

    // ── Empresas asignadas (solo para rol Analista) ───────────────────────────
    public array $empresa_ids = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Cascada: limpiar campos dependientes cuando cambia el padre
    // ─────────────────────────────────────────────────────────────────────────

    /** Al cambiar empresa: limpiar departamento y cargo */
    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
        $this->cargo_id        = '';

        // Invalidar cache de computed properties dependientes
        unset($this->departamentos, $this->cargos);
    }

    /** Al cambiar departamento: limpiar cargo */
    public function updatedDepartamentoId(): void
    {
        $this->cargo_id = '';
        unset($this->cargos);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed Properties — se recalculan solo cuando sus dependencias cambian
    // El atributo #[Computed] cachea el resultado por request
    // ─────────────────────────────────────────────────────────────────────────

    /** Lista de empresas para el select de nómina */
    #[Computed]
    public function empresas()
    {
        return Empresa::orderBy('nombre')->pluck('nombre', 'id');
    }

    /**
     * Departamentos filtrados por empresa seleccionada.
     * empresa_id = NULL → globales (visibles en todas las empresas)
     * empresa_id = X    → exclusivos de esa empresa
     */
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

    /**
     * Cargos filtrados por departamento seleccionado.
     * Devuelve colección vacía si no hay departamento elegido.
     */
    #[Computed]
    public function cargos()
    {
        if (! $this->departamento_id) {
            return collect();
        }

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

    /** Todas las ubicaciones disponibles */
    #[Computed]
    public function ubicaciones()
    {
        return Ubicacion::orderBy('nombre')->pluck('nombre', 'id');
    }

    /** Usuarios que pueden ser jefes (activos, excluyendo al usuario actual) */
    #[Computed]
    public function jefes()
    {
        return User::seleccionables()->pluck('name', 'id');
    }

    /**
     * Roles disponibles según quien está creando:
     *  - Administrador → todos los roles
     *  - Analista       → solo el rol "Usuario"
     */
    #[Computed]
    public function roles()
    {
        if (Auth::user()->hasRole('Analista')) {
            return Role::where('name', 'Usuario')->pluck('name', 'id');
        }

        return Role::orderBy('name')->pluck('name', 'id');
    }

    /** Nombre del rol seleccionado (para mostrar/ocultar sección de empresas asignadas) */
    #[Computed]
    public function rolNombre(): string
    {
        if (! $this->rol_id) return '';
        return Role::find($this->rol_id)?->name ?? '';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación
    // ─────────────────────────────────────────────────────────────────────────

    protected function rules(): array
    {
        return [
            // Personales
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'cedula'   => [
                'required',
                'string',
                'max:15',
                'unique:users,cedula',
                // Formato: V-12345678 o E-12345678
                'regex:/^[VvEe]-\d{6,9}$/',
            ],
            'ficha'    => 'required|string|max:50|unique:users,ficha',
            'telefono' => 'nullable|string|max:20',
            'email'    => 'required|email|max:255',
            'foto'     => 'nullable|image|max:5120',

            // Laborales
            'empresa_id'      => 'required|exists:empresas,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id'        => 'nullable|exists:cargos,id',
            'ubicacion_id'    => 'required|exists:ubicaciones,id',
            'jefe_id'         => 'nullable|exists:users,id',

            // Acceso
            'rol_id'      => 'required|exists:roles,id',
            'empresa_ids'   => 'nullable|array',
            'empresa_ids.*' => 'exists:empresas,id',
            'password'    => 'nullable|min:6|confirmed',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'          => 'El nombre completo es obligatorio.',
            'username.required'      => 'El nombre de usuario es obligatorio.',
            'username.unique'        => 'Ese nombre de usuario ya está en uso.',
            'cedula.required'        => 'La cédula es obligatoria.',
            'cedula.unique'          => 'Esa cédula ya está registrada en el sistema.',
            'cedula.regex'           => 'La cédula debe tener el formato V-12345678 o E-12345678.',
            'ficha.required'         => 'La ficha de nómina es obligatoria.',
            'ficha.unique'           => 'Esa ficha ya está registrada.',
            'email.required'         => 'El correo electrónico es obligatorio.',
            'email.email'            => 'El correo electrónico no tiene un formato válido.',
            'empresa_id.required'    => 'Debe seleccionar la empresa de nómina.',
            'ubicacion_id.required'  => 'Debe seleccionar la ubicación del usuario.',
            'rol_id.required'        => 'Debe asignar un rol al usuario.',
            'foto.image'             => 'El archivo debe ser una imagen (JPG, PNG, etc.).',
            'foto.max'               => 'La imagen no debe superar los 5MB.',
            'password.confirmed'     => 'Las contraseñas no coinciden.',
            'password.min'           => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acción principal: guardar el nuevo usuario
    // ─────────────────────────────────────────────────────────────────────────
    public function guardar(): void
    {
        /** @var User $actor */
        $actor = Auth::user();

        // Validar todos los campos
        $this->validate();

        // Seguridad extra: Analista solo puede crear rol Usuario
        $rol = Role::findOrFail($this->rol_id);

        if ($actor->hasRole('Analista') && $rol->name !== 'Usuario') {
            $this->addError('rol_id', 'No tienes permiso para crear usuarios con ese rol.');
            return;
        }

        // Guardar foto si se subió
        $fotoPath = $this->foto
            ? $this->foto->store('users', 'public')
            : null;

        try {
            // Crear el usuario
            $user = User::create([
                'name'              => $this->name,
                'username'          => $this->username,
                'email'             => $this->email,
                'password'          => Hash::make($this->password ?: '12345678'),
                'empresa_id'        => $this->empresa_id,
                'empresa_activa_id' => $this->empresa_id,
                'departamento_id'   => $this->departamento_id  ?: null,
                'cargo_id'          => $this->cargo_id          ?: null,
                'ubicacion_id'      => $this->ubicacion_id,
                'jefe_id'           => $this->jefe_id           ?: null,
                'estado'            => $actor->hasRole('Analista') ? 'Activo' : $this->estado,
                'telefono'          => $this->telefono           ?: null,
                'foto'              => $fotoPath,
                'ficha'             => $this->ficha,
                'cedula'            => strtoupper($this->cedula), // Normalizar a mayúsculas V-/E-
            ]);

            // Asignar rol
            $user->assignRole($rol);

            // Si es Analista, asignar empresas en la tabla pivot empresa_user
            if ($rol->name === 'Analista' && ! empty($this->empresa_ids)) {
                $user->empresasAsignadas()->sync($this->empresa_ids);
            }

            session()->flash('success', "Usuario «{$user->name}» creado correctamente.");
            $this->redirect(route('admin.usuarios.index'), navigate: true);

        } catch (\Exception $e) {
            // Si hubo error, eliminar la foto que se subió para no dejar archivos huérfanos
            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }

            Log::error('CrearUsuario@guardar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al crear el usuario. Inténtalo de nuevo.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.admin.crear-usuario');
    }
}