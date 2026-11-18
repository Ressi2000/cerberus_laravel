<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\ComponentePiezasModal;
use App\Livewire\Almacen\MovimientoStockModal;
use App\Livewire\Almacen\PiezaFichaModal;
use App\Livewire\Configuracion\Depositos\DepositoContenido;
use App\Livewire\Mantenimientos\MantenimientoComponentesPanel;
use App\Livewire\Mantenimientos\MantenimientoPiezasPanel;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\FamiliaComponente;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\RetiroGranel;
use App\Models\User;
use App\Services\PiezaManualService;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Traza de lo a granel (devolver por error, retirar de un equipo, dañado en
 * el almacén) y corrección de seriales.
 */
class GranelYSerialesTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $admin;
    private Equipo $equipo;
    private ComponenteAlmacen $bisagra;
    private Deposito $deposito;

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

        $this->equipo = Equipo::forceCreate([
            'empresa_id' => $this->empresa->id, 'categoria_id' => CategoriaEquipo::create(['nombre' => 'Laptop'])->id,
            'estado_id' => EstadoEquipo::where('nombre', 'En reparación')->value('id'),
            'codigo_interno' => 'EQ-GRANEL', 'activo' => true,
        ]);

        $familia = FamiliaComponente::create(['nombre' => 'Bisagras', 'control' => 'granel', 'campos' => []]);
        $this->bisagra = ComponenteAlmacen::create([
            'empresa_id' => $this->empresa->id, 'familia_id' => $familia->id, 'nombre' => 'Bisagra HP 240',
            'unidad' => 'unidad', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $this->admin->id,
        ]);
        $this->bisagra->registrarEntrada(10, $this->admin);

        $this->deposito = Deposito::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Piso 25', 'activo' => true, 'creado_por' => $this->admin->id]);
    }

    private function caso(): Mantenimiento
    {
        return Mantenimiento::create([
            'empresa_id' => $this->empresa->id, 'equipo_id' => $this->equipo->id, 'tipo' => 'Correctivo',
            'estado' => 'En reparación', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->toDateString(),
        ]);
    }

    private function puestas(): int
    {
        return app(\App\Services\RetiroGranelService::class)->instaladoEnEquipo($this->equipo)->firstWhere('componente.id', $this->bisagra->id)?->cantidad ?? 0;
    }

    // ─── 1. Devolver lo registrado de más ──────────────────────────────────

    /** @test */
    public function lo_usado_de_mas_se_devuelve_al_almacen_con_su_motivo(): void
    {
        $caso = $this->caso();
        $item = $caso->pedirComponente($this->bisagra->fresh(), 4, $this->admin);
        $this->assertSame(6, $this->bisagra->fresh()->stock_actual);

        Livewire::actingAs($this->admin)->test(MantenimientoComponentesPanel::class, ['mantenimientoId' => $caso->id])
            ->call('abrirDevolver', $item->id)
            ->set('devolverCantidad', 5)->set('devolverMotivo', 'Se registraron de más')
            ->call('devolver')->assertHasErrors('devolverCantidad')     // más de lo usado
            ->set('devolverCantidad', 2)
            ->call('devolver')->assertHasNoErrors()
            ->assertSee('2 devuelta(s) al almacén');

        $this->assertSame(8, $this->bisagra->fresh()->stock_actual);
        $this->assertSame(2, $this->puestas());
        $this->assertDatabaseHas('retiros_granel', ['tipo' => 'devolucion', 'cantidad' => 2, 'destino' => 'almacen', 'mantenimiento_id' => $caso->id]);
        $this->assertDatabaseHas('movimientos_componentes', ['componente_id' => $this->bisagra->id, 'tipo' => 'Entrada', 'mantenimiento_id' => $caso->id]);
    }

    /** @test */
    public function con_el_caso_cerrado_no_se_puede_devolver(): void
    {
        $caso = $this->caso();
        $item = $caso->pedirComponente($this->bisagra->fresh(), 2, $this->admin);
        $caso->update(['estado' => 'Cerrado']);

        $this->expectException(\InvalidArgumentException::class);
        app(\App\Services\RetiroGranelService::class)->devolver($item->fresh(), 1, 'Error de registro', $this->admin);
    }

    // ─── 2. Retirar de un equipo en otro caso ──────────────────────────────

    /** @test */
    public function lo_puesto_en_un_caso_se_retira_en_otro_a_deposito_o_a_desecho(): void
    {
        $viejo = $this->caso();
        $viejo->pedirComponente($this->bisagra->fresh(), 4, $this->admin);
        $viejo->update(['estado' => 'Cerrado']);

        $nuevo = $this->caso();
        $panel = Livewire::actingAs($this->admin)->test(MantenimientoPiezasPanel::class, ['mantenimientoId' => $nuevo->id]);
        $indice = collect($panel->instance()->candidatosRetiro)->search(fn ($c) => $c['tipo'] === 'granel');
        $this->assertNotFalse($indice, 'Lo a granel puesto en el equipo aparece para retirar.');

        $panel->call('abrirRetirar')
            ->set('retirarIndex', $indice)
            ->set('retirarCantidad', 3)
            ->set('retirarDestino', PiezaExtraida::ESTADO_EN_DEPOSITO)
            ->set('retirarDepositoId', $this->deposito->id)
            ->call('retirar')->assertHasErrors('retirarObservaciones')   // motivo obligatorio
            ->set('retirarObservaciones', 'Bisagras partidas')
            ->call('retirar')->assertHasNoErrors();

        $this->assertSame(1, $this->puestas());
        $this->assertSame(6, $this->bisagra->fresh()->stock_actual, 'A depósito no vuelve al stock.');

        $panel->call('abrirRetirar')
            ->set('retirarIndex', $indice)
            ->set('retirarCantidad', 1)
            ->set('retirarDestino', 'desecho')
            ->set('retirarObservaciones', 'Rota, se bota')
            ->call('retirar')->assertHasNoErrors()
            ->assertSee('Retirado del equipo');

        $this->assertSame(0, $this->puestas());
        $this->assertDatabaseHas('retiros_granel', ['tipo' => 'retiro', 'destino' => 'desecho', 'mantenimiento_id' => $nuevo->id]);

        Livewire::actingAs($this->admin)->test(DepositoContenido::class, ['deposito' => $this->deposito])
            ->assertSee('Bisagra HP 240')
            ->assertSee('EQ-GRANEL')
            ->assertSee('Bisagras partidas');
    }

    // ─── 3. Dañado en el almacén ───────────────────────────────────────────

    /** @test */
    public function lo_a_granel_danado_en_el_almacen_va_a_deposito_o_se_desecha_con_traza(): void
    {
        $salida = fn () => Livewire::actingAs($this->admin)->test(MovimientoStockModal::class)
            ->call('abrir', $this->bisagra->id)
            ->set('cantidad', 2)
            ->set('danado', true);

        $salida()->set('destinoDanado', 'deposito')->set('depositoId', $this->deposito->id)
            ->call('guardar')->assertHasErrors('observaciones')
            ->set('observaciones', 'Se oxidaron')->call('guardar')->assertHasNoErrors();

        $salida()->set('destinoDanado', 'desecho')->set('observaciones', 'Mojadas, se botan')
            ->call('guardar')->assertHasNoErrors();

        $this->assertSame(6, $this->bisagra->fresh()->stock_actual);
        $this->assertSame(2, RetiroGranel::where('tipo', 'danado')->count());
        $this->assertDatabaseHas('retiros_granel', ['destino' => 'deposito', 'deposito_id' => $this->deposito->id, 'motivo' => 'Se oxidaron']);

        Livewire::actingAs($this->admin)->test(DepositoContenido::class, ['deposito' => $this->deposito])
            ->assertSee('se dañó en el almacén')->assertSee('Se oxidaron');
    }

    // ─── Seriales ──────────────────────────────────────────────────────────

    /** @test */
    public function el_serial_de_una_pieza_se_corrige_sin_repetir_y_queda_en_el_recorrido(): void
    {
        $familia = FamiliaComponente::create(['nombre' => 'Fan cooler', 'control' => 'unidad', 'campos' => []]);
        $fan = ComponenteAlmacen::create([
            'empresa_id' => $this->empresa->id, 'familia_id' => $familia->id, 'nombre' => 'Fan Cooler HP 450',
            'unidad' => 'unidad', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $this->admin->id,
        ]);
        [$a, $b] = app(PiezaManualService::class)->registrar($fan, 2, PiezaExtraida::CONDICION_NUEVO, $this->admin, seriales: ['w123585s', 'qqw585d'])->all();

        Livewire::actingAs($this->admin)->test(PiezaFichaModal::class)
            ->call('abrir', $a->id)
            ->call('editarSerial')
            ->set('nuevoSerial', 'qqw585d')->call('guardarSerial')->assertHasErrors('nuevoSerial')
            ->set('nuevoSerial', 'W123585S')->call('guardarSerial')->assertHasNoErrors()
            ->assertSee('Serial W123585S')
            ->assertSee('Serial corregido: w123585s');

        $this->assertSame('W123585S', $a->fresh()->identificador);
        $this->assertSame(PiezaExtraidaMovimiento::TIPO_CORRECCION, $a->movimientos()->reorder()->orderByDesc('id')->value('tipo'));
    }

    /** @test */
    public function una_unidad_en_stock_sin_serial_se_identifica_sin_cambiar_el_stock(): void
    {
        $familia = FamiliaComponente::create(['nombre' => 'Fan cooler', 'control' => 'unidad', 'campos' => []]);
        $fan = ComponenteAlmacen::create([
            'empresa_id' => $this->empresa->id, 'familia_id' => $familia->id, 'nombre' => 'Fan Cooler HP 450',
            'unidad' => 'unidad', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $this->admin->id,
        ]);
        $fan->registrarEntrada(1, $this->admin);   // una unidad sin serial
        app(PiezaManualService::class)->registrar($fan->fresh(), 1, PiezaExtraida::CONDICION_NUEVO, $this->admin, seriales: ['abc']);

        Livewire::actingAs($this->admin)->test(ComponentePiezasModal::class)
            ->call('abrir', $fan->id)
            ->assertSee('unidad(es) en stock sin serial')
            ->set('serialNuevo', 'abc')->call('asignarSerial')->assertHasErrors('serialNuevo')
            ->set('serialNuevo', '12365478')->call('asignarSerial')->assertHasNoErrors()
            ->assertSee('Serial 12365478')
            ->assertDontSee('unidad(es) en stock sin serial');

        $fan->refresh();
        $this->assertSame(2, $fan->stock_actual, 'El stock no cambia.');
        $this->assertSame(0, $fan->unidadesSinIdentidad());
    }
}
