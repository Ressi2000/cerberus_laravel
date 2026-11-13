<?php

namespace Tests\Feature\MonitorRed;

use App\Livewire\MonitorRed\CaidasTable;
use App\Livewire\MonitorRed\ImportarHostsModal;
use App\Livewire\MonitorRed\MonitorHostModal;
use App\Livewire\MonitorRed\MonitorHostsTable;
use App\Livewire\MonitorRed\MonitorRedDashboard;
use App\Livewire\MonitorRed\PruebaVelocidad;
use App\Models\Empresa;
use App\Models\MonitorCaida;
use App\Models\MonitorHost;
use App\Models\User;
use App\Notifications\MonitorHostEstadoNotification;
use App\Services\MonitorRed\EstadisticasMonitor;
use App\Services\MonitorRed\MonitorRedService;
use App\Services\MonitorRed\SondaRed;
use Database\Seeders\MonitorRedPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonitorRedTest extends TestCase
{
    use RefreshDatabase;

    /** Resultado que devolverá la sonda falsa: host => [ok, ms]. */
    private array $respuestas = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(MonitorRedPermisosSeeder::class);

        // Sonda de red falsa: nunca ejecuta ping real en los tests.
        $test = $this;
        $this->app->instance(SondaRed::class, new class($test) extends SondaRed {
            public function __construct(private $test) {}
            public function ping(array $hosts, int $timeout, int $concurrencia = 20): array
            {
                return array_map(fn ($h) => $this->test->respuestaPara($h, 'ping'), $hosts);
            }
            public function tcp(array $objetivos, int $timeout): array
            {
                return array_map(fn ($o) => $this->test->respuestaPara($o[0] . ':' . $o[1], 'tcp'), $objetivos);
            }
        });

        config(['monitor_red.fallos_para_caida' => 2, 'monitor_red.notificar' => true]);
    }

    public function respuestaPara(string $clave, string $tipo): array
    {
        return $this->respuestas[$clave] ?? [false, null];
    }

    // ── Fixtures ────────────────────────────────────────────────────────────

    private function empresa(string $nombre = 'Empresa A'): Empresa
    {
        return Empresa::create(['nombre' => $nombre, 'activo' => true]);
    }

    private function crearAdmin(): User
    {
        return tap(User::factory()->create(['estado' => 'Activo']))->assignRole('Administrador');
    }

    private function crearAnalista(Empresa $empresa): User
    {
        $u = User::factory()->create(['estado' => 'Activo', 'empresa_activa_id' => $empresa->id]);
        $u->assignRole('Analista');
        $u->empresasAsignadas()->attach($empresa->id);

        return $u;
    }

    private function crearUsuario(): User
    {
        return tap(User::factory()->create(['estado' => 'Activo']))->assignRole('Usuario');
    }

    private function host(Empresa $empresa, array $attrs = [], ?string $estado = null): MonitorHost
    {
        $f = MonitorHost::factory();
        if ($estado) $f = $f->{$estado}();

        return $f->create(['empresa_id' => $empresa->id] + $attrs);
    }

    private function revisar(): ?array
    {
        return app(MonitorRedService::class)->revisarTodos();
    }

    // ── BLOQUE 1: Control de acceso ─────────────────────────────────────────

    /** @test */
    public function usuario_sin_autenticar_es_redirigido(): void
    {
        $this->get(route('admin.monitor-red.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function rol_usuario_no_puede_acceder(): void
    {
        $this->actingAs($this->crearUsuario())->get(route('admin.monitor-red.hosts'))->assertForbidden();
    }

    /** @test */
    public function analista_y_admin_pueden_acceder_a_todas_las_pantallas(): void
    {
        $empresa = $this->empresa();
        $this->host($empresa, [], 'activo');

        foreach ([$this->crearAnalista($empresa), $this->crearAdmin()] as $user) {
            foreach (['index', 'hosts', 'caidas', 'velocidad'] as $ruta) {
                $this->actingAs($user)->get(route("admin.monitor-red.{$ruta}"))->assertOk();
            }
        }
    }

    /** @test */
    public function analista_no_ve_hosts_de_otra_empresa(): void
    {
        $a = $this->empresa('A');
        $b = $this->empresa('B');
        $this->host($a, ['nombre' => 'Router propio']);
        $this->host($b, ['nombre' => 'Router ajeno']);

        Livewire::actingAs($this->crearAnalista($a))
            ->test(MonitorHostsTable::class)
            ->assertSee('Router propio')
            ->assertDontSee('Router ajeno');
    }

    /** @test */
    public function analista_no_puede_editar_host_de_otra_empresa(): void
    {
        $a = $this->empresa('A');
        $ajeno = $this->host($this->empresa('B'));

        Livewire::actingAs($this->crearAnalista($a))
            ->test(MonitorHostModal::class)
            ->call('abrirEditar', $ajeno->id)
            ->assertForbidden();
    }

    // ── BLOQUE 2: Alta, importación y eliminación ───────────────────────────

    /** @test */
    public function puede_agregar_un_host_valido(): void
    {
        $empresa = $this->empresa();

        Livewire::actingAs($this->crearAnalista($empresa))
            ->test(MonitorHostModal::class)
            ->call('abrirCrear')
            ->set('host', '10.0.0.1')
            ->set('nombre', 'Router central')
            ->set('tipo', 'Router')
            ->set('puerto_tcp', '443')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('monitor_hosts', [
            'empresa_id' => $empresa->id, 'host' => '10.0.0.1', 'puerto_tcp' => 443, 'estado' => 'Pendiente',
        ]);
    }

    /** @test */
    public function rechaza_host_con_caracteres_de_shell_o_url(): void
    {
        $empresa = $this->empresa();

        foreach (['8.8.8.8; rm -rf /', 'http://x.com/a', '-c 100 x', ''] as $malo) {
            Livewire::actingAs($this->crearAnalista($empresa))
                ->test(MonitorHostModal::class)
                ->call('abrirCrear')
                ->set('host', $malo)
                ->call('guardar')
                ->assertHasErrors('host');
        }

        $this->assertDatabaseCount('monitor_hosts', 0);
    }

    /** @test */
    public function no_permite_duplicar_un_host_en_la_misma_empresa(): void
    {
        $empresa = $this->empresa();
        $this->host($empresa, ['host' => '10.0.0.1', 'puerto_tcp' => null]);

        Livewire::actingAs($this->crearAnalista($empresa))
            ->test(MonitorHostModal::class)
            ->call('abrirCrear')
            ->set('host', '10.0.0.1')
            ->call('guardar')
            ->assertHasErrors('host');
    }

    /** @test */
    public function importa_varias_lineas_y_reporta_las_invalidas_y_duplicadas(): void
    {
        $empresa = $this->empresa();
        $this->host($empresa, ['host' => '10.0.0.9', 'puerto_tcp' => null]);

        Livewire::actingAs($this->crearAnalista($empresa))
            ->test(ImportarHostsModal::class)
            ->call('abrir')
            ->set('texto', "10.0.0.1, Router, Router, ISP 1, 80\n10.0.0.2;Switch;Switch\nno valido!!\n10.0.0.9\n10.0.0.3, X, Y, Z, 99999")
            ->call('importar')
            ->assertSet('importados', 2)
            ->assertCount('fallos', 3);

        $this->assertDatabaseHas('monitor_hosts', ['host' => '10.0.0.1', 'puerto_tcp' => 80, 'proveedor' => 'ISP 1']);
        $this->assertDatabaseHas('monitor_hosts', ['host' => '10.0.0.2', 'tipo' => 'Switch']);
    }

    /** @test */
    public function quitar_un_host_lo_borra_en_suave_y_conserva_sus_caidas(): void
    {
        $empresa = $this->empresa();
        $h = $this->host($empresa, [], 'caido');
        $h->caidas()->create(['inicio' => now()->subMinutes(10)]);

        Livewire::actingAs($this->crearAnalista($empresa))
            ->test(MonitorHostsTable::class)
            ->call('eliminar', [$h->id]);

        $this->assertSoftDeleted('monitor_hosts', ['id' => $h->id]);
        $this->assertEquals(1, MonitorCaida::count());
        $this->assertNotNull(MonitorCaida::first()->fin, 'La caída abierta debe cerrarse al quitar el host.');
    }

    // ── BLOQUE 3: Lógica de monitoreo ───────────────────────────────────────

    /** @test */
    public function un_solo_fallo_no_declara_caida(): void
    {
        $h = $this->host($this->empresa(), ['host' => '10.0.0.1'], 'activo');

        $this->respuestas = ['10.0.0.1' => [false, null]];
        $this->revisar();

        $h->refresh();
        $this->assertSame(MonitorHost::ESTADO_ACTIVO, $h->estado);
        $this->assertSame(1, $h->fallos_consecutivos);
        $this->assertSame(0, MonitorCaida::count());
    }

    /** @test */
    public function fallos_consecutivos_declaran_caida_fechada_en_el_primer_fallo_y_notifican(): void
    {
        Notification::fake();
        $empresa  = $this->empresa();
        $analista = $this->crearAnalista($empresa);
        $admin    = $this->crearAdmin();
        $otro     = $this->crearAnalista($this->empresa('Otra'));
        $h = $this->host($empresa, ['host' => '10.0.0.1'], 'activo');

        $this->respuestas = ['10.0.0.1' => [false, null]];
        $this->travelTo(now()->startOfMinute());
        $primerFallo = now()->copy();
        $this->revisar();
        $this->travel(60)->seconds();
        $resumen = $this->revisar();

        $h->refresh();
        $this->assertSame(MonitorHost::ESTADO_CAIDO, $h->estado);
        $this->assertSame(1, $resumen['caidas']);
        $this->assertEquals($primerFallo->getTimestamp(), MonitorCaida::first()->inicio->getTimestamp());

        Notification::assertSentTo([$analista, $admin], MonitorHostEstadoNotification::class, fn ($n) => ! $n->recuperado);
        Notification::assertNotSentTo($otro, MonitorHostEstadoNotification::class);

        // Mientras siga caído no se repite la caída ni la alerta.
        $this->travel(60)->seconds();
        $this->revisar();
        $this->assertSame(1, MonitorCaida::count());
        Notification::assertSentToTimes($analista, MonitorHostEstadoNotification::class, 1);
    }

    /** @test */
    public function al_recuperarse_cierra_la_caida_con_su_duracion_y_notifica(): void
    {
        Notification::fake();
        $empresa  = $this->empresa();
        $analista = $this->crearAnalista($empresa);
        $h = $this->host($empresa, ['host' => '10.0.0.1'], 'caido');
        $h->caidas()->create(['inicio' => now()->subMinutes(5)]);

        $this->respuestas = ['10.0.0.1' => [true, 3.2]];
        $this->revisar();

        $h->refresh();
        $caida = MonitorCaida::first();
        $this->assertSame(MonitorHost::ESTADO_ACTIVO, $h->estado);
        $this->assertSame(3.2, $h->latencia_ms);
        $this->assertNotNull($caida->fin);
        $this->assertEqualsWithDelta(300, $caida->duracion_segundos, 2);
        Notification::assertSentTo($analista, MonitorHostEstadoNotification::class, fn ($n) => $n->recuperado);
    }

    /** @test */
    public function usa_el_puerto_tcp_cuando_el_ping_esta_bloqueado(): void
    {
        $h = $this->host($this->empresa(), ['host' => '10.0.0.1', 'puerto_tcp' => 443]);

        $this->respuestas = ['10.0.0.1' => [false, null], '10.0.0.1:443' => [true, 12.0]];
        $this->revisar();

        $h->refresh();
        $this->assertSame(MonitorHost::ESTADO_ACTIVO, $h->estado);
        $this->assertSame('TCP:443', $h->metodo);
    }

    /** @test */
    public function host_nuevo_que_nunca_respondio_registra_caida_sin_alertar(): void
    {
        Notification::fake();
        $empresa = $this->empresa();
        $this->crearAnalista($empresa);
        $this->host($empresa, ['host' => '10.0.0.1']);

        $this->revisar();
        $this->travel(60)->seconds();
        $this->revisar();

        $this->assertSame(1, MonitorCaida::count());
        Notification::assertNothingSent();
    }

    /** @test */
    public function los_hosts_pausados_no_se_revisan(): void
    {
        $h = $this->host($this->empresa(), ['host' => '10.0.0.1'], 'pausado');

        $resumen = $this->revisar();

        $this->assertSame(0, $resumen['revisados']);
        $this->assertNull($h->refresh()->ultima_revision);
    }

    /** @test */
    public function cambiar_la_ip_reinicia_el_estado_y_cierra_la_caida(): void
    {
        $h = $this->host($this->empresa(), ['host' => '10.0.0.1'], 'caido');
        $h->caidas()->create(['inicio' => now()->subMinutes(3)]);

        $h->update(['host' => '10.0.0.2']);

        $this->assertSame(MonitorHost::ESTADO_PENDIENTE, $h->refresh()->estado);
        $this->assertNotNull(MonitorCaida::first()->fin);
    }

    /** @test */
    public function no_corren_dos_revisiones_a_la_vez(): void
    {
        $this->host($this->empresa());
        $lock = cache()->lock('monitor-red:revision', 60);
        $lock->get();

        $this->assertNull($this->revisar());

        $lock->release();
        $this->assertNotNull($this->revisar());
    }

    /** @test */
    public function el_comando_del_scheduler_revisa_y_purga(): void
    {
        $h = $this->host($this->empresa(), ['host' => '10.0.0.1']);
        $h->mediciones()->create(['medido_en' => now()->subDays(40), 'exito' => true, 'latencia_ms' => 1]);
        $this->respuestas = ['10.0.0.1' => [true, 1.0]];

        $this->artisan('cerberus:monitor-red')->assertSuccessful();
        $this->assertSame(MonitorHost::ESTADO_ACTIVO, $h->refresh()->estado);

        $this->artisan('cerberus:monitor-red --purgar')->assertSuccessful();
        $this->assertSame(1, $h->mediciones()->count());
    }

    // ── BLOQUE 4: Auditoría ─────────────────────────────────────────────────

    /** @test */
    public function crear_host_audita_pero_las_revisiones_no_inundan_la_auditoria(): void
    {
        $empresa = $this->empresa();
        $this->actingAs($this->crearAnalista($empresa));
        $h = $this->host($empresa, ['host' => '10.0.0.1']);

        $this->assertDatabaseHas('auditoria', ['tabla' => 'monitor_hosts', 'accion' => 'CREAR', 'registro_id' => $h->id]);
        $antes = \App\Models\Auditoria::count();

        $this->respuestas = ['10.0.0.1' => [true, 1.0]];
        $this->revisar();
        $this->revisar();

        $this->assertSame($antes, \App\Models\Auditoria::count());
    }

    // ── BLOQUE 5: Estadísticas, filtros y velocidad ─────────────────────────

    /** @test */
    public function calcula_la_disponibilidad_a_partir_de_las_caidas(): void
    {
        $this->travelTo(now()->startOfMinute());
        $h = $this->host($this->empresa(), ['created_at' => now()->subDays(2)]);
        // 1 h 12 min caído dentro de las últimas 24 h → 95 %
        $h->caidas()->create(['inicio' => now()->subHours(3), 'fin' => now()->subHours(3)->addMinutes(72), 'duracion_segundos' => 4320]);

        $disp = app(EstadisticasMonitor::class)->disponibilidad(collect([$h]));

        $this->assertEquals(95.0, $disp[$h->id]['d1']);
        $this->assertEquals(97.5, $disp[$h->id]['d7']); // la ventana empieza en el alta (2 días)
    }

    /** @test */
    public function tabla_filtra_por_estado(): void
    {
        $empresa = $this->empresa();
        $this->host($empresa, ['nombre' => 'Arriba'], 'activo');
        $this->host($empresa, ['nombre' => 'Abajo'], 'caido');

        Livewire::actingAs($this->crearAnalista($empresa))
            ->test(MonitorHostsTable::class)
            ->set('estado', 'Caido')
            ->assertSee('Abajo')
            ->assertDontSee('Arriba');
    }

    /** @test */
    public function dashboard_e_historial_renderizan_con_datos(): void
    {
        $empresa = $this->empresa();
        $h = $this->host($empresa, ['nombre' => 'Firewall borde'], 'caido');
        $h->caidas()->create(['inicio' => now()->subMinutes(10)]);
        $h->mediciones()->create(['medido_en' => now()->subMinutes(5), 'exito' => false]);
        $analista = $this->crearAnalista($empresa);

        Livewire::actingAs($analista)->test(MonitorRedDashboard::class)->assertSee('Firewall borde')->assertSee('sin respuesta');
        Livewire::actingAs($analista)->test(CaidasTable::class)->assertSee('Firewall borde')->assertSee('En curso');
        Livewire::actingAs($analista)->test(MonitorHostsTable::class)->set('vista', 'cuadros')->assertSee('Firewall borde');

        $this->actingAs($analista)->get(route('admin.monitor-red.caidas.exportar'))->assertOk();
    }

    /** @test */
    public function guarda_el_test_de_velocidad_en_la_empresa_activa(): void
    {
        $empresa  = $this->empresa();
        $analista = $this->crearAnalista($empresa);

        Livewire::actingAs($analista)
            ->test(PruebaVelocidad::class)
            ->call('guardar', ['bajada' => 95.456, 'subida' => 40, 'ping' => 12, 'jitter' => 1.5, 'isp' => '<b>ISP 1</b>', 'asn' => 'AS13335', 'ip' => '1.2.3.4']);

        $this->assertDatabaseHas('monitor_pruebas_velocidad', [
            'empresa_id' => $empresa->id, 'usuario_id' => $analista->id, 'bajada_mbps' => 95.46, 'isp' => 'ISP 1', 'asn' => '13335', 'ip_publica' => '1.2.3.4',
        ]);
    }
}
