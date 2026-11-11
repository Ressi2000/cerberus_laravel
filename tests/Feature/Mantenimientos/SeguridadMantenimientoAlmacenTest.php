<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\ComponenteModal;
use App\Livewire\Almacen\ComponentePiezasModal;
use App\Livewire\Almacen\MovimientoStockModal;
use App\Livewire\Almacen\PiezaNuevaModal;
use App\Livewire\Mantenimientos\CrearMantenimiento;
use App\Livewire\Mantenimientos\MantenimientoBajaModal;
use App\Livewire\Mantenimientos\MantenimientoComponentesPanel;
use App\Livewire\Mantenimientos\MantenimientoDetalle;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Mantenimiento;
use App\Models\User;
use Database\Seeders\EstadosEquiposSeeder;
use Database\Seeders\MantenimientoPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cubre las correcciones de seguridad y de flujo de estados del módulo de
 * Mantenimiento/Reparación y Almacén:
 *
 *   A1 — entregar un componente pendiente de OTRO caso.
 *   A2 — pedir componentes de otra empresa o fuera del estado de trabajo.
 *   A3 — crear casos/componentes en otra empresa manipulando empresa_id.
 *   A4 — ver piezas de un componente de otra empresa.
 *   A5 — usar depósitos o equipos de otra empresa.
 *   B1 — transiciones de estado inválidas invocadas directamente.
 */
class SeguridadMantenimientoAlmacenTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresaA;
    private Empresa $empresaB;
    private CategoriaEquipo $categoria;
    private int $codigo = 0;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->seed(EstadosEquiposSeeder::class);
        $this->seed(MantenimientoPermisosSeeder::class);

        $this->empresaA  = Empresa::create(['nombre' => 'Empresa A']);
        $this->empresaB  = Empresa::create(['nombre' => 'Empresa B']);
        $this->categoria = CategoriaEquipo::create(['nombre' => 'Laptop']);

        Storage::fake('public');
    }

    // ─── Fixtures ──────────────────────────────────────────────────────────

    private function crearAnalista(Empresa $empresa): User
    {
        $user = User::factory()->create(['empresa_activa_id' => $empresa->id]);
        $user->assignRole('Analista');

        return $user;
    }

    private function crearAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrador');

        return $user;
    }

    private function crearEquipo(Empresa $empresa): Equipo
    {
        $this->codigo++;

        return Equipo::forceCreate([
            'empresa_id'        => $empresa->id,
            'categoria_id'      => $this->categoria->id,
            'estado_id'         => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
            'codigo_interno'    => 'EQ-' . $this->codigo,
            'fecha_adquisicion' => now()->subYear()->toDateString(),
            'activo'            => true,
        ]);
    }

    private function crearCaso(Empresa $empresa, string $tipo, string $estado, User $actor): Mantenimiento
    {
        return Mantenimiento::create([
            'empresa_id'       => $empresa->id,
            'equipo_id'        => $this->crearEquipo($empresa)->id,
            'tipo'             => $tipo,
            'estado'           => $estado,
            'reportado_por_id' => $actor->id,
            'fecha_inicio'     => now()->toDateString(),
        ]);
    }

    private function crearComponente(Empresa $empresa, User $actor, int $stock = 0): ComponenteAlmacen
    {
        return ComponenteAlmacen::create([
            'empresa_id'   => $empresa->id,
            'nombre'       => 'Componente ' . $empresa->nombre . ' ' . uniqid(),
            'unidad'       => 'unidad',
            'stock_actual' => $stock,
            'activo'       => true,
            'creado_por'   => $actor->id,
        ]);
    }

    private function crearFamilia(): \App\Models\FamiliaComponente
    {
        return \App\Models\FamiliaComponente::firstOrCreate(['nombre' => 'Otros'], ['control' => 'unidad', 'campos' => []]);
    }

    private function crearDeposito(Empresa $empresa, User $actor): Deposito
    {
        return Deposito::create([
            'empresa_id' => $empresa->id,
            'nombre'     => 'Depósito ' . $empresa->nombre,
            'activo'     => true,
            'creado_por' => $actor->id,
        ]);
    }

    // ─── B1: transiciones de estado en el modelo ───────────────────────────

    /** @test */
    public function no_se_puede_cerrar_una_reparacion_que_no_esta_reparada(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'Reportado', $analista);

        $this->expectException(\InvalidArgumentException::class);
        $caso->cerrar();
    }

    /** @test */
    public function cerrar_exige_la_foto_despues(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'Reparado', $analista);

        try {
            $caso->cerrar();
            $this->fail('Debió exigir la foto "Después".');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Reparado', $caso->fresh()->estado);
        }

        $caso->evidencias()->create(['ruta_archivo' => 'x.jpg', 'tipo' => 'Después', 'subido_por_id' => $analista->id]);
        $caso->cerrar();

        $this->assertSame('Cerrado', $caso->fresh()->estado);
    }

    /** @test */
    public function no_se_puede_completar_ni_cancelar_un_correctivo(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'En reparación', $analista);

        foreach (['completar', 'cancelar'] as $accion) {
            try {
                $caso->{$accion}();
                $this->fail("{$accion}() debió rechazarse en un Correctivo.");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame('En reparación', $caso->fresh()->estado);
            }
        }
    }

    /** @test */
    public function no_se_puede_completar_un_preventivo_programado(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso = $this->crearCaso($this->empresaA, 'Preventivo', 'Programado', $analista);

        $this->expectException(\InvalidArgumentException::class);
        $caso->completar();
    }

    /** @test */
    public function no_se_puede_marcar_reparado_fuera_de_en_reparacion(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'Reportado', $analista);

        $this->expectException(\InvalidArgumentException::class);
        $caso->marcarReparado();
    }

    /** @test */
    public function no_se_puede_avanzar_de_reportado_sin_diagnostico(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'Reportado', $analista);

        Livewire::actingAs($analista)
            ->test(MantenimientoDetalle::class, ['mantenimientoId' => $caso->id])
            ->call('avanzarEstado');

        $this->assertSame('Reportado', $caso->fresh()->estado);
    }

    /** @test */
    public function no_se_puede_dar_de_baja_un_caso_cerrado(): void
    {
        $admin = $this->crearAdmin();
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'Cerrado', $admin);
        $deposito = $this->crearDeposito($this->empresaA, $admin);

        $this->expectException(\InvalidArgumentException::class);
        $caso->marcarDadoDeBaja($admin, 'Sin arreglo', $deposito);
    }

    /** @test */
    public function livewire_rechaza_cerrar_un_caso_recien_reportado(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'Reportado', $analista);

        Livewire::actingAs($analista)
            ->test(MantenimientoDetalle::class, ['mantenimientoId' => $caso->id])
            ->call('cerrar')
            ->call('completar')
            ->call('marcarReparado');

        $this->assertSame('Reportado', $caso->fresh()->estado);
    }

    // ─── A1: entregar pendiente de otro caso ───────────────────────────────

    /** @test */
    public function no_se_puede_entregar_un_componente_pendiente_de_otro_caso(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);
        $analistaB = $this->crearAnalista($this->empresaB);

        $casoA = $this->crearCaso($this->empresaA, 'Correctivo', 'En reparación', $analistaA);
        $casoB = $this->crearCaso($this->empresaB, 'Correctivo', 'En reparación', $analistaB);

        $componenteB = $this->crearComponente($this->empresaB, $analistaB, 0);
        $itemB = $casoB->pedirComponente($componenteB, 1, $analistaB);
        $componenteB->registrarEntrada(5, $analistaB);

        Livewire::actingAs($analistaA)
            ->test(MantenimientoComponentesPanel::class, ['mantenimientoId' => $casoA->id])
            ->call('entregarPendiente', $itemB->id);

        $this->assertSame('Pendiente', $itemB->fresh()->estado);
        $this->assertSame(5, $componenteB->fresh()->stock_actual);
    }

    /** @test */
    public function el_modelo_rechaza_entregar_un_item_de_otro_caso(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso1 = $this->crearCaso($this->empresaA, 'Correctivo', 'En reparación', $analista);
        $caso2 = $this->crearCaso($this->empresaA, 'Correctivo', 'En reparación', $analista);

        $componente = $this->crearComponente($this->empresaA, $analista, 0);
        $item = $caso2->pedirComponente($componente, 1, $analista);

        $this->expectException(\InvalidArgumentException::class);
        $caso1->entregarComponentePendiente($item, $analista);
    }

    // ─── A2: pedir componentes ─────────────────────────────────────────────

    /** @test */
    public function no_se_puede_pedir_un_componente_de_otra_empresa(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);
        $casoA = $this->crearCaso($this->empresaA, 'Correctivo', 'En reparación', $analistaA);
        $componenteB = $this->crearComponente($this->empresaB, $this->crearAdmin(), 10);

        Livewire::actingAs($analistaA)
            ->test(MantenimientoComponentesPanel::class, ['mantenimientoId' => $casoA->id])
            ->set('componente_id', (string) $componenteB->id)
            ->set('cantidad', 2)
            ->call('pedir')
            ->assertHasErrors('componente_id');

        $this->assertSame(10, $componenteB->fresh()->stock_actual);
        $this->assertSame(0, $casoA->componentes()->count());
    }

    /** @test */
    public function no_se_puede_pedir_un_componente_si_el_caso_no_esta_en_reparacion(): void
    {
        $analista = $this->crearAnalista($this->empresaA);
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'Reportado', $analista);
        $componente = $this->crearComponente($this->empresaA, $analista, 10);

        Livewire::actingAs($analista)
            ->test(MantenimientoComponentesPanel::class, ['mantenimientoId' => $caso->id])
            ->set('componente_id', (string) $componente->id)
            ->set('cantidad', 1)
            ->call('pedir');

        $this->assertSame(10, $componente->fresh()->stock_actual);
        $this->assertSame(0, $caso->componentes()->count());
    }

    /** @test */
    public function el_id_del_caso_en_los_paneles_no_se_puede_cambiar_desde_el_navegador(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);
        $casoA = $this->crearCaso($this->empresaA, 'Correctivo', 'En reparación', $analistaA);
        $casoB = $this->crearCaso($this->empresaB, 'Correctivo', 'En reparación', $this->crearAdmin());

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($analistaA)
            ->test(MantenimientoComponentesPanel::class, ['mantenimientoId' => $casoA->id])
            ->set('mantenimientoId', $casoB->id);
    }

    // ─── A3: empresa manipulada ────────────────────────────────────────────

    /** @test */
    public function analista_no_puede_crear_un_caso_con_un_equipo_de_otra_empresa(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);
        $equipoB = $this->crearEquipo($this->empresaB);

        Livewire::actingAs($analistaA)
            ->test(CrearMantenimiento::class)
            ->set('empresa_id', (string) $this->empresaB->id)
            ->set('equipo_id', (string) $equipoB->id)
            ->set('tipo', 'Correctivo')
            ->set('falla_reportada', 'No enciende')
            ->set('fotoAntes', UploadedFile::fake()->image('antes.jpg'))
            ->call('guardar')
            ->assertHasErrors('equipo_id');

        $this->assertSame(0, Mantenimiento::count());
    }

    /** @test */
    public function analista_crea_el_caso_en_su_empresa_activa(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);
        $equipoA = $this->crearEquipo($this->empresaA);

        Livewire::actingAs($analistaA)
            ->test(CrearMantenimiento::class)
            ->set('equipo_id', (string) $equipoA->id)
            ->set('tipo', 'Correctivo')
            ->set('falla_reportada', 'No enciende')
            ->set('fotoAntes', UploadedFile::fake()->image('antes.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame($this->empresaA->id, (int) Mantenimiento::first()->empresa_id);
    }

    /** @test */
    public function analista_no_puede_crear_un_componente_en_otra_empresa(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);

        Livewire::actingAs($analistaA)
            ->test(ComponenteModal::class)
            ->call('abrirCrear')
            ->set('empresa_id', (string) $this->empresaB->id)
            ->set('familia_id', (string) $this->crearFamilia()->id)
            ->set('nombre', 'Pasta térmica')
            ->call('guardar');

        $componente = ComponenteAlmacen::where('nombre', 'Pasta térmica')->firstOrFail();
        $this->assertSame($this->empresaA->id, (int) $componente->empresa_id);
    }

    /** @test */
    public function editar_un_componente_no_cambia_su_empresa(): void
    {
        $admin = $this->crearAdmin();
        $componente = $this->crearComponente($this->empresaA, $admin, 3);

        Livewire::actingAs($admin)
            ->test(ComponenteModal::class)
            ->call('abrirEditar', $componente->id)
            ->set('familia_id', (string) $this->crearFamilia()->id)
            ->set('empresa_id', (string) $this->empresaB->id)
            ->set('nombre', 'Nuevo nombre')
            ->call('guardar');

        $componente->refresh();
        $this->assertSame('Nuevo nombre', $componente->nombre);
        $this->assertSame($this->empresaA->id, (int) $componente->empresa_id);
    }

    /** @test */
    public function pieza_nueva_requiere_autorizacion(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('Usuario');

        Livewire::actingAs($usuario)
            ->test(PiezaNuevaModal::class)
            ->call('guardar')
            ->assertForbidden();
    }

    // ─── A4: ver piezas de otra empresa ────────────────────────────────────

    /** @test */
    public function analista_no_puede_ver_piezas_de_un_componente_de_otra_empresa(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);
        $componenteB = $this->crearComponente($this->empresaB, $this->crearAdmin());

        Livewire::actingAs($analistaA)
            ->test(ComponentePiezasModal::class)
            ->call('abrir', $componenteB->id)
            ->assertForbidden();
    }

    // ─── A5: depósito / equipo de otra empresa ─────────────────────────────

    /** @test */
    public function no_se_puede_dar_de_baja_hacia_un_deposito_de_otra_empresa(): void
    {
        $admin = $this->crearAdmin();
        $caso = $this->crearCaso($this->empresaA, 'Correctivo', 'En reparación', $admin);
        $depositoB = $this->crearDeposito($this->empresaB, $admin);

        Livewire::actingAs($admin)
            ->test(MantenimientoBajaModal::class)
            ->call('abrir', $caso->id)
            ->set('motivo', 'Sin arreglo')
            ->set('depositoId', $depositoB->id)
            ->call('confirmar')
            ->assertHasErrors('depositoId');

        $this->assertSame('En reparación', $caso->fresh()->estado);
    }

    /** @test */
    public function entrada_reutilizada_no_acepta_equipo_de_otra_empresa(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);
        $componenteA = $this->crearComponente($this->empresaA, $analistaA, 0);
        $equipoB = $this->crearEquipo($this->empresaB);

        Livewire::actingAs($analistaA)
            ->test(MovimientoStockModal::class)
            ->call('abrir', $componenteA->id)
            ->set('tipo', 'Entrada')
            ->set('cantidad', 1)
            ->set('condicion', 'reutilizado')
            ->set('equipoOrigenId', $equipoB->id)
            ->call('guardar')
            ->assertHasErrors('equipoOrigenId');

        $this->assertSame(0, $componenteA->fresh()->stock_actual);
    }

    /** @test */
    public function salida_danada_no_acepta_deposito_de_otra_empresa(): void
    {
        $analistaA = $this->crearAnalista($this->empresaA);
        $componenteA = $this->crearComponente($this->empresaA, $analistaA, 4);
        $depositoB = $this->crearDeposito($this->empresaB, $this->crearAdmin());

        Livewire::actingAs($analistaA)
            ->test(MovimientoStockModal::class)
            ->call('abrir', $componenteA->id)
            ->set('tipo', 'Salida')
            ->set('cantidad', 1)
            ->set('danado', true)
            ->set('depositoId', $depositoB->id)
            ->call('guardar')
            ->assertHasErrors('depositoId');

        $this->assertSame(4, $componenteA->fresh()->stock_actual);
    }
}
