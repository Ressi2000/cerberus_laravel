# CERBERUS — Guía maestra para construir un módulo

Versión: 2026-01 | Stack: Laravel 12 · PHP 8.3 · Livewire 3 · Alpine.js · Tailwind CSS · Spatie Permission · Material Icons

Esta instrucción define el orden, los patrones y las reglas que **deben seguirse siempre** al construir cualquier módulo nuevo en Cerberus. No es opcional ni aproximada — es el estándar del proyecto.

---

## 0. Principios rectores inamovibles

Antes de escribir una sola línea, internalizar estos cuatro principios:

**Multiempresa siempre.** Cada registro lleva `empresa_id`. El Analista solo ve su `empresa_activa_id`. El Administrador ve todo. El Usuario no accede al módulo.

**Visibilidad por ubicación física, no por empresa de nómina.** `empresa_id` es nómina. `empresa_activa_id` es contexto de sesión. `ubicacion_id` determina qué personas y equipos ve el Analista. `ubicacion.es_estado = true` marca foráneos visibles sin importar empresa activa.

**Lógica en el modelo, coordinación en Livewire, cero lógica en el Controller.** El Controller solo valida acceso (`authorize`) y retorna la vista. Livewire gestiona estado y validación. El Modelo tiene métodos de negocio, scopes y relaciones.

**Auditoría automática vía trait, nunca manual en CRUD.** El trait `Auditable` registra CREAR/EDITAR/ELIMINAR automáticamente. Solo se audita manualmente en acciones de negocio que no son CRUD puro (cambio de estado empresarial, decisiones específicas).

---

## 1. Orden de construcción

Siempre en este orden. No saltarse pasos.

```
1. Migración(es)
2. Modelo(s)
3. Policy
4. Seeder de permisos
5. Controller
6. Rutas (web.php)
7. Componentes Livewire
8. Vistas Blade
9. Sidebar
10. Factory + Seeder de datos
11. Feature Tests
```

---

## 2. Migración

### Reglas obligatorias

- Nombre de tabla: `snake_case` plural → `asignaciones`, `prestamos`, `mantenimientos`
- FK siempre con `constrained()` + política explícita (`restrictOnDelete` o `cascadeOnDelete` — nunca dejar el default)
- **Siempre incluir** `$table->timestamps()` y `$table->softDeletes()` en la tabla principal del módulo
- Las tablas pivot/detalle (ej: `asignacion_items`) NO llevan `softDeletes` — tienen su propio mecanismo de control
- Índices obligatorios: `empresa_id`, `estado` o `estado_id`, combinaciones frecuentes de filtro como `['empresa_id', 'estado']`
- `empresa_id` SIEMPRE presente y como primer índice

### Patrón de columnas mínimas de una tabla principal

```php
$table->id();
$table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
$table->foreignId('analista_id')->constrained('users')->restrictOnDelete();
// ... campos propios del módulo ...
$table->text('observaciones')->nullable();
$table->timestamps();
$table->softDeletes();

$table->index('empresa_id');
$table->index(['empresa_id', 'estado']); // si tiene campo estado
```

### Enums

Usar `$table->enum('estado', ['Valor1', 'Valor2', 'Valor3'])` con los valores exactos del dominio. No usar strings libres.

---

## 3. Modelo

### Traits obligatorios (en este orden)

```php
use HasFactory, SoftDeletes, Auditable;
```

`Auditable` es el trait personalizado de Cerberus en `App\Traits\Auditable`. Registra CREAR/EDITAR/ELIMINAR automáticamente en la tabla `auditoria`.

### Estructura del modelo

```php
class NombreModulo extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    // 1. fillable — todos los campos asignables masivamente
    protected $fillable = [...];

    // 2. casts — fechas, booleans, arrays JSON
    protected $casts = [
        'fecha_campo' => 'date',
        'campo_bool'  => 'boolean',
    ];

    // 3. booted() — reglas de negocio que previenen cambios inválidos
    protected static function booted(): void { ... }

    // 4. Relaciones — en orden: belongsTo → hasMany → belongsToMany
    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function analista() { return $this->belongsTo(User::class, 'analista_id'); }
    // ...

    // 5. Scopes — visibilidad y filtros comunes
    public function scopeVisiblePara(Builder $query, User $actor): Builder { ... }
    public function scopeActivos(Builder $query): Builder { ... }

    // 6. Helpers de negocio — métodos que encapsulan lógica del dominio
    public function recalcularEstado(): void { ... }
    public function estaActivo(): bool { ... }
}
```

