<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\RegistrarEntradaModal;
use App\Livewire\Mantenimientos\UsarDelAlmacenModal;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EstadoEquipo;
use App\Models\FamiliaComponente;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\User;
use App\Services\VinculacionFamiliaService;
use Database\Seeders\EstadosEquiposSeeder;
use Database\Seeders\FamiliasComponentesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fase 3 — Una sola forma de meter stock (Registrar entrada) y una sola de
 * usarlo en un caso (Usar del almacén).
 */
class EntradaYUsoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $admin;
    private CategoriaEquipo $laptop;
    private FamiliaComponente $ram;
    private FamiliaComponente $pantallas;
    private FamiliaComponente $bisagras;
    private AtributoEquipo $ramLaptop;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);
        $this->seed(FamiliasComponentesSeeder::class);

        $this->empresa = Empresa::create(['nombre' => 'Empresa A']);
        $this->admin   = User::factory()->create(['empresa_id' => $this->empresa->id]);
        $this->admin->assignRole('Administrador');

        $this->ram       = FamiliaComponente::where('nombre', 'RAM')->firstOrFail();
        $this->pantallas = FamiliaComponente::create(['nombre' => 'Pantallas', 'control' => 'unidad', 'campos' => []]);
        $this->bisagras  = FamiliaComponente::create(['nombre' => 'Bisagras', 'control' => 'granel', 'campos' => []]);

        $this->laptop    = CategoriaEquipo::create(['nombre' => 'Laptop']);
        $this->ramLaptop = AtributoEquipo::create(['categoria_id' => $this->laptop->id, 'nombre' => 'RAM', 'slug' => 'ram', 'tipo' => 'group', 'reutilizable' => true]);
        app(VinculacionFamiliaService::class)->vincularSinDatos($this->ramLaptop, $this->ram);
    }

    // ─── Fixtures ──────────────────────────────────────────────────────────

    private function campo(string $nombre): string
    {
        return collect($this->ram->campos)->firstWhere('nombre', $nombre)['id'];
    }

    private function crearCaso(): Mantenimiento
    {
        $equipo = Equipo::forceCreate([
            'empresa_id' => $this->empresa->id, 'categoria_id' => $this->laptop->id,
            'estado_id' => EstadoEquipo::where('nombre', 'En reparación')->value('id'),
            'codigo_interno' => 'EQ-' . uniqid(), 'activo' => true,
        ]);

        return Mantenimiento::create([
            'empresa_id' => $this->empresa->id, 'equipo_id' => $equipo->id, 'tipo' => 'Correctivo',
            'estado' => 'En reparación', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->toDateString(),
        ]);
    }

    /** Entrada de RAM por la ficha: devuelve el componente (variante) creado. */
    private function entrarRam(string $capacidad, string $tipo, string $seriales): ComponenteAlmacen
    {
        Livewire::actingAs($this->admin)
            ->test(RegistrarEntradaModal::class)
            ->call('abrir')
            ->set('empresa_id', (string) $this->empresa->id)
            ->set('familiaId', (string) $this->ram->id)
            ->set('valores.' . $this->campo('Capacidad (GB)'), $capacidad)
            ->set('valores.' . $this->campo('Tipo'), $tipo)
            ->set('valores.' . $this->campo('Formato'), 'SO-DIMM (laptop)')
            ->set('cantidad', count(explode("\n", $seriales)))
            ->set('seriales', $seriales)
            ->call('guardar')
            ->assertHasNoErrors();

        return ComponenteAlmacen::where('familia_id', $this->ram->id)->latest('id')->firstOrFail();
    }

    // ─── Registrar entrada ─────────────────────────────────────────────────

    /** @test */
    public function una_entrada_de_ram_por_la_ficha_crea_la_variante_y_sus_piezas_con_serial(): void
    {
        $componente = $this->entrarRam('8', 'DDR4', "SN-1\nSN-2");

        $this->assertSame('RAM (Capacidad (GB): 8 / Tipo: DDR4 / Formato: SO-DIMM (laptop))', $componente->nombre);
        $this->assertSame(2, $componente->stock_actual);
        $piezas = PiezaExtraida::where('componente_almacen_id', $componente->id)->get();
        $this->assertSame(['SN-1', 'SN-2'], $piezas->pluck('identificador')->all());
        $this->assertTrue($piezas->every(fn ($p) => $p->atributo_id === $this->ramLaptop->id), 'Quedan ligadas a la ficha para poder instalarse.');

        // Otra entrada con los mismos valores cae en la misma variante.
        $this->assertSame($componente->id, $this->entrarRam('8', 'DDR4', 'SN-3')->id);
        $this->assertSame(3, $componente->fresh()->stock_actual);
    }

    /** @test */
    public function una_entrada_puede_crear_una_variante_nueva_en_una_familia_sin_ficha(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RegistrarEntradaModal::class)
            ->call('abrir')
            ->set('empresa_id', (string) $this->empresa->id)
            ->set('familiaId', (string) $this->bisagras->id)
            ->set('componenteId', 'nueva')
            ->set('nuevoNombre', 'Bisagra HP 14"')
            ->set('cantidad', 12)
            ->call('guardar')
            ->assertHasNoErrors();

        $variante = ComponenteAlmacen::where('nombre', 'Bisagra HP 14"')->firstOrFail();
        $this->assertSame($this->bisagras->id, $variante->familia_id);
        $this->assertSame(12, $variante->stock_actual);
        $this->assertSame(0, PiezaExtraida::where('componente_almacen_id', $variante->id)->count(), 'A granel: sin piezas.');
    }

    // ─── Usar del almacén ──────────────────────────────────────────────────

    /** @test */
    public function usar_una_ram_la_instala_y_actualiza_la_ficha_del_equipo(): void
    {
        $componente = $this->entrarRam('8', 'DDR4', 'SN-RAM');
        $pieza = PiezaExtraida::where('identificador', 'SN-RAM')->firstOrFail();
        $caso = $this->crearCaso();

        Livewire::actingAs($this->admin)
            ->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->ram->id)
            ->set('componenteId', (string) $componente->id)
            ->set('piezaId', (string) $pieza->id)
            ->assertSee('Ficha del equipo')
            ->set('modo', 'agregar')
            ->call('usar')
            ->assertHasNoErrors();

        $this->assertSame(PiezaExtraida::ESTADO_INSTALADA, $pieza->fresh()->estado);
        $this->assertDatabaseHas('equipo_atributo_grupo_instancias', ['equipo_id' => $caso->equipo_id, 'atributo_id' => $this->ramLaptop->id, 'es_actual' => true]);
        $this->assertDatabaseHas('mantenimiento_componentes', ['mantenimiento_id' => $caso->id, 'pieza_id' => $pieza->id, 'estado' => 'Entregado']);
    }

    /** @test */
    public function reemplazar_una_ram_registra_la_que_sale(): void
    {
        $componente = $this->entrarRam('8', 'DDR4', 'SN-NUEVA');
        $pieza = PiezaExtraida::where('identificador', 'SN-NUEVA')->firstOrFail();
        $caso = $this->crearCaso();
        $vieja = EquipoAtributoGrupoInstancia::create([
            'equipo_id' => $caso->equipo_id, 'atributo_id' => $this->ramLaptop->id,
            'valores' => [$this->campo('Capacidad (GB)') => '4', $this->campo('Tipo') => 'DDR4'],
            'orden' => 0, 'es_actual' => true, 'creado_por' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->ram->id)
            ->set('componenteId', (string) $componente->id)
            ->set('piezaId', (string) $pieza->id)
            ->set('modo', 'sustituir')
            ->set('instanciaId', (string) $vieja->id)
            ->call('usar')
            ->assertHasNoErrors();

        $this->assertFalse($vieja->fresh()->es_actual);
        $saliente = PiezaExtraida::where('equipo_origen_id', $caso->equipo_id)->firstOrFail();
        $this->assertSame(PiezaExtraida::ESTADO_EN_ALMACEN, $saliente->estado);
        $this->assertSame('4', $saliente->valor_extraido[$this->campo('Capacidad (GB)')]);
    }

    /** @test */
    public function usar_una_pieza_sin_ficha_la_deja_en_el_equipo_con_su_serial(): void
    {
        $pantalla = ComponenteAlmacen::create([
            'empresa_id' => $this->empresa->id, 'familia_id' => $this->pantallas->id, 'nombre' => 'Pantalla 14" HD',
            'unidad' => 'unidad', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $this->admin->id,
        ]);
        $pieza = app(\App\Services\PiezaManualService::class)
            ->registrar($pantalla, 1, 'nuevo', $this->admin, seriales: ['PANT-001'])->first();
        $caso = $this->crearCaso();

        Livewire::actingAs($this->admin)
            ->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->pantallas->id)
            ->set('componenteId', (string) $pantalla->id)
            ->set('piezaId', (string) $pieza->id)
            ->assertDontSee('Ficha del equipo')
            ->call('usar')
            ->assertHasNoErrors();

        $this->assertSame(0, $pantalla->fresh()->stock_actual);
        $this->assertSame($caso->equipo_id, (int) $pieza->fresh()->equipo_destino_id);
        $this->assertDatabaseHas('mantenimiento_componentes', ['mantenimiento_id' => $caso->id, 'pieza_id' => $pieza->id]);
    }

    /** @test */
    public function a_granel_se_usa_por_cantidad_y_sin_stock_queda_pendiente(): void
    {
        $bisagra = ComponenteAlmacen::create([
            'empresa_id' => $this->empresa->id, 'familia_id' => $this->bisagras->id, 'nombre' => 'Bisagra HP 14"',
            'unidad' => 'unidad', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $this->admin->id,
        ]);
        $bisagra->registrarEntrada(3, $this->admin);
        $caso = $this->crearCaso();

        Livewire::actingAs($this->admin)
            ->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->bisagras->id)
            ->set('componenteId', (string) $bisagra->id)
            ->set('cantidad', 2)
            ->call('usar')
            ->assertHasNoErrors();

        $this->assertSame(1, $bisagra->fresh()->stock_actual);

        // Pide 5 y solo queda 1: queda pendiente.
        Livewire::actingAs($this->admin)
            ->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->bisagras->id)
            ->set('componenteId', (string) $bisagra->id)
            ->set('cantidad', 5)
            ->call('usar')
            ->assertHasNoErrors();

        $this->assertSame(1, $bisagra->fresh()->stock_actual);
        $this->assertSame('Pendiente', $caso->componentes()->latest('id')->first()->estado);
    }

    /** @test */
    public function se_puede_solicitar_algo_que_no_esta_en_el_almacen(): void
    {
        $caso = $this->crearCaso();

        Livewire::actingAs($this->admin)
            ->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->pantallas->id)
            ->set('componenteId', 'nueva')
            ->set('nuevoNombre', 'Pantalla 15.6" FHD')
            ->set('cantidad', 1)
            ->call('solicitar')
            ->assertHasNoErrors();

        $variante = ComponenteAlmacen::where('nombre', 'Pantalla 15.6" FHD')->firstOrFail();
        $this->assertSame($this->pantallas->id, $variante->familia_id);
        $this->assertSame('Pendiente', $caso->componentes()->first()->estado);
    }
}
