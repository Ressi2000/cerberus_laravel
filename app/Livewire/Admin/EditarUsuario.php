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

/**
 * EditarUsuario — Componente Livewire 3
 *
 * Por qué guardamos $usuarioId (int) y no el objeto User:
 *   Livewire serializa todas las propiedades públicas en cada request.
 *   Serializar un modelo Eloquent completo con relaciones es costoso y
 *   puede fallar. El patrón correcto es guardar solo el ID y exponer
 *   el objeto a través de una computed property cacheada.
 *
 * $usuario → computed property → accesible en el blade como $usuario
 */
class EditarUsuario extends Component
{
    use WithFileUploads;

    // ── ID del usuario que se edita ───────────────────────────────────────────
    // Solo este int se serializa en el estado de Livewire (liviano y seguro)
    public int $usuarioId;

    // ── Datos personales ──────────────────────────────────────────────────────
    public string $name     = '';
    public string $username = '';
    public string $cedula   = '';
    public string $ficha    = '';
    public string $telefono = '';
    public string $email    = '';
    public $foto            = null;
    public ?string $fotoActual = null;

    // ── Datos laborales ───────────────────────────────────────────────────────
    public string $empresa_id      = '';
    public string $departamento_id = '';
    public string $cargo_id        = '';
    public string $ubicacion_id    = '';
    public string $jefe_id         = '';

    // ── Acceso ────────────────────────────────────────────────────────────────
    public string $rol_id               = '';
    public string $estado               = 'Activo';
    public string $password             = '';
    public string $password_confirmation = '';

    // ── Empresas asignadas (Analistas) ────────────────────────────────────────
    // Se carga una sola vez en mount(). NO se limpia al cambiar empresa_id,
    // ya que empresa_id = nómina y empresa_ids = acceso analista son independientes.
    public array $empresa_ids = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Mount: recibir el ID, cargar el modelo y llenar los campos
    // ─────────────────────────────────────────────────────────────────────────
    public function mount(int $usuarioId): void
    {
        $this->usuarioId = $usuarioId;

        // Cargar con relaciones necesarias para la hidratación inicial
        $u = User::with(['roles', 'empresasAsignadas'])->findOrFail($usuarioId);

        $this->authorize('update', $u);

        // Datos personales
        $this->name       = $u->name;
        $this->username   = $u->username;
        $this->cedula     = $u->cedula     ?? '';
        $this->ficha      = $u->ficha      ?? '';
        $this->telefono   = $u->telefono   ?? '';
        $this->email      = $u->email      ?? '';
        $this->fotoActual = $u->foto;

        // Datos laborales — convertir a string para que los selects coincidan
        $this->empresa_id      = (string) ($u->empresa_id      ?? '');
        $this->departamento_id = (string) ($u->departamento_id ?? '');
        $this->cargo_id        = (string) ($u->cargo_id        ?? '');
        $this->ubicacion_id    = (string) ($u->ubicacion_id    ?? '');
        $this->jefe_id         = (string) ($u->jefe_id         ?? '');

        // Acceso
        $this->rol_id = (string) ($u->roles->first()?->id ?? '');
        $this->estado = $u->estado;

        // Empresas asignadas (pivot) — se carga una sola vez aquí
        $this->empresa_ids = $u->empresasAsignadas
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cascada de selects
    // ─────────────────────────────────────────────────────────────────────────

    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
        $this->cargo_id        = '';
        unset($this->departamentos, $this->cargos);
    }