### Scope `visiblePara` — patrón EXACTO de Cerberus

```php
public function scopeVisiblePara(Builder $query, User $actor): Builder
{
    if ($actor->hasRole('Administrador')) {
        return $query; // Admin ve todo
    }

    if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
        return $query->where('empresa_id', $actor->empresa_activa_id);
    }

    return $query->whereRaw('1 = 0'); // Rol Usuario → sin resultados
}
```

Si el módulo involucra personas (usuarios) en lugar de activos, el scope filtra por `ubicacion_id` en lugar de `empresa_id`:

```php
// Para módulos relacionados con personas:
return $query->where(function ($q) use ($actor) {
    $q->where('ubicacion_id', $actor->empresa_activa_id)
      ->orWhereHas('ubicacion', fn($u) => $u->where('es_estado', true));
});
```

### Regla de serialización en Livewire

**Nunca guardar un modelo Eloquent completo como propiedad pública de Livewire.** Guardar solo el ID (`public int $modeloId`) y exponer el objeto vía `#[Computed]`:

```php
public int $modeloId;

#[Computed]
public function modelo(): NombreModulo
{
    return NombreModulo::with(['relacion1', 'relacion2'])->findOrFail($this->modeloId);
}
```

---

## 4. Policy

### Patrón EXACTO de Cerberus

```php
class NombreModuloPolicy
{
    use HandlesAuthorization;

    /**
     * Cortocircuito:
     *   Admin     → true  (todo permitido, sin evaluar métodos)
     *   Usuario   → false (todo denegado, sin evaluar métodos)
     *   Analista  → null  (continúa a cada método)
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador')) return true;
        if ($user->hasRole('Usuario'))       return false;
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function view(User $user, NombreModulo $modelo): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $modelo->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function update(User $user, NombreModulo $modelo): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $modelo->empresa_id;
    }

    public function delete(User $user, NombreModulo $modelo): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $modelo->empresa_id;
    }

    // Acciones de negocio específicas del módulo (ej: devolver, aprobar, cerrar)
    public function accionEspecifica(User $user, NombreModulo $modelo): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $modelo->empresa_id
            && $modelo->estado !== 'Cerrado'; // guardia de estado
    }
}
```

### Registro de la Policy

Laravel auto-descubre por convención (`NombreModulo` → `NombreModuloPolicy`). Para hacerlo explícito en `AppServiceProvider::boot()`:

```php
Gate::policy(NombreModulo::class, NombreModuloPolicy::class);
```

### Uso en Livewire

```php
// En mount() — validar acceso al cargar
$this->authorize('create', NombreModulo::class);
$this->authorize('update', $modelo);

// En métodos de acción
$this->authorize('accionEspecifica', $modelo);
```

### Uso en Blade

```blade
@can('create', App\Models\NombreModulo::class)
    <button>Nuevo</button>
@endcan

@can('accionEspecifica', $modelo)
    <button>Acción</button>
@endcan
```

---

## 5. Seeder de permisos

### Nomenclatura de permisos en Cerberus

`"verbo sustantivo"` en minúsculas con espacio. Coherente con los existentes (`ver usuarios`, `crear usuarios`).

```
ver {modulo}
crear {modulo}
editar {modulo}
eliminar {modulo}
{accion_especifica} {modulo}   → ej: devolver asignaciones
```

### Patrón del seeder

```php
class NombreModuloPermisosSeeder extends Seeder
{
    private array $permisos = [
        'ver modulo'      => 'Ver listado y detalle',
        'crear modulo'    => 'Crear nuevos registros',
        'editar modulo'   => 'Editar registros existentes',
        'eliminar modulo' => 'Eliminación administrativa',
    ];

    private array $asignacionesPorRol = [
        'Administrador' => ['ver modulo', 'crear modulo', 'editar modulo', 'eliminar modulo'],
        'Analista'      => ['ver modulo', 'crear modulo', 'editar modulo'],
        // 'Usuario' → no recibe permisos del módulo
    ];

    public function run(): void
    {
        foreach ($this->permisos as $nombre => $descripcion) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        foreach ($this->asignacionesPorRol as $rolNombre => $permisosDelRol) {
            $rol = Role::where('name', $rolNombre)->first();
            if ($rol) $rol->givePermissionTo($permisosDelRol);
        }

        // Limpiar caché inmediatamente
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
```

**En producción, después de correr el seeder:**
```bash
php artisan permission:cache-reset
```

---

## 6. Controller

