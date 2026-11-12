<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\AlmacenTable;
use App\Livewire\Almacen\ComponentePiezasModal;
use App\Livewire\Almacen\MovimientoStockModal;
use App\Livewire\Configuracion\Familias\FamiliaModal;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\FamiliaComponente;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\User;
use App\Services\ExtraccionPiezaService;
use App\Services\PiezaManualService;
use App\Services\VinculacionFamiliaService;
use Database\Seeders\EstadosEquiposSeeder;
use Database\Seeders\FamiliasComponentesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fase 2 — Almacén organizado por familias, con control por unidad o a granel.
 */
class AlmacenFamiliasTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $admin;
    private FamiliaComponente $bisagras;
    private FamiliaComponente $pantallas;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);

        $this->empresa = Empresa::create(['nombre' => 'Empresa A']);
        $this->admin   = User::factory()->create(['empresa_id' => $this->empresa->id]);
        $this->admin->assignRole('Administrador');

        $this->bisagras  = FamiliaComponente::create(['nombre' => 'Bisagras', 'control' => 'granel', 'campos' => []]);
        $this->pantallas = FamiliaComponente::create(['nombre' => 'Pantallas', 'control' => 'unidad', 'campos' => []]);
    }

    private function crearComponente(string $nombre, ?FamiliaComponente $familia = null, int $stock = 0): ComponenteAlmacen
    {
        return ComponenteAlmacen::create([
            'empresa_id'   => $this->empresa->id,
            'familia_id'   => $familia?->id,
            'nombre'       => $nombre,
            'unidad'       => 'unidad',
            'stock_actual' => $stock,
            'activo'       => true,
            'creado_por'   => $this->admin->id,
        ]);
    }

    private function crearCaso(): Mantenimiento
    {
        $categoria = CategoriaEquipo::create(['nombre' => 'Laptop ' . uniqid()]);
        $equipo = Equipo::forceCreate([
            'empresa_id'     => $this->empresa->id,
            'categoria_id'   => $categoria->id,
            'estado_id'      => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
            'codigo_interno' => 'EQ-' . uniqid(),
            'activo'         => true,
        ]);

        return Mantenimiento::create([
            'empresa_id' => $this->empresa->id, 'equipo_id' => $equipo->id, 'tipo' => 'Correctivo',
            'estado' => 'En reparación', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->toDateString(),
        ]);
    }

    // ─── Pasar a granel ────────────────────────────────────────────────────

    /** @test */
    public function al_pasar_a_granel_las_piezas_en_almacen_se_cuentan_por_cantidad_y_lo_usado_conserva_su_historial(): void
    {
        $componente = $this->crearComponente('Bisagra HP 14"');
        $piezas = app(PiezaManualService::class)->registrar($componente, 3, PiezaExtraida::CONDICION_NUEVO, $this->admin);

        // Una se usó en una reparación.
        $caso = $this->crearCaso();
        $caso->pedirComponente($componente->fresh(), 1, $this->admin);
        $usada = $piezas->first()->fresh();
        $this->assertSame(PiezaExtraida::ESTADO_INSTALADA, $usada->estado);

        $agrupadas = $componente->fresh()->asignarFamilia($this->bisagras, $this->admin);

        $componente->refresh();
        $this->assertSame(2, $agrupadas);
        $this->assertSame(2, $componente->stock_actual, 'El stock no cambia.');
        $this->assertSame(0, $componente->piezasEnAlmacen()->count());
        $this->assertSame(2, $componente->unidadesSinIdentidad());
        $this->assertSame(PiezaExtraida::ESTADO_INSTALADA, $usada->fresh()->estado, 'Lo usado conserva su historial.');
        $this->assertDatabaseHas('piezas_extraidas_movimientos', ['pieza_id' => $piezas[1]->id, 'tipo' => 'agrupada']);
    }

    /** @test */
    public function cambiar_una_familia_a_granel_agrupa_las_piezas_de_sus_componentes(): void
    {
        $componente = $this->crearComponente('Pantalla 14" HD', $this->pantallas);
        app(PiezaManualService::class)->registrar($componente, 2, PiezaExtraida::CONDICION_NUEVO, $this->admin);

        Livewire::actingAs($this->admin)
            ->test(FamiliaModal::class)
            ->call('abrirEditar', $this->pantallas->id)
            ->set('control', 'granel')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(0, $componente->piezasEnAlmacen()->count());
        $this->assertSame(2, $componente->fresh()->stock_actual);
    }

    // ─── Entradas y salidas ────────────────────────────────────────────────

    /** @test */
    public function una_entrada_a_granel_solo_suma_cantidad(): void
    {
        $componente = $this->crearComponente('Bisagra Dell 15"', $this->bisagras);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Almacen\RegistrarEntradaModal::class)
            ->call('abrir', $componente->id)
            ->set('cantidad', 10)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(10, $componente->fresh()->stock_actual);
        $this->assertSame(0, PiezaExtraida::where('componente_almacen_id', $componente->id)->count());

        // Y la salida no pide marcar piezas.
        Livewire::actingAs($this->admin)
            ->test(MovimientoStockModal::class)
            ->call('abrir', $componente->id)
            ->set('cantidad', 3)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(7, $componente->fresh()->stock_actual);
    }

    /** @test */
    public function una_entrada_por_unidad_registra_un_serial_por_linea(): void
    {
        $componente = $this->crearComponente('Pantalla 15.6" FHD', $this->pantallas);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Almacen\RegistrarEntradaModal::class)
            ->call('abrir', $componente->id)
            ->set('cantidad', 3)
            ->set('seriales', "SN-AAA\nSN-BBB")
            ->call('guardar')
            ->assertHasNoErrors();

        $ids = PiezaExtraida::where('componente_almacen_id', $componente->id)->orderBy('id')->pluck('identificador')->all();
        $this->assertSame(['SN-AAA', 'SN-BBB', "AUTO-{$componente->id}-001"], $ids);
    }

    /** @test */
    public function no_acepta_seriales_repetidos(): void
    {
        $componente = $this->crearComponente('Pantalla 13" HD', $this->pantallas);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Almacen\RegistrarEntradaModal::class)
            ->call('abrir', $componente->id)
            ->set('cantidad', 2)
            ->set('seriales', "SN-1\nSN-1")
            ->call('guardar')
            ->assertHasErrors('seriales');

        $this->assertSame(0, $componente->fresh()->stock_actual);
    }

    // ─── Familia automática ────────────────────────────────────────────────

    /** @test */
    public function una_pieza_de_una_caracteristica_con_familia_cae_en_esa_familia(): void
    {
        $this->seed(FamiliasComponentesSeeder::class);
        $ram = FamiliaComponente::where('nombre', 'RAM')->firstOrFail();

        $categoria = CategoriaEquipo::create(['nombre' => 'Desktop']);
        $atributo = AtributoEquipo::create(['categoria_id' => $categoria->id, 'nombre' => 'RAM', 'slug' => 'ram', 'tipo' => 'group', 'reutilizable' => true]);
        app(VinculacionFamiliaService::class)->vincularSinDatos($atributo, $ram);

        $campos = collect($ram->campos)->keyBy('nombre');
        $pieza = app(ExtraccionPiezaService::class)->registrarNueva(
            $this->empresa->id, $atributo->fresh(), [$campos['Capacidad (GB)']['id'] => '8', $campos['Tipo']['id'] => 'DDR4'], $this->admin,
        )->first();

        $this->assertSame($ram->id, $pieza->componenteAlmacen->familia_id);
    }

    // ─── Pantallas ─────────────────────────────────────────────────────────

    /** @test */
    public function el_almacen_se_muestra_agrupado_por_familia(): void
    {
        $this->crearComponente('Bisagra HP 14"', $this->bisagras, 12);
        $this->crearComponente('Pantalla 14" HD', $this->pantallas, 0);
        $this->crearComponente('Cosa vieja sin clasificar');

        Livewire::actingAs($this->admin)
            ->test(AlmacenTable::class)
            ->assertSeeInOrder(['Bisagras', 'A granel', 'Bisagra HP 14', 'Pantallas', 'Por unidad', 'Sin familia', 'Cosa vieja sin clasificar'])
            ->assertSee('Hay componentes sin familia');
    }

    /** @test */
    public function el_detalle_de_una_variante_a_granel_muestra_donde_se_uso(): void
    {
        $componente = $this->crearComponente('Bisagra HP 14"', $this->bisagras);
        $componente->registrarEntrada(5, $this->admin, 'Compra');
        $caso = $this->crearCaso();
        $caso->pedirComponente($componente->fresh(), 2, $this->admin);

        Livewire::actingAs($this->admin)
            ->test(ComponentePiezasModal::class)
            ->call('abrir', $componente->id)
            ->assertSee('dónde se usó')
            ->assertSee("Caso #{$caso->id}");
    }
}