    public function updatedDepartamentoId(): void
    {
        $this->cargo_id = '';
        unset($this->cargos);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed Properties
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * El objeto User del usuario que se está editando.
     * Se expone como $usuario en el blade (Livewire 3 inyecta computed
     * properties como variables de la vista con el mismo nombre).
     * Se recarga fresco de BD en cada request, así siempre refleja
     * el estado actual (foto_url, estado badge, etc.).
     */
    #[Computed]
    public function usuario(): User
    {
        return User::with(['roles', 'empresaNomina'])->findOrFail($this->usuarioId);
    }

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

    /**
     * URL de preview de la foto.
     * Prioridad: nueva foto subida (temporal) → foto actual en storage → avatar generado
     */
    #[Computed]
    public function fotoPreviewUrl(): string
    {
        if ($this->foto) {
            return $this->foto->temporaryUrl();
        }

        return User::find($this->usuarioId)?->foto_url
            ?? 'https://ui-avatars.com/api/?name=U&background=1B263B&color=A9D6E5&size=128';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acciones
    // ─────────────────────────────────────────────────────────────────────────

    /** Marcar la foto para eliminación al guardar */
    public function eliminarFoto(): void
    {
        $this->foto       = null;
        $this->fotoActual = null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación
    // ─────────────────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $id = $this->usuarioId;

        return [
            'name'            => 'required|string|max:255',
            'username'        => ['required', 'string', 'max:50',
                                  Rule::unique('users', 'username')->ignore($id)],
            'cedula'          => ['required', 'string', 'max:15',
                                  'regex:/^[VvEe]-\d{6,9}$/',
                                  Rule::unique('users', 'cedula')->ignore($id)],
            'ficha'           => ['required', 'string', 'max:50',
                                  Rule::unique('users', 'ficha')->ignore($id)],
            'telefono'        => 'nullable|string|max:20',
            'email'           => 'required|email|max:255',
            'foto'            => 'nullable|image|max:5120',

            'empresa_id'      => 'required|exists:empresas,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'cargo_id'        => 'nullable|exists:cargos,id',
            'ubicacion_id'    => 'required|exists:ubicaciones,id',
            'jefe_id'         => 'nullable|exists:users,id',

            'rol_id'          => 'required|exists:roles,id',
            'empresa_ids'     => 'nullable|array',
            'empresa_ids.*'   => 'exists:empresas,id',
            'password'        => 'nullable|min:6|confirmed',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'         => 'El nombre completo es obligatorio.',
            'username.required'     => 'El nombre de usuario es obligatorio.',
            'username.unique'       => 'Ese nombre de usuario ya está en uso.',
            'cedula.required'       => 'La cédula es obligatoria.',
            'cedula.unique'         => 'Esa cédula ya está registrada.',
            'cedula.regex'          => 'La cédula debe tener el formato V-12345678 o E-12345678.',
            'ficha.required'        => 'La ficha de nómina es obligatoria.',
            'ficha.unique'          => 'Esa ficha ya está registrada.',
            'email.required'        => 'El correo electrónico es obligatorio.',
            'empresa_id.required'   => 'Debe seleccionar la empresa de nómina.',
            'ubicacion_id.required' => 'Debe seleccionar la ubicación del usuario.',
            'rol_id.required'       => 'Debe asignar un rol al usuario.',
            'foto.image'            => 'El archivo debe ser una imagen.',
            'foto.max'              => 'La imagen no debe superar los 5MB.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'password.min'          => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar cambios
    // ─────────────────────────────────────────────────────────────────────────
    public function actualizar(): void
    {
        $actor   = Auth::user();
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
                if ($fotoPath) Storage::disk('public')->delete($fotoPath);
                $fotoPath = $this->foto->store('users', 'public');
            } elseif ($this->fotoActual === null && $usuario->foto) {
                Storage::disk('public')->delete($usuario->foto);
                $fotoPath = null;
            }

            // ── Datos ─────────────────────────────────────────────────────────
            $data = [
                'name'            => $this->name,
                'username'        => $this->username,
                'email'           => $this->email,
                'empresa_id'      => $this->empresa_id,
                'departamento_id' => $this->departamento_id ?: null,
                'cargo_id'        => $this->cargo_id        ?: null,
                'ubicacion_id'    => $this->ubicacion_id,
                'jefe_id'         => $this->jefe_id         ?: null,
                'telefono'        => $this->telefono        ?: null,
                'foto'            => $fotoPath,
                'ficha'           => $this->ficha,
                'cedula'          => strtoupper($this->cedula),
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

            // ── Empresas asignadas ────────────────────────────────────────────
            if ($rol->name === 'Analista') {
                $usuario->empresasAsignadas()->sync($this->empresa_ids);
            } else {
                $usuario->empresasAsignadas()->detach();
            }

            session()->flash('success', "Usuario «{$usuario->name}» actualizado correctamente.");
            $this->redirect(route('admin.usuarios.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('EditarUsuario@actualizar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al actualizar el usuario.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.admin.editar-usuario');
    }
}