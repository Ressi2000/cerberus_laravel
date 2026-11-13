<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\AlmacenTable;
use App\Livewire\Mantenimientos\MantenimientoDetalle;
use App\Livewire\Mantenimientos\ReportarProblemaModal;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
use App\Models\PiezaExtraida;
use App\Models\User;
use App\Services\ExtraccionPiezaService;
use App\Services\PiezaManualService;
use App\Services\SustitucionPiezaService;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * B2–B8 (flujo de los casos) y C3–C7 (almacén).
 */
class FlujoCasosAlmacenTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private CategoriaEquipo $categoria;
    private User $analista;
    private User $admin;

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

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    // ─── Fixtures ──────────────────────────────────────────────────────────

    private function estado(string $nombre): int
    {
        return EstadoEquipo::where('nombre', $nombre)->value('id');
    }

    private function crearEquipo(string $estado = 'Disponible'): Equipo
    {
        return Equipo::forceCreate([
            'empresa_id'     => $this->empresa->id,
            'categoria_id'   => $this->categoria->id,
            'estado_id'      => $this->estado($estado),
            'codigo_interno' => 'EQ-' . uniqid(),
            'activo'         => true,
        ]);
    }

    private function crearCaso(string $tipo, string $estado, ?Equipo $equipo = null, ?int $estadoAnterior = null): Mantenimiento
    {
        $equipo ??= $this->crearEquipo();

        return Mantenimiento::create([
            'empresa_id'                => $this->empresa->id,
            'equipo_id'                 => $equipo->id,
            'tipo'                      => $tipo,
            'estado'                    => $estado,
            'reportado_por_id'          => $this->analista->id,
            'fecha_inicio'              => now()->toDateString(),
            'estado_equipo_anterior_id' => $estadoAnterior,
        ]);
    }

    private function crearComponente(int $stock = 0, bool $activo = true): ComponenteAlmacen
    {
        return ComponenteAlmacen::create([
            'empresa_id'   => $this->empresa->id,
            'nombre'       => 'Componente ' . uniqid(),
            'unidad'       => 'unidad',
            'stock_actual' => $stock,
            'activo'       => $activo,
            'creado_por'   => $this->analista->id,
        ]);
    }

    private function crearAtributoRam(): AtributoEquipo
    {
        return AtributoEquipo::create([
            'categoria_id' => $this->categoria->id,
            'nombre'       => 'RAM',
            'slug'         => 'ram-' . uniqid(),
            'tipo'         => 'string',
            'reutilizable' => true,
        ]);
    }

    // ─── B2: eliminar ──────────────────────────────────────────────────────

    /** @test */
    public function analista_puede_eliminar_un_caso_abierto_y_libera_el_equipo(): void
    {
        $equipo = $this->crearEquipo('En reparación');
        $caso = $this->crearCaso('Correctivo', 'Reportado', $equipo, $this->estado('Disponible'));

        Livewire::actingAs($this->analista)
            ->test(MantenimientoDetalle::class, ['mantenimientoId' => $caso->id])
            ->call('eliminar');

        $this->assertSoftDeleted($caso);
        $this->assertSame($this->estado('Disponible'), (int) $equipo->fresh()->estado_id);
    }

    /** @test */
    public function nadie_puede_eliminar_un_caso_cerrado(): void
    {
        $caso = $this->crearCaso('Correctivo', 'Cerrado');

        Livewire::actingAs($this->analista)
            ->test(MantenimientoDetalle::class, ['mantenimientoId' => $caso->id])
            ->call('eliminar')
            ->assertForbidden();

        Livewire::actingAs($this->admin)
            ->test(MantenimientoDetalle::class, ['mantenimientoId' => $caso->id])
            ->call('eliminar');

        $this->assertNotSoftDeleted($caso);
    }

    // ─── B3 / B4: reabrir ──────────────────────────────────────────────────

    /** @test */
    public function analista_puede_reabrir_y_se_usa_el_estado_actual_del_equipo(): void
    {
        // Se cerró cuando el equipo estaba "Asignado"; después quedó "Disponible".
        $equipo = $this->crearEquipo('Disponible');
        $caso = $this->crearCaso('Correctivo', 'Cerrado', $equipo, $this->estado('Asignado'));

        Livewire::actingAs($this->analista)
            ->test(MantenimientoDetalle::class, ['mantenimientoId' => $caso->id])
            ->call('abrirReabrir')
            ->set('motivoReapertura', 'Volvió a fallar igual')
            ->call('confirmarReabrir');

        $caso->refresh();
        $this->assertSame('En reparación', $caso->estado);
        $this->assertSame($this->estado('En reparación'), (int) $equipo->fresh()->estado_id);
        $this->assertSame($this->estado('Disponible'), (int) $caso->estado_equipo_anterior_id);
    }

    /** @test */
    public function no_se_reabre_si_el_equipo_ya_tiene_otro_caso_abierto(): void
    {
        $equipo = $this->crearEquipo('Disponible');
        $cerrado = $this->crearCaso('Correctivo', 'Cerrado', $equipo, $this->estado('Disponible'));
        $this->crearCaso('Preventivo', 'Programado', $equipo);

        $this->expectException(\InvalidArgumentException::class);
        $cerrado->reabrir($this->analista, 'motivo');
    }

    /** @test */
    public function al_liberar_un_equipo_asignado_sin_asignacion_activa_vuelve_a_disponible(): void
    {
        $equipo = $this->crearEquipo('En mantenimiento');
        $caso = $this->crearCaso('Preventivo', 'En proceso', $equipo, $this->estado('Asignado'));

        $caso->completar();

        $this->assertSame($this->estado('Disponible'), (int) $equipo->fresh()->estado_id);
    }

    // ─── B5 / B7: preventivo programado ────────────────────────────────────

    /** @test */
    public function un_preventivo_programado_no_bloquea_devoluciones_ni_permite_trabajar(): void
    {
        $equipo = $this->crearEquipo('Asignado');
        $programado = $this->crearCaso('Preventivo', 'Programado', $equipo);

        $this->assertNull(Mantenimiento::bloqueoActivoDe($equipo->id));
        $this->assertFalse($programado->permiteRegistrarTrabajo());

        $programado->avanzarEstado(); // En proceso: ahora sí bloquea

        $this->assertSame($programado->id, Mantenimiento::bloqueoActivoDe($equipo->id)?->id);
        $this->assertTrue($programado->fresh()->permiteRegistrarTrabajo());
    }

    // ─── B6: reportar problema ─────────────────────────────────────────────

    /** @test */
    public function reportar_problema_completa_el_preventivo_y_deja_nota_de_la_reparacion(): void
    {
        $equipo = $this->crearEquipo('En mantenimiento');
        $preventivo = $this->crearCaso('Preventivo', 'En proceso', $equipo, $this->estado('Disponible'));

        Livewire::actingAs($this->analista)
            ->test(ReportarProblemaModal::class)
            ->call('abrir', $preventivo->id)
            ->set('falla_reportada', 'El disco hace ruido')
            ->call('guardar');

        $preventivo->refresh();
        $correctivo = Mantenimiento::where('mantenimiento_origen_id', $preventivo->id)->firstOrFail();

        $this->assertSame('Completado', $preventivo->estado);
        $this->assertStringContainsString("reparación #{$correctivo->id}", $preventivo->observaciones);
        $this->assertSame($this->estado('En reparación'), (int) $equipo->fresh()->estado_id);
    }

    // ─── C3: sustituir no pierde la pieza que sale ─────────────────────────

    /** @test */
    public function sustituir_registra_la_pieza_que_sale_del_equipo(): void
    {
        $atributo = $this->crearAtributoRam();
        $caso = $this->crearCaso('Correctivo', 'En reparación');

        EquipoAtributoValor::create([
            'equipo_id'   => $caso->equipo_id,
            'atributo_id' => $atributo->id,
            'valor'       => '4GB',
            'es_actual'   => true,
            'creado_por'  => $this->analista->id,
        ]);

        $nueva = app(ExtraccionPiezaService::class)
            ->registrarNueva($this->empresa->id, $atributo, ['valor' => '8GB'], $this->analista)
            ->first();

        app(SustitucionPiezaService::class)->instalar(
            $nueva, $caso->equipo, SustitucionPiezaService::MODO_SUSTITUIR, $this->analista, $caso,
        );

        $saliente = PiezaExtraida::where('equipo_origen_id', $caso->equipo_id)->firstOrFail();
        $this->assertSame(['valor' => '4GB'], $saliente->valor_extraido);
        $this->assertSame(PiezaExtraida::ESTADO_EN_ALMACEN, $saliente->estado);
        $this->assertSame(PiezaExtraida::MOTIVO_SUSTITUCION_REPARACION, $saliente->motivo);

        $this->assertSame('8GB', EquipoAtributoValor::where('equipo_id', $caso->equipo_id)
            ->where('atributo_id', $atributo->id)->where('es_actual', true)->value('valor'));
    }

    // ─── C4: pendientes al terminar ────────────────────────────────────────

    /** @test */
    public function al_terminar_el_caso_los_pendientes_se_cancelan(): void
    {
        $caso = $this->crearCaso('Preventivo', 'En proceso', null, $this->estado('Disponible'));
        $item = $caso->pedirComponente($this->crearComponente(0), 1, $this->analista);
        $this->assertTrue($caso->fresh()->esperando_componente);

        $caso->fresh()->completar();

        $this->assertSame(MantenimientoComponente::ESTADO_CANCELADO, $item->fresh()->estado);
        $this->assertFalse($caso->fresh()->esperando_componente);
    }

    // ─── C5: el componente de rescates se reactiva ─────────────────────────

    /** @test */
    public function un_rescate_reactiva_su_componente_si_estaba_desactivado_o_eliminado(): void
    {
        $atributo = $this->crearAtributoRam();
        $servicio = app(ExtraccionPiezaService::class);

        $componente = $servicio->registrarNueva($this->empresa->id, $atributo, ['valor' => '8GB'], $this->analista)
            ->first()->componenteAlmacen;
        $componente->update(['activo' => false]);
        $componente->delete();

        $otra = $servicio->registrarNueva($this->empresa->id, $atributo, ['valor' => '8GB'], $this->analista)->first();

        $this->assertSame($componente->id, $otra->componente_almacen_id);
        $componente->refresh();
        $this->assertFalse($componente->trashed());
        $this->assertTrue($componente->activo);
        $this->assertSame(2, $componente->stock_actual);
    }

    // ─── C6: desactivar ────────────────────────────────────────────────────

    /** @test */
    public function no_se_desactiva_un_componente_con_stock_o_pendientes(): void
    {
        $conStock = $this->crearComponente(3);

        Livewire::actingAs($this->admin)
            ->test(AlmacenTable::class)
            ->call('desactivar', $conStock->id);
        $this->assertTrue($conStock->fresh()->activo);

        $sinStock = $this->crearComponente(0);
        $caso = $this->crearCaso('Correctivo', 'En reparación', null, $this->estado('Disponible'));
        $caso->pedirComponente($sinStock, 1, $this->analista); // queda Pendiente

        $this->expectException(\InvalidArgumentException::class);
        $sinStock->fresh()->desactivar();
    }

    // ─── C7: identificadores ───────────────────────────────────────────────

    /** @test */
    public function los_identificadores_no_se_repiten_dentro_de_un_componente(): void
    {
        $componente = $this->crearComponente(0);
        $servicio = app(PiezaManualService::class);

        $servicio->registrar($componente, 1, PiezaExtraida::CONDICION_NUEVO, $this->analista, identificador: 'SN-1');

        // Una pieza registrada con identificador manual no hace repetir la numeración automática.
        $auto = $servicio->registrar($componente->fresh(), 2, PiezaExtraida::CONDICION_NUEVO, $this->analista)
            ->pluck('identificador')->all();
        $this->assertSame(["AUTO-{$componente->id}-001", "AUTO-{$componente->id}-002"], $auto);

        $this->expectException(\InvalidArgumentException::class);
        $servicio->registrar($componente->fresh(), 1, PiezaExtraida::CONDICION_NUEVO, $this->analista, identificador: 'SN-1');
    }
}