El Controller en Cerberus es **exclusivamente un coordinador**. Cero lógica de negocio.

```php
namespace App\Http\Controllers\NombreModulo;

class NombreModuloController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', NombreModulo::class);
        return view('admin.nombre-modulo.index');
    }

    public function create()
    {
        $this->authorize('create', NombreModulo::class);
        return view('admin.nombre-modulo.crear');
    }

    // Solo si el módulo lo requiere (ej: vista de devolución, aprobación, etc.)
    public function accionEspecifica(NombreModulo $modelo)
    {
        $this->authorize('accionEspecifica', $modelo);
        return view('admin.nombre-modulo.accion', compact('modelo'));
    }
}
```

**Convención de namespace del Controller:**
```
App\Http\Controllers\{NombreModulo}\{NombreModulo}Controller
```

---

## 7. Rutas

### Patrón de grupo de rutas en `routes/web.php`

```php
Route::prefix('admin/nombre-modulo')
    ->name('admin.nombre-modulo.')
    ->middleware(['auth', 'verified', 'user.active', 'empresa.activa', 'role:Administrador|Analista'])
    ->group(function () {
        Route::get('/',              [NombreModuloController::class, 'index'])  ->name('index');
        Route::get('/crear',         [NombreModuloController::class, 'create']) ->name('create');
        Route::get('/{modelo}/accion', [NombreModuloController::class, 'accionEspecifica'])->name('accionEspecifica');
    });
```

**Middleware stack obligatorio para módulos:**
- `auth` → autenticado
- `verified` → email verificado
- `user.active` → usuario activo (no inactivo)
- `empresa.activa` → Analista tiene empresa activa seleccionada
- `role:Administrador|Analista` → solo roles con acceso al módulo

---

## 8. Componentes Livewire

### Namespaces y rutas de archivos

```
App\Livewire\{NombreModulo}\{NombreModulo}Table       → tabla reactiva
App\Livewire\{NombreModulo}\Crear{NombreModulo}        → formulario creación
App\Livewire\{NombreModulo}\Editar{NombreModulo}       → formulario edición (si aplica)
App\Livewire\{NombreModulo}\{NombreModulo}ViewModal    → modal de detalle
App\Livewire\{NombreModulo}\{Accion}{NombreModulo}     → componente de acción específica
```

### Tabla reactiva — checklist de implementación

```php
class NombreModuloTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]      // sincroniza búsqueda con URL
    public string $search = '';

    public string $estado     = '';
    public string $fecha_desde = '';
    public string $fecha_hasta = '';
    public int    $perPage    = 10;

    // Reset paginación al cambiar cualquier filtro
    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'estado', 'fecha_desde', 'fecha_hasta']);
        $this->resetPage();
    }

    // Datos para selects de filtros — siempre como #[Computed]
    #[Computed]
    public function opcionesParaFiltro() { ... }

    // Contador de filtros activos — siempre como #[Computed]
    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->estado, ...])->filter()->count();
    }

    // Stats para las cards superiores — siempre como #[Computed]
    #[Computed]
    public function stats(): array { ... }

    public function render()
    {
        $query = NombreModulo::with([...])->visiblePara(Auth::user())->latest();
        // aplicar filtros...
        return view('livewire.nombre-modulo.nombre-modulo-table', [
            'registros' => $query->paginate($this->perPage),
            'stats'     => $this->stats,
        ]);
    }
}
```

### Formulario de creación — checklist

```php
class CrearNombreModulo extends Component
{
    // Propiedades tipadas (nunca sin tipo si Livewire puede serializarlas)
    public string $campo1 = '';
    public string $campo2 = '';

    public function mount(): void
    {
        $this->authorize('create', NombreModulo::class);
        // inicializar valores por defecto
    }

    // Datos para selects — #[Computed], nunca en render()
    #[Computed]
    public function opciones() { ... }

    // Validación — método rules() separado, con messages() separado
    protected function rules(): array { ... }
    protected function messages(): array { ... }

    public function guardar(): void
    {
        $this->authorize('create', NombreModulo::class);
        $this->validate();

        try {
            DB::transaction(function () {
                NombreModulo::create([...]);
            });
            session()->flash('success', 'Registro creado correctamente.');
            $this->redirect(route('admin.nombre-modulo.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('CrearNombreModulo@guardar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error. Por favor, inténtalo de nuevo.');
        }
    }

    public function render()
    {
        return view('livewire.nombre-modulo.crear-nombre-modulo');
    }
}
```

### Modal de detalle — patrón

