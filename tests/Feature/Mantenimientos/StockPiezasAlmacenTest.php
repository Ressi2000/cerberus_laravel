<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\MovimientoStockModal;
use App\Livewire\Mantenimientos\MantenimientoComponentesPanel;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\User;
use App\Services\ExtraccionPiezaService;
use App\Services\PiezaManualService;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * C1 — Toda salida de stock dice qué piezas con identidad salen, así que
 *      stock_actual y piezas "en almacén" siempre cuadran.
 * C2 — "Pedir componente" no toma piezas ligadas a una característica del
 *      equipo (esas se instalan desde el panel de Piezas), y las piezas
 *      manuales que entrega quedan registradas en la misma salida.
 */
class StockPiezasAlmacenTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private CategoriaEquipo $categoria;
    private User $analista;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->seed(EstadosEquiposSeeder::class);

        $this->empresa   = Empresa::create(['nombre' => 'Empresa A']);
        $this->categoria = CategoriaEquipo::create(['nombre' => 'Laptop']);

        $this->analista = User::factory()->create(['empresa_activa_id' => $this->empresa->id]);
        $this->analista->assignRole('Analista');
    }

    // ─── Fixtures ──────────────────────────────────────────────────────────

    private function crearComponente(int $stockSinIdentidad = 0): ComponenteAlmacen
    {
        return ComponenteAlmacen::create([
            'empresa_id'   => $this->empresa->id,
            'nombre'       => 'Pasta térmica ' . uniqid(),
            'unidad'       => 'unidad',
            'stock_actual' => $stockSinIdentidad,
            'activo'       => true,
            'creado_por'   => $this->analista->id,
        ]);
    }

    /** Piezas registradas a mano (sin característica del equipo). */
    private function agregarPiezasManuales(ComponenteAlmacen $componente, int $cantidad)
    {
        return app(PiezaManualService::class)->registrar(
            $componente, $cantidad, PiezaExtraida::CONDICION_NUEVO, $this->analista,
        );
    }

    /** Piezas ligadas a una característica del equipo (ej. RAM 8GB). Devuelve su componente. */
    private function crearComponenteConPiezasDeCaracteristica(int $cantidad): ComponenteAlmacen
    {
        $atributo = AtributoEquipo::create([
            'categoria_id' => $this->categoria->id,
            'nombre'       => 'RAM',
            'slug'         => 'ram-' . uniqid(),
            'tipo'         => 'string',
            'reutilizable' => true,
        ]);

        $piezas = app(ExtraccionPiezaService::class)->registrarNueva(
            $this->empresa->id, $atributo, ['valor' => '8GB'], $this->analista, $cantidad,
        );

        return $piezas->first()->componenteAlmacen;
    }

    private function crearCasoEnReparacion(): Mantenimiento
    {
        $equipo = Equipo::forceCreate([
            'empresa_id'        => $this->empresa->id,
            'categoria_id'      => $this->categoria->id,
            'estado_id'         => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
            'codigo_interno'    => 'EQ-' . uniqid(),
            'fecha_adquisicion' => now()->subYear()->toDateString(),
            'activo'            => true,
        ]);

        return Mantenimiento::create([
            'empresa_id'       => $this->empresa->id,
            'equipo_id'        => $equipo->id,
            'tipo'             => 'Correctivo',
            'estado'           => 'En reparación',
            'reportado_por_id' => $this->analista->id,
            'fecha_inicio'     => now()->toDateString(),
        ]);
    }

    private function assertCuadra(ComponenteAlmacen $componente): void
    {
        $componente->refresh();

        $this->assertGreaterThanOrEqual(
            $componente->piezasEnAlmacen()->count(),
            $componente->stock_actual,
            'Hay más piezas "en almacén" que stock.',
        );
    }

    // ─── C1: salidas manuales ──────────────────────────────────────────────

    /** @test */
    public function ajuste_sin_elegir_piezas_se_rechaza_si_todas_tienen_identidad(): void
    {
        $componente = $this->crearComponente();
        $this->agregarPiezasManuales($componente, 3);

        Livewire::actingAs($this->analista)
            ->test(MovimientoStockModal::class)
            ->call('abrir', $componente->id)
            ->set('tipo', 'Salida')
            ->set('cantidad', 1)
            ->call('guardar')
            ->assertHasErrors('piezaIds');

        $this->assertSame(3, $componente->fresh()->stock_actual);
        $this->assertSame(3, $componente->piezasEnAlmacen()->count());
    }

    /** @test */
    public function ajuste_eligiendo_la_pieza_la_marca_como_retirada(): void
    {
        $componente = $this->crearComponente();
        $piezas = $this->agregarPiezasManuales($componente, 3);
        $elegida = $piezas->first();

        Livewire::actingAs($this->analista)
            ->test(MovimientoStockModal::class)
            ->call('abrir', $componente->id)
            ->set('tipo', 'Salida')
            ->set('cantidad', 1)
            ->set('motivo', 'Se perdió')
            ->set('piezaIds', [$elegida->id])
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(2, $componente->fresh()->stock_actual);
        $this->assertSame(PiezaExtraida::ESTADO_RETIRADA, $elegida->fresh()->estado);
        $this->assertDatabaseHas('piezas_extraidas_movimientos', [
            'pieza_id' => $elegida->id,
            'tipo'     => 'salida_ajuste',
        ]);
        $this->assertCuadra($componente);
    }

    /** @test */
    public function ajuste_usa_primero_las_unidades_sin_identidad(): void
    {
        $componente = $this->crearComponente(2);
        $this->agregarPiezasManuales($componente, 1); // stock 3: 2 sin identidad + 1 pieza

        Livewire::actingAs($this->analista)
            ->test(MovimientoStockModal::class)
            ->call('abrir', $componente->id)
            ->set('tipo', 'Salida')
            ->set('cantidad', 2)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(1, $componente->fresh()->stock_actual);
        $this->assertSame(1, $componente->piezasEnAlmacen()->count());
    }

    /** @test */
    public function no_se_pueden_marcar_mas_piezas_que_la_cantidad(): void
    {
        $componente = $this->crearComponente();
        $piezas = $this->agregarPiezasManuales($componente, 2);

        Livewire::actingAs($this->analista)
            ->test(MovimientoStockModal::class)
            ->call('abrir', $componente->id)
            ->set('tipo', 'Salida')
            ->set('cantidad', 1)
            ->set('piezaIds', $piezas->pluck('id')->all())
            ->call('guardar')
            ->assertHasErrors('piezaIds');

        $this->assertSame(2, $componente->fresh()->stock_actual);
    }

    /** @test */
    public function danado_con_varias_piezas_las_envia_todas_al_deposito(): void
    {
        $componente = $this->crearComponente();
        $piezas = $this->agregarPiezasManuales($componente, 3);
        $deposito = Deposito::create([
            'empresa_id' => $this->empresa->id,
            'nombre'     => 'Principal',
            'activo'     => true,
            'creado_por' => $this->analista->id,
        ]);

        Livewire::actingAs($this->analista)
            ->test(MovimientoStockModal::class)
            ->call('abrir', $componente->id)
            ->set('tipo', 'Salida')
            ->set('danado', true)
            ->set('cantidad', 2)
            ->set('depositoId', $deposito->id)
            ->set('piezaIds', $piezas->take(2)->pluck('id')->all())
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(1, $componente->fresh()->stock_actual);
        foreach ($piezas->take(2) as $pieza) {
            $this->assertSame(PiezaExtraida::ESTADO_EN_DEPOSITO, $pieza->fresh()->estado);
            $this->assertSame($deposito->id, (int) $pieza->fresh()->deposito_id);
        }
        $this->assertCuadra($componente);
    }

    /** @test */
    public function una_salida_nunca_deja_el_stock_negativo(): void
    {
        $componente = $this->crearComponente(1);

        $this->expectException(\InvalidArgumentException::class);
        $componente->registrarSalida(2, $this->analista);
    }

    // ─── C2: pedir componente desde un caso ────────────────────────────────

    /** @test */
    public function pedir_no_toma_piezas_ligadas_a_una_caracteristica(): void
    {
        $componente = $this->crearComponenteConPiezasDeCaracteristica(2);
        $caso = $this->crearCasoEnReparacion();

        Livewire::actingAs($this->analista)
            ->test(MantenimientoComponentesPanel::class, ['mantenimientoId' => $caso->id])
            ->set('componente_id', (string) $componente->id)
            ->set('cantidad', 1)
            ->call('pedir');

        $this->assertSame('Pendiente', $caso->componentes()->first()->estado);
        $this->assertSame(2, $componente->fresh()->stock_actual);
        $this->assertSame(2, $componente->piezasEnAlmacen()->count());
        $this->assertSame(0, PiezaExtraida::where('estado', PiezaExtraida::ESTADO_INSTALADA)->count());
    }

    /** @test */
    public function pedir_usa_primero_unidades_sin_identidad_y_luego_piezas_manuales_antiguas(): void
    {
        $componente = $this->crearComponente(1);
        $piezas = $this->agregarPiezasManuales($componente, 2); // stock 3: 1 sin identidad + 2 piezas
        $caso = $this->crearCasoEnReparacion();

        $caso->pedirComponente($componente, 2, $this->analista);

        $this->assertSame(1, $componente->fresh()->stock_actual);

        $instaladas = PiezaExtraida::where('estado', PiezaExtraida::ESTADO_INSTALADA)->get();
        $this->assertCount(1, $instaladas);
        $this->assertSame($piezas->first()->id, $instaladas->first()->id);
        $this->assertSame($caso->equipo_id, (int) $instaladas->first()->equipo_destino_id);
        $this->assertCuadra($componente);
    }

    /** @test */
    public function entregar_pendiente_mantiene_el_stock_cuadrado(): void
    {
        $componente = $this->crearComponente();
        $caso = $this->crearCasoEnReparacion();

        $item = $caso->pedirComponente($componente, 2, $this->analista);
        $this->assertSame('Pendiente', $item->estado);

        $this->agregarPiezasManuales($componente, 2);
        $this->assertTrue($caso->entregarComponentePendiente($item, $this->analista));

        $this->assertSame(0, $componente->fresh()->stock_actual);
        $this->assertSame(0, $componente->piezasEnAlmacen()->count());
        $this->assertSame(2, PiezaExtraida::where('estado', PiezaExtraida::ESTADO_INSTALADA)->count());
    }

    /** @test */
    public function instalar_una_pieza_con_caracteristica_descuenta_esa_pieza_y_actualiza_el_equipo(): void
    {
        $componente = $this->crearComponenteConPiezasDeCaracteristica(2);
        $pieza = $componente->piezasEnAlmacen()->first();
        $caso = $this->crearCasoEnReparacion();

        app(\App\Services\SustitucionPiezaService::class)->instalar(
            $pieza, $caso->equipo, \App\Services\SustitucionPiezaService::MODO_SUSTITUIR, $this->analista, $caso,
        );

        $this->assertSame(1, $componente->fresh()->stock_actual);
        $this->assertSame(PiezaExtraida::ESTADO_INSTALADA, $pieza->fresh()->estado);
        $this->assertDatabaseHas('equipo_atributo_valores', [
            'equipo_id'   => $caso->equipo_id,
            'atributo_id' => $pieza->atributo_id,
            'valor'       => '8GB',
            'es_actual'   => true,
        ]);
        $this->assertCuadra($componente);

        // La misma pieza no se puede instalar dos veces.
        $this->expectException(\InvalidArgumentException::class);
        app(\App\Services\SustitucionPiezaService::class)->instalar(
            $pieza->fresh(), $caso->equipo, \App\Services\SustitucionPiezaService::MODO_SUSTITUIR, $this->analista, $caso,
        );
    }

    // ─── Comando de verificación de descuadres ─────────────────────────────

    /** @test */
    public function el_comando_detecta_y_corrige_descuadres_existentes(): void
    {
        $componente = $this->crearComponente();
        $piezas = $this->agregarPiezasManuales($componente, 2);
        // Simula un descuadre de antes de la corrección: el stock bajó sin marcar pieza.
        $componente->update(['stock_actual' => 1]);

        $this->artisan('cerberus:verificar-stock-almacen')->assertExitCode(1);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->artisan('cerberus:verificar-stock-almacen', [
            '--retirar' => (string) $piezas->first()->id,
            '--usuario' => (string) $admin->id,
        ])->assertExitCode(0);

        $this->assertSame(PiezaExtraida::ESTADO_RETIRADA, $piezas->first()->fresh()->estado);
        $this->artisan('cerberus:verificar-stock-almacen')->assertExitCode(0);
    }

    /** @test */
    public function el_comando_repone_al_stock_una_pieza_que_si_esta(): void
    {
        $componente = $this->crearComponente();
        $piezas = $this->agregarPiezasManuales($componente, 1);
        $componente->update(['stock_actual' => 0]); // descuadre: 1 pieza, 0 stock

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->artisan('cerberus:verificar-stock-almacen', [
            '--reponer' => (string) $piezas->first()->id,
            '--usuario' => (string) $admin->id,
        ])->assertExitCode(0);

        $this->assertSame(1, $componente->fresh()->stock_actual);
        $this->assertSame(PiezaExtraida::ESTADO_EN_ALMACEN, $piezas->first()->fresh()->estado);
        $this->assertDatabaseHas('movimientos_componentes', [
            'componente_id'     => $componente->id,
            'tipo'              => 'Entrada',
            'motivo'            => 'Corrección de descuadre',
            'registrado_por_id' => $admin->id,
        ]);
        $this->artisan('cerberus:verificar-stock-almacen')->assertExitCode(0);
    }

    /** @test */
    public function el_comando_no_retira_mas_piezas_de_las_que_sobran(): void
    {
        $componente = $this->crearComponente();
        $piezas = $this->agregarPiezasManuales($componente, 2);
        $componente->update(['stock_actual' => 1]);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->artisan('cerberus:verificar-stock-almacen', [
            '--retirar' => $piezas->pluck('id')->implode(','),
            '--usuario' => (string) $admin->id,
        ])->assertExitCode(1);

        $this->assertSame(2, $componente->piezasEnAlmacen()->count());
    }
}
