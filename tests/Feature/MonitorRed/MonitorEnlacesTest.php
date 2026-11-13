<?php

namespace Tests\Feature\MonitorRed;

use App\Livewire\MonitorRed\EnlaceModal;
use App\Livewire\MonitorRed\EnlacesTable;
use App\Livewire\MonitorRed\MonitorHostModal;
use App\Livewire\MonitorRed\PruebaVelocidad;
use App\Models\Empresa;
use App\Models\MonitorEnlace;
use App\Models\MonitorHost;
use App\Models\MonitorPruebaVelocidad;
use App\Models\User;
use Database\Seeders\MonitorRedPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonitorEnlacesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(MonitorRedPermisosSeeder::class);
    }

    private function empresa(string $nombre = 'Sede A'): Empresa
    {
        return Empresa::create(['nombre' => $nombre, 'activo' => true]);
    }

    private function crearAnalista(Empresa $empresa): User
    {
        $u = User::factory()->create(['estado' => 'Activo', 'empresa_activa_id' => $empresa->id]);
        $u->assignRole('Analista');
        $u->empresasAsignadas()->attach($empresa->id);

        return $u;
    }

    private function crearAdmin(): User
    {
        return tap(User::factory()->create(['estado' => 'Activo']))->assignRole('Administrador');
    }

    private function enlace(Empresa $empresa, array $attrs = []): MonitorEnlace
    {
        return MonitorEnlace::factory()->create(['empresa_id' => $empresa->id] + $attrs);
    }

    // ── Acceso y visibilidad ────────────────────────────────────────────────

    /** @test */
    public function analista_solo_ve_los_enlaces_de_su_empresa(): void
    {
        $a = $this->empresa('Sede A');
        $b = $this->empresa('Sede B');
        $this->enlace($a, ['nombre' => 'Fibra de A']);
        $this->enlace($b, ['nombre' => 'Fibra de B']);

        $analista = $this->crearAnalista($a);
        $this->actingAs($analista)->get(route('admin.monitor-red.enlaces'))->assertOk();

        Livewire::actingAs($analista)->test(EnlacesTable::class)
            ->assertSee('Fibra de A')
            ->assertDontSee('Fibra de B');

        Livewire::actingAs($this->crearAdmin())->test(EnlacesTable::class)
            ->assertSee('Fibra de A')
            ->assertSee('Fibra de B');
    }

    /** @test */
    public function rol_usuario_no_accede_a_enlaces(): void
    {
        $u = tap(User::factory()->create(['estado' => 'Activo']))->assignRole('Usuario');
        $this->actingAs($u)->get(route('admin.monitor-red.enlaces'))->assertForbidden();
    }

    /** @test */
    public function analista_no_puede_editar_ni_crear_enlaces_de_otra_empresa(): void
    {
        $a = $this->empresa('Sede A');
        $b = $this->empresa('Sede B');
        $ajeno = $this->enlace($b);
        $analista = $this->crearAnalista($a);

        Livewire::actingAs($analista)->test(EnlaceModal::class)
            ->call('abrirEditar', $ajeno->id)
            ->assertForbidden();

        Livewire::actingAs($analista)->test(EnlaceModal::class)
            ->call('abrirCrear')
            ->set('empresa_id', (string) $b->id)
            ->set('nombre', 'Intruso')
            ->set('proveedor', 'X')
            ->call('guardar')
            ->assertHasErrors('empresa_id');

        $this->assertDatabaseMissing('monitor_enlaces', ['nombre' => 'Intruso']);
    }

    // ── Alta y validación ───────────────────────────────────────────────────

    /** @test */
    public function registra_un_enlace_con_puerta_de_enlace_y_plan(): void
    {
        $a = $this->empresa();

        Livewire::actingAs($this->crearAnalista($a))->test(EnlaceModal::class)
            ->call('abrirCrear')
            ->set('nombre', 'Fibra principal')
            ->set('proveedor', 'ISP Uno')
            ->set('tipo_conexion', 'Fibra')
            ->set('gateway', '192.168.1.1')
            ->set('ip_publica', '200.44.32.12')
            ->set('asn', 'AS8048')
            ->set('bajada', '100')
            ->set('subida', '50')
            ->set('principal', true)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('monitor_enlaces', [
            'empresa_id' => $a->id, 'gateway' => '192.168.1.1', 'ip_publica' => '200.44.32.12', 'asn' => '8048', 'principal' => true,
        ]);
        $this->assertDatabaseHas('auditoria', ['tabla' => 'monitor_enlaces', 'accion' => 'CREAR']);
    }

    /** @test */
    public function valida_puerta_de_enlace_e_ip_publica(): void
    {
        $a = $this->empresa();

        Livewire::actingAs($this->crearAnalista($a))->test(EnlaceModal::class)
            ->call('abrirCrear')
            ->set('nombre', 'X')->set('proveedor', 'Y')
            ->set('gateway', 'router.local')
            ->set('ip_publica', '192.168.1.50')
            ->call('guardar')
            ->assertHasErrors(['gateway', 'ip_publica']);
    }

    /** @test */
    public function solo_hay_un_enlace_principal_por_empresa(): void
    {
        $a = $this->empresa();
        $b = $this->empresa('Sede B');
        $viejo = $this->enlace($a, ['principal' => true]);
        $otraEmpresa = $this->enlace($b, ['principal' => true]);

        $nuevo = $this->enlace($a, ['principal' => true]);

        $this->assertFalse($viejo->refresh()->principal);
        $this->assertTrue($nuevo->refresh()->principal);
        $this->assertTrue($otraEmpresa->refresh()->principal);
    }

    /** @test */
    public function vigilar_ip_publica_crea_un_host_vinculado_al_enlace(): void
    {
        $a = $this->empresa();
        $enlace = $this->enlace($a, ['ip_publica' => '200.44.32.12', 'proveedor' => 'ISP Uno']);

        Livewire::actingAs($this->crearAnalista($a))->test(EnlacesTable::class)
            ->call('monitorearWan', $enlace->id)
            ->call('monitorearWan', $enlace->id); // idempotente

        $this->assertSame(1, MonitorHost::where('host', '200.44.32.12')->count());
        $host = MonitorHost::where('host', '200.44.32.12')->first();
        $this->assertSame($enlace->id, $host->enlace_id);
        $this->assertSame('ISP Uno', $host->proveedor);
    }

    /** @test */
    public function el_host_solo_admite_enlaces_de_su_misma_empresa(): void
    {
        $a = $this->empresa();
        $ajeno = $this->enlace($this->empresa('Sede B'));
        $propio = $this->enlace($a, ['proveedor' => 'ISP Uno']);
        $analista = $this->crearAnalista($a);

        Livewire::actingAs($analista)->test(MonitorHostModal::class)
            ->call('abrirCrear')->set('host', '10.0.0.1')->set('enlace_id', (string) $ajeno->id)
            ->call('guardar')->assertHasErrors('enlace_id');

        Livewire::actingAs($analista)->test(MonitorHostModal::class)
            ->call('abrirCrear')->set('host', '10.0.0.1')->set('enlace_id', (string) $propio->id)
            ->call('guardar')->assertHasNoErrors();

        $this->assertDatabaseHas('monitor_hosts', ['host' => '10.0.0.1', 'enlace_id' => $propio->id, 'proveedor' => 'ISP Uno']);
    }

    // ── Atribución de los tests de velocidad ────────────────────────────────

    private function guardarTest(User $u, array $extra): MonitorPruebaVelocidad
    {
        Livewire::actingAs($u)->test(PruebaVelocidad::class)
            ->call('guardar', ['bajada' => 80, 'subida' => 40, 'ping' => 10, 'jitter' => 1] + $extra);

        return MonitorPruebaVelocidad::latest('id')->first();
    }

    /** @test */
    public function el_test_se_asocia_al_enlace_por_ip_publica_o_por_asn(): void
    {
        $a = $this->empresa();
        $fibra   = $this->enlace($a, ['ip_publica' => '200.44.32.12', 'asn' => '8048']);
        $radio   = $this->enlace($a, ['ip_publica' => null, 'asn' => '27889']);
        $u = $this->crearAnalista($a);

        $p1 = $this->guardarTest($u, ['ip' => '200.44.32.12', 'asn' => 'AS8048', 'isp' => 'ISP Uno']);
        $this->assertSame([$fibra->id, 'ip'], [$p1->enlace_id, $p1->enlace_asignado_por]);

        $p2 = $this->guardarTest($u, ['ip' => '190.1.1.1', 'asn' => '27889', 'isp' => 'ISP Dos']);
        $this->assertSame([$radio->id, 'asn'], [$p2->enlace_id, $p2->enlace_asignado_por]);

        $p3 = $this->guardarTest($u, ['ip' => '8.8.4.4', 'asn' => '15169', 'isp' => 'Otro']);
        $this->assertNull($p3->enlace_id);
    }

    /** @test */
    public function asn_compartido_por_dos_enlaces_no_se_adivina(): void
    {
        $a = $this->empresa();
        $this->enlace($a, ['asn' => '8048']);
        $this->enlace($a, ['asn' => '8048']);

        $p = $this->guardarTest($this->crearAnalista($a), ['ip' => '190.1.1.1', 'asn' => '8048']);
        $this->assertNull($p->enlace_id);
    }

    /** @test */
    public function el_enlace_elegido_a_mano_tiene_prioridad_y_debe_ser_de_la_empresa(): void
    {
        $a = $this->empresa();
        $fibra = $this->enlace($a, ['ip_publica' => '200.44.32.12']);
        $respaldo = $this->enlace($a, ['asn' => '27889']);
        $ajeno = $this->enlace($this->empresa('Sede B'));
        $u = $this->crearAnalista($a);

        $p = $this->guardarTest($u, ['ip' => '200.44.32.12', 'enlace_id' => $respaldo->id]);
        $this->assertSame([$respaldo->id, 'manual'], [$p->enlace_id, $p->enlace_asignado_por]);

        $p = $this->guardarTest($u, ['ip' => '200.44.32.12', 'enlace_id' => $ajeno->id]);
        $this->assertSame([$fibra->id, 'ip'], [$p->enlace_id, $p->enlace_asignado_por]);
    }

    /** @test */
    public function la_pantalla_de_velocidad_muestra_el_resumen_por_enlace_y_la_guia(): void
    {
        $a = $this->empresa();
        $e = $this->enlace($a, ['nombre' => 'Fibra principal', 'gateway' => '192.168.1.1', 'bajada_contratada_mbps' => 100]);
        MonitorPruebaVelocidad::create(['empresa_id' => $a->id, 'usuario_id' => $this->crearAdmin()->id, 'enlace_id' => $e->id,
            'enlace_asignado_por' => 'manual', 'bajada_mbps' => 45, 'subida_mbps' => 20]);

        Livewire::actingAs($this->crearAnalista($a))->test(PruebaVelocidad::class)
            ->assertSee('Fibra principal')
            ->assertSee('45 % del plan')
            ->assertSee('route change 0.0.0.0 mask 0.0.0.0 192.168.1.1')
            ->set('enlace_filtro', 'sin')
            ->assertDontSee('elegido a mano');
    }
}