```php
class NombreModuloViewModal extends Component
{
    public bool $open = false;
    public ?int $modeloId = null;

    #[On('openNombreModuloView')]
    public function abrir(int $id): void
    {
        $this->modeloId = $id;
        $this->open = true;
    }

    public function cerrar(): void
    {
        $this->open = false;
        $this->modeloId = null;
    }

    public function render()
    {
        $modelo = $this->modeloId
            ? NombreModulo::with([...])->find($this->modeloId)
            : null;

        return view('livewire.nombre-modulo.nombre-modulo-view-modal', compact('modelo'));
    }
}
```

---

## 9. Vistas Blade

### Vista de página (`resources/views/admin/{modulo}/index.blade.php`)

```blade
<x-app-layout title="{Módulo}" header="{Módulo}">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => '{Módulo}',  'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('{modulo}.{modulo}-table')

</x-app-layout>
```

### Componentes Blade disponibles y cuándo usar cada uno

| Componente | Cuándo usar |
|---|---|
| `<x-app-layout>` | Siempre en vistas de página. Props: `title`, `header` |
| `<x-ui.breadcrumb>` | Siempre. Array de `['label', 'url']`. Último ítem sin URL |
| `<x-ui.stats-cards>` | En la tabla reactiva. Array de `['title', 'value', 'icon']` |
| `<x-table.crud-header>` | En la tabla reactiva. Props: `title`, `subtitle`, `buttonLabel`, `buttonUrl` o `buttonEvent` |
| `<x-table.crud-table>` | Contenedor de la tabla. Props: `headers` (array), `paginated` |
| `<x-table.table-actions>` | En cada fila de la tabla. Props: `model`, `viewEvent`, `editUrl`, `deleteEvent`, `policy` |
| `<x-form.input>` | Inputs de texto, fecha, número. Soporta `wire:model`, `hint`, `type` |
| `<x-form.select>` | Selects. Props: `options` (array `id => nombre`), `placeholder` |
| `<x-form.textarea>` | Textareas. Props: `rows`, `placeholder`, `hint` |
| `<x-form.success>` | Flash de éxito. Siempre en vistas de página, antes del componente Livewire |
| `<x-form.errors>` | Flash de errores generales (formularios tradicionales) |

### Vista de tabla reactiva — estructura mínima

```blade
<div class="space-y-6">

    {{-- Modales registrados aquí --}}
    @livewire('nombre-modulo.nombre-modulo-view-modal')

    {{-- Stats cards --}}
    <x-ui.stats-cards :items="[...]" />

    {{-- Header + filtros --}}
    <x-table.crud-header title="..." subtitle="..." buttonLabel="..." :buttonUrl="...">
        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4 space-y-4">
                @if ($this->activeFiltersCount > 0)
                    {{-- Badge + botón limpiar --}}
                @endif
                <x-form.input label="Buscar" wire:model.live.500ms="search" />
                {{-- Otros filtros --}}
            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- Tabla --}}
    <x-table.crud-table :headers="['Col1', 'Col2', 'Acciones']" :paginated="$registros">
        @forelse ($registros as $registro)
            <tr wire:key="registro-{{ $registro->id }}" class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">
                <td class="px-4 py-3 text-gray-900 dark:text-white text-sm">{{ $registro->campo }}</td>
                {{-- ... --}}
                <td class="px-4 py-3 text-center">
                    <x-table.table-actions :model="$registro" viewEvent="openNombreModuloView" :editUrl="null" :deleteEvent="null" :policy="null" />
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="N" class="px-4 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                    <span class="material-icons text-4xl block mb-2 opacity-40">inbox</span>
                    No se encontraron registros.
                </td>
            </tr>
        @endforelse
    </x-table.crud-table>

</div>
```

### Clases Tailwind de referencia para texto y fondos

```
Fondo panel:       bg-white dark:bg-cerberus-mid
Borde panel:       border border-gray-200 dark:border-cerberus-steel
Texto principal:   text-gray-900 dark:text-white
Texto secundario:  text-gray-500 dark:text-cerberus-accent
Texto muted:       text-gray-400 dark:text-cerberus-steel
Hover fila tabla:  hover:bg-gray-50 dark:hover:bg-cerberus-steel/10
```

---

## 10. Sidebar

Agregar entrada en `resources/views/components/ui/sidebar.blade.php` siguiendo el patrón de los ítems existentes:

