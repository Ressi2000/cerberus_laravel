<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\AlmacenTable;
use App\Livewire\Almacen\ComponentePiezasModal;
use App\Livewire\Almacen\PiezaFichaModal;
use App\Livewire\Almacen\RegistrarEntradaModal;
use App\Livewire\Mantenimientos\MantenimientoPiezasPanel;
use App\Livewire\Mantenimientos\UsarDelAlmacenModal;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\FamiliaComponente;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use App\Services\VinculacionFamiliaService;
use Database\Seeders\EstadosEquiposSeeder;
use Database\Seeders\FamiliasComponentesSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fase 4 — Traza: ficha de cada pieza con su recorrido, historial de la
 * variante, búsqueda por serial, y la misma pieza física conserva su id y
 * su serial aunque entre y salga de varios equipos.
 */
class TrazaPiezasTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $admin;
    private FamiliaComponente $ram;
    private AtributoEquipo $ramLaptop;
    private CategoriaEquipo $laptop;

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
        $this->laptop    = CategoriaEquipo::create(['nombre' => 'Laptop']);
        $this->ramLaptop = AtributoEquipo::create(['categoria_id' => $this->laptop->id, 'nombre' => 'RAM', 'slug' => 'ram', 'tipo' => 'group', 'reutilizable' => true]);
        app(VinculacionFamiliaService::class)->vincularSinDatos($this->ramLaptop, $this->ram);
    }

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

    private function entrarRam(string $seriales): ComponenteAlmacen
    {
        Livewire::actingAs($this->admin)
            ->test(RegistrarEntradaModal::class)
            ->call('abrir')
            ->set('empresa_id', (string) $this->empresa->id)
            ->set('familiaId', (string) $this->ram->id)
            ->set('valores.' . $this->campo('Capacidad (GB)'), '8')
            ->set('valores.' . $this->campo('Tipo'), 'DDR4')
            ->set('valores.' . $this->campo('Formato'), 'SO-DIMM (laptop)')
            ->set('cantidad', count(explode("\n", $seriales)))
            ->set('seriales', $seriales)
            ->call('guardar')
            ->assertHasNoErrors();

        return ComponenteAlmacen::where('familia_id', $this->ram->id)->firstOrFail();
    }

    private function instalar(Mantenimiento $caso, ComponenteAlmacen $componente, string $serial): PiezaExtraida
    {
        $pieza = PiezaExtraida::where('identificador', $serial)->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->ram->id)
            ->set('componenteId', (string) $componente->id)
            ->set('piezaId', (string) $pieza->id)
            ->set('modo', 'agregar')
            ->call('usar')
            ->assertHasNoErrors();

        return $pieza->fresh();
    }

    /** Índice de la RAM en la lista "Pieza a retirar" del panel del caso. */
    private function indiceRam(Mantenimiento $caso, int $cual = 0): int
    {
        $panel = Livewire::actingAs($this->admin)->test(MantenimientoPiezasPanel::class, ['mantenimientoId' => $caso->id]);

        return collect($panel->instance()->candidatosRetiro)
            ->filter(fn ($c) => $c['atributo_id'] === $this->ramLaptop->id)
            ->keys()
            ->get($cual);
    }

    // ─── Ficha de la pieza ─────────────────────────────────────────────────

    /** @test */
    public function la_ficha_muestra_el_serial_la_ubicacion_y_el_recorrido(): void
    {
        $componente = $this->entrarRam('SN-FICHA');
        $caso = $this->crearCaso();
        $pieza = $this->instalar($caso, $componente, 'SN-FICHA');

        Livewire::actingAs($this->admin)
            ->test(PiezaFichaModal::class)
            ->call('abrir', $pieza->id)
            ->assertSee('Serial SN-FICHA')
            ->assertSee('Instalada en')
            ->assertSee($caso->equipo->codigo_interno)
            ->assertSee('Capacidad (GB)')
            ->assertSeeInOrder(['move_to_inbox', 'Instalada en un equipo', "Caso #{$caso->id}"]);
    }

    /** @test */
    public function no_se_puede_ver_la_ficha_de_una_pieza_de_otra_empresa(): void
    {
        $this->entrarRam('SN-AJENA');
        $pieza = PiezaExtraida::where('identificador', 'SN-AJENA')->firstOrFail();

        $otra = Empresa::create(['nombre' => 'Empresa B']);
        $analista = User::factory()->create(['empresa_id' => $otra->id, 'empresa_activa_id' => $otra->id]);
        $analista->assignRole('Analista');

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($analista)->test(PiezaFichaModal::class)->call('abrir', $pieza->id);
    }

    // ─── La misma pieza a lo largo de su vida ──────────────────────────────

    /** @test */
    public function al_retirar_una_pieza_que_vino_del_almacen_sigue_siendo_la_misma(): void
    {
        $componente = $this->entrarRam('SN-VIAJERA');
        $caso = $this->crearCaso();
        $pieza = $this->instalar($caso, $componente, 'SN-VIAJERA');
        $totalPiezas = PiezaExtraida::count();

        Livewire::actingAs($this->admin)
            ->test(MantenimientoPiezasPanel::class, ['mantenimientoId' => $caso->id])
            ->call('abrirRetirar')
            ->set('retirarIndex', $this->indiceRam($caso))
            ->assertSee('seguirá con su mismo historial')
            ->set('retirarDestino', PiezaExtraida::ESTADO_EN_ALMACEN)
            ->call('retirar')
            ->assertHasNoErrors();

        $this->assertSame($totalPiezas, PiezaExtraida::count(), 'No se crea otra pieza.');
        $pieza->refresh();
        $this->assertSame('SN-VIAJERA', $pieza->identificador);
        $this->assertSame(PiezaExtraida::ESTADO_EN_ALMACEN, $pieza->estado);
        $this->assertSame(PiezaExtraida::CONDICION_REUTILIZADO, $pieza->condicion);
        $this->assertNull($pieza->equipo_destino_id);
        $this->assertSame(1, $componente->fresh()->stock_actual);
        $this->assertSame(
            [PiezaExtraidaMovimiento::TIPO_INGRESO_ALMACEN, PiezaExtraidaMovimiento::TIPO_INSTALACION, PiezaExtraidaMovimiento::TIPO_DESINSTALACION, PiezaExtraidaMovimiento::TIPO_INGRESO_ALMACEN],
            $pieza->movimientos()->reorder()->orderBy('id')->pluck('tipo')->all(),
        );
    }

    /** @test */
    public function con_dos_piezas_iguales_instaladas_se_elige_cual_sale_por_su_serial(): void
    {
        $componente = $this->entrarRam("SN-A\nSN-B");
        $caso = $this->crearCaso();
        $this->instalar($caso, $componente, 'SN-A');
        $this->instalar($caso, $componente, 'SN-B');

        $panel = Livewire::actingAs($this->admin)
            ->test(MantenimientoPiezasPanel::class, ['mantenimientoId' => $caso->id])
            ->call('abrirRetirar')
            ->set('retirarIndex', $this->indiceRam($caso))
            ->assertSee('¿Cuál de las iguales sale?')
            ->call('retirar')
            ->assertHasErrors('retirarPiezaId');

        $b = PiezaExtraida::where('identificador', 'SN-B')->firstOrFail();
        $panel->set('retirarPiezaId', (string) $b->id)->call('retirar')->assertHasNoErrors();

        $this->assertSame(PiezaExtraida::ESTADO_EN_ALMACEN, $b->fresh()->estado);
        $this->assertSame(PiezaExtraida::ESTADO_INSTALADA, PiezaExtraida::where('identificador', 'SN-A')->value('estado'));
    }

    // ─── Historial de la variante y búsqueda ───────────────────────────────

    /** @test */
    public function el_historial_de_la_variante_incluye_las_piezas_ya_usadas(): void
    {
        $componente = $this->entrarRam("SN-USADA\nSN-STOCK");
        $this->instalar($this->crearCaso(), $componente, 'SN-USADA');

        $modal = Livewire::actingAs($this->admin)
            ->test(ComponentePiezasModal::class)
            ->call('abrir', $componente->id)
            ->assertSee('SN-STOCK')
            ->assertDontSee('SN-USADA')
            ->call('verPestana', 'piezas')
            ->assertSee('SN-USADA')
            ->assertSee('SN-STOCK');

        $usada = PiezaExtraida::where('identificador', 'SN-USADA')->first();
        $modal->call('verPieza', $usada->id)->assertDispatched('openPiezaFicha', piezaId: $usada->id);
    }

    /** @test */
    public function buscar_un_serial_en_almacen_encuentra_la_pieza_este_donde_este(): void
    {
        $componente = $this->entrarRam("SN-BUSCADA\nSN-OTRA");
        $this->instalar($this->crearCaso(), $componente, 'SN-BUSCADA');

        Livewire::actingAs($this->admin)
            ->test(AlmacenTable::class)
            ->set('search', 'BUSCADA')
            ->assertSee('Piezas con ese serial o código')
            ->assertSee('Serial SN-BUSCADA')
            ->assertDontSee('Serial SN-OTRA')
            ->assertSee($componente->nombre);
    }

    /** @test */
    public function el_historial_del_equipo_enlaza_a_la_ficha_de_sus_piezas(): void
    {
        $componente = $this->entrarRam('SN-EQUIPO');
        $caso = $this->crearCaso();
        $this->instalar($caso, $componente, 'SN-EQUIPO');

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Equipos\HistorialEquipo::class, ['equipo' => $caso->equipo])
            ->assertSee('Piezas del almacén instaladas en este equipo')
            ->assertSee('Serial SN-EQUIPO')
            ->assertSee('Nueva, del almac');
    }

    // ─── Hallazgos de la prueba de uso en el navegador ─────────────────────

    /** @test */
    public function entregar_una_solicitud_pendiente_anota_cuales_piezas_salieron(): void
    {
        $pantallas = FamiliaComponente::create(['nombre' => 'Pantallas', 'control' => 'unidad', 'campos' => []]);
        $variante = ComponenteAlmacen::create([
            'empresa_id' => $this->empresa->id, 'familia_id' => $pantallas->id, 'nombre' => 'Pantalla 14" HD',
            'unidad' => 'unidad', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $this->admin->id,
        ]);
        $caso = $this->crearCaso();
        $item = $caso->pedirComponente($variante, 2, $this->admin);
        $this->assertSame('Pendiente', $item->estado);

        app(\App\Services\PiezaManualService::class)->registrar($variante, 2, PiezaExtraida::CONDICION_NUEVO, $this->admin, seriales: ['PAN-1', 'PAN-2']);
        $this->assertTrue($caso->entregarComponentePendiente($item->fresh(), $this->admin));

        $renglones = $caso->componentes()->with('pieza')->orderBy('id')->get();
        $this->assertSame(['PAN-1', 'PAN-2'], $renglones->pluck('pieza.identificador')->all(), 'Un renglón por pieza, con su serial.');
        $this->assertSame([1, 1], $renglones->pluck('cantidad_requerida')->map(fn ($n) => (int) $n)->all());
        $this->assertTrue($renglones->every(fn ($r) => $r->estado === 'Entregado'));
    }

    /** @test */
    public function al_reemplazar_se_puede_anotar_el_serial_de_la_pieza_que_sale(): void
    {
        $componente = $this->entrarRam('SN-ENTRA');
        $pieza = PiezaExtraida::where('identificador', 'SN-ENTRA')->firstOrFail();
        $caso = $this->crearCaso();
        $vieja = \App\Models\EquipoAtributoGrupoInstancia::create([
            'equipo_id' => $caso->equipo_id, 'atributo_id' => $this->ramLaptop->id,
            'valores' => [$this->campo('Capacidad (GB)') => '4', $this->campo('Tipo') => 'DDR3'],
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
            ->set('serialSaliente', '  SN-SALE-77 ')
            ->call('usar')
            ->assertHasNoErrors();

        $saliente = PiezaExtraida::where('equipo_origen_id', $caso->equipo_id)->firstOrFail();
        $this->assertSame('SN-SALE-77', $saliente->identificador);
        $this->assertSame(PiezaExtraida::CONDICION_REUTILIZADO, $saliente->condicion);
    }

    /** @test */
    public function en_una_salida_por_unidad_la_cantidad_es_la_de_las_piezas_marcadas(): void
    {
        $componente = $this->entrarRam("SN-S1\nSN-S2\nSN-S3");
        $ids = PiezaExtraida::whereIn('identificador', ['SN-S1', 'SN-S3'])->pluck('id')->all();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Almacen\MovimientoStockModal::class)
            ->call('abrir', $componente->id)
            ->assertDontSee('Ej: 10')
            ->call('guardar')
            ->assertHasErrors('piezaIds')
            ->set('piezaIds', $ids)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(1, $componente->fresh()->stock_actual);
        $this->assertSame(PiezaExtraida::ESTADO_EN_ALMACEN, PiezaExtraida::where('identificador', 'SN-S2')->value('estado'));
    }

    /** @test */
    public function una_pieza_que_no_esta_en_la_ficha_tambien_se_puede_retirar(): void
    {
        $pantallas = FamiliaComponente::create(['nombre' => 'Pantallas', 'control' => 'unidad', 'campos' => []]);
        $variante = ComponenteAlmacen::create([
            'empresa_id' => $this->empresa->id, 'familia_id' => $pantallas->id, 'nombre' => 'Pantalla 14" HD',
            'unidad' => 'unidad', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $this->admin->id,
        ]);
        $pieza = app(\App\Services\PiezaManualService::class)
            ->registrar($variante, 1, PiezaExtraida::CONDICION_NUEVO, $this->admin, seriales: ['PAN-77'])->first();
        $caso = $this->crearCaso();
        $caso->usarPieza($pieza, $this->admin);
        $this->assertSame(0, $variante->fresh()->stock_actual);

        $panel = Livewire::actingAs($this->admin)->test(MantenimientoPiezasPanel::class, ['mantenimientoId' => $caso->id]);
        $indice = collect($panel->instance()->candidatosRetiro)->search(fn ($c) => $c['pieza_id'] === $pieza->id);
        $this->assertNotFalse($indice, 'La pantalla aparece para retirar.');

        $panel->call('abrirRetirar')
            ->assertSee('Serial PAN-77')
            ->set('retirarIndex', $indice)
            ->set('retirarDestino', PiezaExtraida::ESTADO_EN_ALMACEN)
            ->call('retirar')
            ->assertHasNoErrors();

        $pieza->refresh();
        $this->assertSame(PiezaExtraida::ESTADO_EN_ALMACEN, $pieza->estado);
        $this->assertSame(PiezaExtraida::CONDICION_REUTILIZADO, $pieza->condicion);
        $this->assertSame(1, $variante->fresh()->stock_actual);
        $this->assertSame(PiezaExtraidaMovimiento::TIPO_DESINSTALACION, $pieza->movimientos()->reorder()->orderByDesc('id')->skip(1)->value('tipo'));
    }

    /** @test */
    public function avisa_si_la_categoria_del_equipo_no_tiene_la_familia_vinculada(): void
    {
        $componente = $this->entrarRam('SN-SIN-VINCULO');
        $pieza = PiezaExtraida::where('identificador', 'SN-SIN-VINCULO')->firstOrFail();

        // Un Desktop cuya RAM todavía no usa la familia.
        $desktop = CategoriaEquipo::create(['nombre' => 'Desktop']);
        AtributoEquipo::create(['categoria_id' => $desktop->id, 'nombre' => 'RAM (GB)', 'slug' => 'ram-gb', 'tipo' => 'group', 'reutilizable' => true]);
        $equipo = Equipo::forceCreate([
            'empresa_id' => $this->empresa->id, 'categoria_id' => $desktop->id,
            'estado_id' => EstadoEquipo::where('nombre', 'En reparación')->value('id'),
            'codigo_interno' => 'EQ-DSK', 'activo' => true,
        ]);
        $caso = Mantenimiento::create([
            'empresa_id' => $this->empresa->id, 'equipo_id' => $equipo->id, 'tipo' => 'Correctivo',
            'estado' => 'En reparación', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->toDateString(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->ram->id)
            ->set('componenteId', (string) $componente->id)
            ->set('piezaId', (string) $pieza->id)
            ->assertSee('no se va a actualizar')
            ->assertDontSee('Ficha del equipo');
    }

    /** @test */
    public function lo_instalado_antes_de_vincular_la_familia_se_puede_retirar_despues(): void
    {
        $componente = $this->entrarRam('SN-ANTES');
        $pieza = PiezaExtraida::where('identificador', 'SN-ANTES')->firstOrFail();

        // Desktop con RAM todavía sin vincular: la pieza se instala sin tocar la ficha.
        $desktop = CategoriaEquipo::create(['nombre' => 'Desktop']);
        $ramDesktop = AtributoEquipo::create(['categoria_id' => $desktop->id, 'nombre' => 'RAM (GB)', 'slug' => 'ram-gb', 'tipo' => 'group', 'reutilizable' => true]);
        $equipo = Equipo::forceCreate([
            'empresa_id' => $this->empresa->id, 'categoria_id' => $desktop->id,
            'estado_id' => EstadoEquipo::where('nombre', 'En reparación')->value('id'),
            'codigo_interno' => 'EQ-DSK2', 'activo' => true,
        ]);
        $caso = Mantenimiento::create([
            'empresa_id' => $this->empresa->id, 'equipo_id' => $equipo->id, 'tipo' => 'Correctivo',
            'estado' => 'En reparación', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->toDateString(),
        ]);
        Livewire::actingAs($this->admin)->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->ram->id)
            ->set('componenteId', (string) $componente->id)
            ->set('piezaId', (string) $pieza->id)
            ->call('usar')->assertHasNoErrors();

        // Después se vincula la RAM de Desktop a la familia.
        app(VinculacionFamiliaService::class)->vincularSinDatos($ramDesktop, $this->ram);

        $candidatos = Livewire::actingAs($this->admin)
            ->test(MantenimientoPiezasPanel::class, ['mantenimientoId' => $caso->id])
            ->instance()->candidatosRetiro;

        $this->assertContains($pieza->id, array_column($candidatos, 'pieza_id'), 'Sigue apareciendo para retirar.');
    }

    /** @test */
    public function una_pieza_que_esta_en_la_ficha_no_aparece_repetida_para_retirar(): void
    {
        $componente = $this->entrarRam('SN-EN-FICHA');
        $caso = $this->crearCaso();
        $this->instalar($caso, $componente, 'SN-EN-FICHA');

        $candidatos = Livewire::actingAs($this->admin)
            ->test(MantenimientoPiezasPanel::class, ['mantenimientoId' => $caso->id])
            ->instance()->candidatosRetiro;

        $this->assertSame([], array_values(array_filter(array_column($candidatos, 'pieza_id'))), 'Sale por la ficha, no como pieza suelta.');
        $this->assertCount(1, array_filter($candidatos, fn ($c) => $c['atributo_id'] === $this->ramLaptop->id));
    }

    /** @test */
    public function una_solicitud_pendiente_de_ram_se_entrega_eligiendo_la_pieza_y_actualiza_la_ficha(): void
    {
        $caso = $this->crearCaso();

        // Se solicita una RAM que no hay.
        Livewire::actingAs($this->admin)->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id)
            ->set('familiaId', (string) $this->ram->id)
            ->set('componenteId', 'nueva')
            ->set('valores.' . $this->campo('Capacidad (GB)'), '8')
            ->set('valores.' . $this->campo('Tipo'), 'DDR3')
            ->set('valores.' . $this->campo('Formato'), 'SO-DIMM (laptop)')
            ->call('solicitar')->assertHasNoErrors();
        $pendiente = $caso->componentes()->firstOrFail();
        $variante = $pendiente->componente;

        // Llega al almacén.
        Livewire::actingAs($this->admin)->test(RegistrarEntradaModal::class)
            ->call('abrir', $variante->id)->set('seriales', 'SN-LLEGO')->call('guardar')->assertHasNoErrors();

        // La bandeja la manda al caso; en el caso, "Entregar ahora" abre Usar del almacén.
        $this->assertTrue(Livewire::actingAs($this->admin)->test(\App\Livewire\Almacen\SolicitudesPendientes::class)
            ->instance()->solicitudes->first()->en_el_caso);
        Livewire::actingAs($this->admin)->test(\App\Livewire\Mantenimientos\MantenimientoComponentesPanel::class, ['mantenimientoId' => $caso->id])
            ->call('entregarPendiente', $pendiente->id)
            ->assertDispatched('openUsarDelAlmacen', mantenimientoId: $caso->id, pendienteId: $pendiente->id);

        $pieza = PiezaExtraida::where('identificador', 'SN-LLEGO')->firstOrFail();
        Livewire::actingAs($this->admin)->test(UsarDelAlmacenModal::class)
            ->call('abrir', $caso->id, $pendiente->id)
            ->assertSet('componenteId', (string) $variante->id)
            ->assertSee('Entregando la solicitud pendiente')
            ->assertSee('1 en stock')
            ->set('piezaId', (string) $pieza->id)
            ->set('modo', 'agregar')
            ->call('usar')->assertHasNoErrors();

        $this->assertSame(1, $caso->componentes()->count(), 'La solicitud se completa; no se agrega otro renglón.');
        $this->assertSame('Entregado', $pendiente->fresh()->estado);
        $this->assertSame($pieza->id, $pendiente->fresh()->pieza_id);
        $this->assertDatabaseHas('equipo_atributo_grupo_instancias', ['equipo_id' => $caso->equipo_id, 'atributo_id' => $this->ramLaptop->id, 'es_actual' => true]);
        $this->assertFalse((bool) $caso->fresh()->esperando_componente);
    }

    /** @test */
    public function lo_usado_que_se_retiro_despues_queda_marcado(): void
    {
        $componente = $this->entrarRam('SN-IDA-Y-VUELTA');
        $caso = $this->crearCaso();
        $this->instalar($caso, $componente, 'SN-IDA-Y-VUELTA');

        Livewire::actingAs($this->admin)->test(MantenimientoPiezasPanel::class, ['mantenimientoId' => $caso->id])
            ->call('abrirRetirar')
            ->set('retirarIndex', $this->indiceRam($caso))
            ->call('retirar')->assertHasNoErrors()
            ->assertDispatched('componenteRefrescar');

        Livewire::actingAs($this->admin)->test(\App\Livewire\Mantenimientos\MantenimientoComponentesPanel::class, ['mantenimientoId' => $caso->id])
            ->assertSee('Retirada')
            ->assertSee('Se retiró del equipo después');
    }

    // ─── Traslado entre almacenes de empresas y piezas en depósito ─────────

    /** @test */
    public function se_trasladan_piezas_al_almacen_de_otra_empresa_y_siguen_siendo_las_mismas(): void
    {
        $origen = $this->entrarRam("SN-T1\nSN-T2");
        $otra = Empresa::create(['nombre' => 'Fenix']);
        $pieza = PiezaExtraida::where('identificador', 'SN-T1')->firstOrFail();

        Livewire::actingAs($this->admin)->test(\App\Livewire\Almacen\TrasladoAlmacenModal::class)
            ->call('abrir', $origen->id)
            ->assertSee('SN-T2')
            ->set('empresaDestinoId', (string) $otra->id)
            ->set('piezaIds', [$pieza->id])
            ->set('motivo', 'Para el caso de Fenix que espera RAM')
            ->call('guardar')
            ->assertHasNoErrors();

        $destino = ComponenteAlmacen::where('empresa_id', $otra->id)->firstOrFail();
        $this->assertSame($origen->nombre, $destino->nombre);
        $this->assertSame($origen->familia_id, $destino->familia_id);
        $this->assertSame(1, $origen->fresh()->stock_actual);
        $this->assertSame(1, $destino->stock_actual);

        $pieza->refresh();
        $this->assertSame($otra->id, (int) $pieza->empresa_id);
        $this->assertSame($destino->id, (int) $pieza->componente_almacen_id);
        $this->assertSame(PiezaExtraida::ESTADO_EN_ALMACEN, $pieza->estado);
        $this->assertSame(PiezaExtraidaMovimiento::TIPO_TRASLADO_ALMACEN, $pieza->movimientos()->reorder()->orderByDesc('id')->value('tipo'));
        $this->assertDatabaseHas('movimientos_componentes', ['componente_id' => $origen->id, 'tipo' => 'Salida', 'motivo' => 'Traslado a Fenix']);
        $this->assertDatabaseHas('movimientos_componentes', ['componente_id' => $destino->id, 'tipo' => 'Entrada', 'motivo' => 'Traslado desde Empresa A']);
    }

    /** @test */
    public function a_granel_se_traslada_por_cantidad_y_solo_el_administrador_puede(): void
    {
        $bisagras = FamiliaComponente::create(['nombre' => 'Bisagras', 'control' => 'granel', 'campos' => []]);
        $origen = ComponenteAlmacen::create([
            'empresa_id' => $this->empresa->id, 'familia_id' => $bisagras->id, 'nombre' => 'Bisagra HP',
            'unidad' => 'par', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $this->admin->id,
        ]);
        $origen->registrarEntrada(10, $this->admin);
        $otra = Empresa::create(['nombre' => 'Fenix']);

        Livewire::actingAs($this->admin)->test(\App\Livewire\Almacen\TrasladoAlmacenModal::class)
            ->call('abrir', $origen->id)
            ->set('empresaDestinoId', (string) $otra->id)
            ->set('sinSerial', 4)
            ->call('guardar')->assertHasErrors('motivo')
            ->set('motivo', 'Fenix se quedó sin bisagras para el caso #12')
            ->call('guardar')->assertHasNoErrors();

        $this->assertSame(6, $origen->fresh()->stock_actual);
        $this->assertSame(4, ComponenteAlmacen::where('empresa_id', $otra->id)->value('stock_actual'));

        $analista = User::factory()->create(['empresa_id' => $this->empresa->id, 'empresa_activa_id' => $this->empresa->id]);
        $analista->assignRole('Analista');
        Livewire::actingAs($analista)->test(\App\Livewire\Almacen\TrasladoAlmacenModal::class)
            ->call('abrir', $origen->id)
            ->assertForbidden();
    }

    /** @test */
    public function una_pieza_en_un_deposito_muestra_su_codigo_y_su_recorrido(): void
    {
        $componente = $this->entrarRam('SN-DANADA');
        $pieza = PiezaExtraida::where('identificador', 'SN-DANADA')->firstOrFail();
        $deposito = \App\Models\Deposito::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Piso 25', 'activo' => true, 'creado_por' => $this->admin->id]);
        app(\App\Services\DescarteComponenteService::class)->marcarDanado($componente, 1, $deposito, $this->admin, [$pieza], 'No enciende');

        Livewire::actingAs($this->admin)->test(\App\Livewire\Configuracion\Depositos\DepositoContenido::class, ['deposito' => $deposito])
            ->assertSee('Serial SN-DANADA')
            ->assertSee('Recorrido')
            ->assertSee($componente->nombre);

        Livewire::actingAs($this->admin)->test(PiezaFichaModal::class)
            ->call('abrir', $pieza->id)
            ->assertSee('Piso 25')
            ->assertSeeInOrder(['move_to_inbox', 'Piso 25']);
    }

    /** @test */
    public function el_traslado_genera_su_autorizacion_en_pdf(): void
    {
        $origen = $this->entrarRam("SN-PDF-1\nSN-PDF-2");
        $otra = Empresa::create(['nombre' => 'Fenix']);
        $ids = PiezaExtraida::whereIn('identificador', ['SN-PDF-1', 'SN-PDF-2'])->pluck('id')->all();

        Livewire::actingAs($this->admin)->test(\App\Livewire\Almacen\TrasladoAlmacenModal::class)
            ->call('abrir', $origen->id)
            ->set('empresaDestinoId', (string) $otra->id)
            ->set('piezaIds', $ids)
            ->set('motivo', 'Reponer RAM en Fenix para dos laptops en reparación')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Autorización de Traslado (TA-');

        $traslado = \App\Models\TrasladoAlmacen::firstOrFail();
        $this->assertSame(2, $traslado->cantidad);
        $this->assertSame(['SN-PDF-1', 'SN-PDF-2'], array_column($traslado->detalle['piezas'], 'codigo'));
        $this->assertSame(2, \App\Models\MovimientoComponente::where('traslado_almacen_id', $traslado->id)->count(), 'Salida y entrada ligadas.');

        $this->actingAs($this->admin)->get(route('admin.almacen.traslados.autorizacion', $traslado))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // El HTML del documento dice de dónde a dónde, el motivo y los seriales.
        $html = view('planillas.traslado-almacen', ['traslado' => $traslado->fresh(['empresaOrigen', 'empresaDestino', 'registradoPor']), 'fecha' => now()->format('d/m/Y')])->render();
        foreach (['Autorización de Traslado', 'Empresa A', 'Fenix', 'Reponer RAM en Fenix', 'SN-PDF-2', 'Autoriza el traslado'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }

        // Un Analista de otra empresa no la ve.
        $ajena = Empresa::create(['nombre' => 'Ajena']);
        $analista = User::factory()->create(['empresa_id' => $ajena->id, 'empresa_activa_id' => $ajena->id]);
        $analista->assignRole('Analista');
        $this->assertNotSame(200, $this->actingAs($analista)->get(route('admin.almacen.traslados.autorizacion', $traslado))->status());
    }
}
