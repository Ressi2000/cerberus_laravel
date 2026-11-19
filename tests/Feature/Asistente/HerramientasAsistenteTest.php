<?php

namespace Tests\Feature\Asistente;

use App\Asistente\Herramientas;
use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\Ubicacion;
use App\Models\User;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Herramientas de consulta del asistente (Fase 1): solo lectura y
 * respetando lo que cada usuario puede ver.
 */
class HerramientasAsistenteTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresaA;
    private Empresa $empresaB;
    private User $admin;
    private User $analistaA;
    private Equipo $laptopA;
    private Equipo $laptopB;
    private CategoriaEquipo $laptop;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);

        $this->empresaA = Empresa::create(['nombre' => 'Empresa A']);
        $this->empresaB = Empresa::create(['nombre' => 'Empresa B']);
        $ubicA = Ubicacion::create(['nombre' => 'Sede A', 'empresa_id' => $this->empresaA->id, 'activo' => true]);
        $ubicB = Ubicacion::create(['nombre' => 'Sede B', 'empresa_id' => $this->empresaB->id, 'activo' => true]);

        $this->admin = User::factory()->create(['empresa_id' => $this->empresaA->id, 'username' => 'jefe']);
        $this->admin->assignRole('Administrador');

        $this->analistaA = User::factory()->create([
            'empresa_id' => $this->empresaA->id, 'empresa_activa_id' => $this->empresaA->id, 'ubicacion_id' => $ubicA->id,
        ]);
        $this->analistaA->assignRole('Analista');

        $this->laptop = CategoriaEquipo::create(['nombre' => 'Laptop']);
        $serial = AtributoEquipo::create(['categoria_id' => $this->laptop->id, 'nombre' => 'Serial', 'slug' => 'serial', 'tipo' => 'string']);

        $this->laptopA = $this->equipo('EQ-A001', $this->empresaA, $ubicA);
        $this->laptopB = $this->equipo('EQ-B001', $this->empresaB, $ubicB);
        EquipoAtributoValor::create(['equipo_id' => $this->laptopA->id, 'atributo_id' => $serial->id, 'valor' => 'SN-ABC-123', 'es_actual' => true]);
    }

    private function equipo(string $codigo, Empresa $empresa, Ubicacion $ubicacion): Equipo
    {
        return Equipo::forceCreate([
            'empresa_id' => $empresa->id, 'categoria_id' => $this->laptop->id, 'ubicacion_id' => $ubicacion->id,
            'estado_id' => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
            'codigo_interno' => $codigo, 'activo' => true,
        ]);
    }

    private function usar(string $herramienta, User $actor, array $args = []): array
    {
        return app(Herramientas::class)->ejecutar($herramienta, $actor, $args);
    }

    /** @test */
    public function cada_herramienta_tiene_una_definicion_valida_para_la_ia(): void
    {
        $definiciones = app(Herramientas::class)->definiciones();

        $this->assertCount(count(Herramientas::CLASES), $definiciones);
        foreach ($definiciones as $d) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $d['name']);
            $this->assertNotEmpty($d['description']);
            $this->assertSame('object', $d['input_schema']['type']);
            foreach ($d['input_schema']['required'] as $req) {
                $this->assertObjectHasProperty($req, $d['input_schema']['properties']);
            }
        }
    }

    /** @test */
    public function el_analista_solo_ve_los_equipos_de_su_empresa(): void
    {
        $propio = $this->usar('ver_equipo', $this->analistaA, ['codigo' => 'EQ-A001']);
        $this->assertTrue($propio['ok']);
        $this->assertSame('SN-ABC-123', $propio['datos']['caracteristicas']['Serial']);

        $ajeno = $this->usar('ver_equipo', $this->analistaA, ['codigo' => 'EQ-B001']);
        $this->assertFalse($ajeno['ok']);
        $this->assertStringContainsString('No encontré', $ajeno['error']);

        $busqueda = $this->usar('buscar_equipos', $this->analistaA, ['texto' => 'EQ-']);
        $this->assertSame(['EQ-A001'], array_column($busqueda['datos']['equipos'], 'codigo'));

        // El administrador ve los dos.
        $this->assertSame(2, $this->usar('buscar_equipos', $this->admin, ['texto' => 'EQ-'])['datos']['total']);
    }

    /** @test */
    public function busca_equipos_por_una_caracteristica_y_por_la_persona_asignada(): void
    {
        $persona = User::factory()->create(['name' => 'María Pérez', 'empresa_id' => $this->empresaA->id]);
        $asignacion = Asignacion::forceCreate([
            'empresa_id' => $this->empresaA->id, 'usuario_id' => $persona->id, 'analista_id' => $this->admin->id,
            'fecha_asignacion' => now()->subMonth()->toDateString(), 'estado' => 'Activa',
        ]);
        AsignacionItem::forceCreate(['asignacion_id' => $asignacion->id, 'equipo_id' => $this->laptopA->id, 'devuelto' => false]);

        $porSerial = $this->usar('buscar_equipos', $this->admin, ['texto' => 'abc-123']);
        $this->assertSame(['EQ-A001'], array_column($porSerial['datos']['equipos'], 'codigo'));

        $porPersona = $this->usar('buscar_equipos', $this->admin, ['texto' => 'maría']);
        $this->assertSame('María Pérez', $porPersona['datos']['equipos'][0]['asignado_a']);

        $deLaPersona = $this->usar('equipos_de_persona', $this->admin, ['persona' => 'María Pérez']);
        $this->assertSame(['EQ-A001'], array_column($deLaPersona['datos']['asignados'], 'codigo'));

        $this->assertSame(1, $this->usar('buscar_equipos', $this->admin, ['asignado' => false])['datos']['total']);
    }

    /** @test */
    public function el_historial_junta_casos_y_asignaciones_del_equipo(): void
    {
        Mantenimiento::create([
            'empresa_id' => $this->empresaA->id, 'equipo_id' => $this->laptopA->id, 'tipo' => 'Correctivo',
            'estado' => 'Cerrado', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->subDays(10)->toDateString(),
            'falla_reportada' => 'No enciende',
        ]);
        $asignacion = Asignacion::forceCreate([
            'empresa_id' => $this->empresaA->id, 'usuario_id' => $this->analistaA->id, 'analista_id' => $this->admin->id,
            'fecha_asignacion' => now()->subDays(5)->toDateString(), 'estado' => 'Cerrada',
        ]);
        AsignacionItem::forceCreate([
            'asignacion_id' => $asignacion->id, 'equipo_id' => $this->laptopA->id,
            'devuelto' => true, 'fecha_devolucion' => now()->subDay()->toDateString(),
        ]);

        $r = $this->usar('historial_equipo', $this->analistaA, ['codigo' => 'EQ-A001']);

        $this->assertTrue($r['ok']);
        $this->assertSame(['Devolución', 'Asignación', 'Reparación'], array_column($r['datos']['eventos'], 'tipo'));
        $this->assertStringContainsString('No enciende', $r['datos']['eventos'][2]['detalle']);

        $soloCasos = $this->usar('historial_equipo', $this->analistaA, ['codigo' => 'EQ-A001', 'tipo' => 'mantenimiento']);
        $this->assertSame(1, $soloCasos['datos']['total']);
    }

    /** @test */
    public function piezas_y_stock_respetan_la_empresa(): void
    {
        foreach ([$this->empresaA, $this->empresaB] as $empresa) {
            $variante = ComponenteAlmacen::create([
                'empresa_id' => $empresa->id, 'nombre' => "Memoria DDR4 8GB {$empresa->nombre}",
                'unidad' => 'unidad', 'stock_actual' => 1, 'activo' => true, 'creado_por' => $this->admin->id,
            ]);
            $pieza = PiezaExtraida::create([
                'empresa_id' => $empresa->id, 'reutilizable' => true, 'identificador' => "RAM-{$empresa->id}",
                'estado' => 'en_almacen', 'componente_almacen_id' => $variante->id, 'motivo' => 'registro_manual',
                'extraido_por' => $this->admin->id,
            ]);
            $pieza->movimientos()->create(['tipo' => 'ingreso_almacen', 'registrado_por' => $this->admin->id]);
        }

        $stock = $this->usar('stock_almacen', $this->analistaA, ['texto' => 'ddr4']);
        $this->assertSame(1, $stock['datos']['variantes']);

        $piezas = $this->usar('buscar_piezas', $this->analistaA, ['texto' => 'RAM-']);
        $this->assertSame(["Serial RAM-{$this->empresaA->id}"], array_column($piezas['datos']['piezas'], 'codigo'));

        $recorrido = $this->usar('recorrido_pieza', $this->analistaA, ['serial' => "RAM-{$this->empresaA->id}"]);
        $this->assertSame('Ingresó al almacén', $recorrido['datos']['recorrido'][0]['que_paso']);

        $this->assertFalse($this->usar('recorrido_pieza', $this->analistaA, ['serial' => "RAM-{$this->empresaB->id}"])['ok']);
    }

    /** @test */
    public function casos_y_resumen_cuentan_solo_lo_visible(): void
    {
        foreach ([$this->laptopA, $this->laptopB] as $equipo) {
            Mantenimiento::create([
                'empresa_id' => $equipo->empresa_id, 'equipo_id' => $equipo->id, 'tipo' => 'Correctivo',
                'estado' => 'En reparación', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->toDateString(),
            ]);
        }

        $casos = $this->usar('casos_mantenimiento', $this->analistaA);
        $this->assertSame(1, $casos['datos']['total']);
        $this->assertSame('EQ-A001', $casos['datos']['casos'][0]['equipo']);

        $this->assertSame(2, $this->usar('casos_mantenimiento', $this->admin, ['tipo' => 'correctivo'])['datos']['total']);
        $this->assertSame(0, $this->usar('casos_mantenimiento', $this->admin, ['situacion' => 'cerrados'])['datos']['total']);

        $resumen = $this->usar('resumen_inventario', $this->analistaA);
        $this->assertSame(1, $resumen['datos']['equipos_activos']);
        $this->assertSame(1, $resumen['datos']['casos_abiertos']['reparaciones']);
    }

    /** @test */
    public function errores_claros_y_sin_acceso_para_usuarios_finales(): void
    {
        $usuario = User::factory()->create(['empresa_id' => $this->empresaA->id]);
        $usuario->assignRole('Usuario');

        $this->assertStringContainsString('no tiene acceso', $this->usar('resumen_inventario', $usuario)['error']);
        $this->assertStringContainsString('No existe', $this->usar('borrar_todo', $this->admin)['error']);
        $this->assertStringContainsString('Falta el dato «codigo»', $this->usar('ver_equipo', $this->admin)['error']);
        $this->assertStringContainsString('debe ser uno de', $this->usar('casos_mantenimiento', $this->admin, ['situacion' => 'x'])['error']);
        $this->assertStringContainsString('fecha', $this->usar('casos_mantenimiento', $this->admin, ['desde' => 'ayer no'])['error']);
    }

    /** @test */
    public function el_comando_ejecuta_una_herramienta_con_los_permisos_del_usuario(): void
    {
        $this->artisan('cerberus:herramienta')->assertSuccessful();

        $this->artisan('cerberus:herramienta', ['nombre' => 'ver_equipo', '--usuario' => 'jefe', '--args' => '{"codigo":"EQ-B001"}'])
            ->expectsOutputToContain('"codigo": "EQ-B001"')
            ->assertSuccessful();

        $this->artisan('cerberus:herramienta', ['nombre' => 'ver_equipo', '--args' => '{}'])->assertFailed();
    }
}