```blade
{{-- Ítem simple (sin submenú) --}}
@php $modOpen = $active('admin.nombre-modulo.*'); @endphp
<a href="{{ route('admin.nombre-modulo.index') }}"
   class="{{ $li }} {{ $modOpen ? $on : $off }}"
   title="Nombre Módulo">
    <span class="material-icons text-xl flex-shrink-0 {{ $modOpen ? $ion : $ioff }}">
        icono_material
    </span>
    <span class="sidebar-label flex-1 text-sm font-medium text-left whitespace-nowrap">
        Nombre Módulo
    </span>
    @include('components.ui._sidebar-tooltip', ['label' => 'Nombre Módulo'])
</a>

{{-- Ítem con submenú (igual que Equipos o Configuración) --}}
@php $modOpen = $active('admin.nombre-modulo.*'); @endphp
<div x-data="{ open: {{ $modOpen ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $li }} {{ $modOpen ? $on : $off }}" title="Nombre Módulo">
        <span class="material-icons text-xl flex-shrink-0 {{ $modOpen ? $ion : $ioff }}">icono_material</span>
        <span class="sidebar-label flex-1 text-sm font-medium text-left whitespace-nowrap">Nombre Módulo</span>
        <span class="sidebar-arrow material-icons text-sm text-gray-400 dark:text-cerberus-steel flex-shrink-0 transition-transform duration-200"
              :class="{ 'rotate-180': open }">expand_more</span>
        @include('components.ui._sidebar-tooltip', ['label' => 'Nombre Módulo'])
    </button>
    <div class="sidebar-submenu mt-0.5 ml-4 pl-3 border-l-2 border-gray-200 dark:border-cerberus-steel/40 space-y-0.5"
         x-show="open" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
        <a href="{{ route('admin.nombre-modulo.index') }}"
           class="{{ $li }} py-1.5 text-sm {{ $active('admin.nombre-modulo.index') ? $on : $off }}">
            <span class="material-icons text-base flex-shrink-0 {{ $active('admin.nombre-modulo.index') ? $ion : $ioff }}">list</span>
            <span class="whitespace-nowrap">Listado</span>
        </a>
    </div>
</div>
```

---

## 11. Factory y Seeder de datos

### Factory — patrón

```php
class NombreModuloFactory extends Factory
{
    protected $model = NombreModulo::class;

    public function definition(): array
    {
        $empresa  = Empresa::inRandomOrder()->first() ?? Empresa::factory()->create();
        $analista = User::role('Analista')->inRandomOrder()->first() ?? User::factory()->create();

        return [
            'empresa_id'       => $empresa->id,
            'analista_id'      => $analista->id,
            'campo1'           => $this->faker->word(),
            'estado'           => $this->faker->randomElement(['EstadoA', 'EstadoB']),
            'observaciones'    => $this->faker->optional(0.4)->sentence(),
        ];
    }

    // Estados encadenables
    public function estadoA(): static { return $this->state(['estado' => 'EstadoA']); }
    public function estadoB(): static { return $this->state(['estado' => 'EstadoB']); }
}
```

### Seeder de datos — patrón

```php
class NombreModuloSeeder extends Seeder
{
    public function run(): void
    {
        // Guardia: verificar datos base necesarios
        if (Empresa::count() === 0) {
            $this->command->warn('Sin empresas. Ejecuta InicialSeeder primero.');
            return;
        }

        // Crear datos representativos
        NombreModulo::factory()->count(10)->create();
        NombreModulo::factory()->estadoA()->count(5)->create();

        $this->command->info('Datos de NombreModulo generados: ' . NombreModulo::count());
    }
}
```

### Registro en DatabaseSeeder

```php
public function run(): void
{
    $this->call([
        InicialSeeder::class,
        NombreModuloPermisosSeeder::class, // permisos primero
        // ...
        NombreModuloSeeder::class,         // datos al final
    ]);
}
```

---

## 12. Feature Tests

### Estructura y bloques obligatorios

```php
class NombreModuloTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NombreModuloPermisosSeeder::class);
    }

    // Helpers de fixtures — siempre privados
    private function crearAdmin(): User { ... }
    private function crearAnalista(?Empresa $empresa = null): User { ... }
    private function crearUsuario(): User { ... }

    // BLOQUE 1: Control de acceso
    /** @test */
    public function usuario_sin_autenticar_es_redirigido(): void { ... }
    /** @test */
    public function rol_usuario_no_puede_acceder(): void { ... }
    /** @test */
    public function analista_puede_acceder(): void { ... }
    /** @test */
    public function analista_no_ve_datos_de_otra_empresa(): void { ... }

    // BLOQUE 2: Creación
    /** @test */
    public function puede_crear_registro_con_datos_validos(): void { ... }
    /** @test */
    public function falla_sin_campos_obligatorios(): void { ... }

    // BLOQUE 3: Acciones de negocio específicas del módulo
    // ...

    // BLOQUE 4: Auditoría
    /** @test */
    public function crear_registro_genera_entrada_en_auditoria(): void
    {
        // ... acción ...
        $this->assertDatabaseHas('auditoria', [
            'tabla'  => 'nombre_tabla',
            'accion' => 'CREAR',
        ]);
    }

    // BLOQUE 5: Filtros de la tabla
    /** @test */
    public function tabla_filtra_por_estado(): void { ... }
}
```

### Comandos de ejecución

```bash
# Suite completa del módulo
php artisan test tests/Feature/NombreModulo/NombreModuloTest.php --verbose

# Test específico
php artisan test --filter="puede_crear_registro"

# Todos los tests del proyecto
php artisan test
```

---

## 13. Checklist de entrega de un módulo

Antes de considerar un módulo "completo", verificar cada ítem:

**Base de datos**
- [ ] Migración principal con `empresa_id`, `timestamps`, `softDeletes`
- [ ] Índices en `empresa_id` y combinaciones de filtro frecuentes
- [ ] FK con política explícita en cada `constrained()`

**Modelo**
- [ ] Traits: `HasFactory`, `SoftDeletes`, `Auditable`
- [ ] `fillable` completo
- [ ] `casts` para fechas y booleans
- [ ] `scopeVisiblePara` implementado
- [ ] Relaciones completas (belongsTo, hasMany)
- [ ] Helpers de negocio en el modelo (no en Livewire)

**Acceso y permisos**
- [ ] Policy con `before()` → Admin/Usuario cortocircuitados
- [ ] Métodos de policy para cada operación del módulo
- [ ] `AsignacionPermisosSeeder` corrido (o equivalente)
- [ ] `php artisan permission:cache-reset` ejecutado tras el seeder

**Capas de la aplicación**
- [ ] Controller delgado (solo `authorize` + `return view`)
- [ ] Rutas con middleware stack completo
- [ ] Entrada en sidebar con detección de ruta activa

**Livewire**
- [ ] `NombreModuloTable` con filtros, paginación, stats y `activeFiltersCount`
- [ ] `CrearNombreModulo` con `authorize` en `mount()` y en la acción
- [ ] `NombreModuloViewModal` escuchando evento con `#[On]`
- [ ] Modales registrados con `@livewire(...)` en la vista de tabla
- [ ] IDs en lugar de modelos Eloquent en propiedades públicas de Livewire

**Vistas**
- [ ] `index.blade.php` con `<x-ui.breadcrumb>` y `<x-form.success>`
- [ ] Vista de tabla con `<x-ui.stats-cards>`, `<x-table.crud-header>`, `<x-table.crud-table>`
- [ ] `<x-table.table-actions>` en cada fila
- [ ] Modo claro y oscuro en todas las clases Tailwind (`dark:` en todo)

**Calidad**
- [ ] Factory con estados encadenables
- [ ] Seeder de datos con guardia de prerequisitos
- [ ] Feature Tests cubriendo: acceso, CRUD, acciones específicas, auditoría, filtros

---

## 14. Anti-patrones prohibidos en Cerberus

❌ **Lógica de negocio en el Controller** → va en el Modelo o en el componente Livewire
❌ **Modelo Eloquent completo como propiedad pública de Livewire** → guardar solo el ID
❌ **`DB::insert` directo en `model_has_roles`** → siempre `$user->assignRole()`
❌ **Auditoría manual en CRUD** → el trait Auditable lo hace automáticamente
❌ **`$table->foreign()` sin política explícita** → siempre `restrictOnDelete()` o `cascadeOnDelete()`
❌ **Clases Tailwind sin `dark:`** → toda clase de color necesita su variante oscura
❌ **`<x-breadcrumb>`** → el componente correcto es `<x-ui.breadcrumb>`
❌ **`<x-table-actions>`** → el componente correcto es `<x-table.table-actions>`
❌ **`wireModel` sin debounce en búsquedas** → siempre `wire:model.live.500ms` para inputs de texto
❌ **Policy sin `before()`** → siempre incluir el cortocircuito de Admin y Usuario
❌ **Permiso en producción sin `permission:cache-reset`** → Spatie cachea 24h, los cambios no aplican sin limpiar
