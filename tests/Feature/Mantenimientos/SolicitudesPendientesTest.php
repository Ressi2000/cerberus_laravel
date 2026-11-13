<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\SolicitudesPendientes;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
use App\Models\User;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bandeja de solicitudes pendientes del Almacén.
 */
class SolicitudesPendientesTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresaA;
    private Empresa $empresaB;
    private CategoriaEquipo $categoria;
    private User $analista;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->seed(EstadosEquiposSeeder::class);

        $this->empresaA  = Empresa::create(['nombre' => 'Empresa A']);
        $this->empresaB  = Empresa::create(['nombre' => 'Empresa B']);
        $this->categoria = CategoriaEquipo::create(['nombre' => 'Laptop']);

        $this->analista = User::factory()->create([
            'empresa_id'        => $this->empresaA->id,
            'empresa_activa_id' => $this->empresaA->id,
        ]);
        $this->analista->assignRole('Analista');
        $this->analista->empresasAsignadas()->attach($this->empresaA->id);
    }

    private function crearCaso(Empresa $empresa, string $estado = 'En reparación'): Mantenimiento
    {
        $equipo = Equipo::forceCreate([
            'empresa_id'     => $empresa->id,
            'categoria_id'   => $this->categoria->id,
            'estado_id'      => EstadoEquipo::where('nombre', 'En reparación')->value('id'),
            'codigo_interno' => 'EQ-' . uniqid(),
            'activo'         => true,
        ]);

        return Mantenimiento::create([
            'empresa_id'                => $empresa->id,
            'equipo_id'                 => $equipo->id,
            'tipo'                      => 'Correctivo',
            'estado'                    => $estado,
            'reportado_por_id'          => $this->analista->id,
            'fecha_inicio'              => now()->toDateString(),
            'estado_equipo_anterior_id' => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
        ]);
    }

    private function crearComponente(Empresa $empresa): ComponenteAlmacen
    {
        return ComponenteAlmacen::create([
            'empresa_id'   => $empresa->id,
            'nombre'       => 'Componente ' . uniqid(),
            'unidad'       => 'unidad',
            'stock_actual' => 0,
            'activo'       => true,
            'creado_por'   => $this->analista->id,
        ]);
    }

    /** Pide sin stock (queda Pendiente) y devuelve la solicitud. */
    private function pedirSinStock(Mantenimiento $caso, ComponenteAlmacen $componente, int $cantidad = 1): MantenimientoComponente
    {
        $item = $caso->pedirComponente($componente, $cantidad, $this->analista);
        $this->assertSame('Pendiente', $item->estado);

        return $item;
    }

    /** @test */
    public function la_pagina_es_para_admin_y_analista(): void
    {
        $this->actingAs($this->analista)->get(route('admin.almacen.solicitudes'))->assertOk();

        $usuario = User::factory()->create(['empresa_activa_id' => $this->empresaA->id]);
        $usuario->assignRole('Usuario');
        $this->actingAs($usuario)->get(route('admin.almacen.solicitudes'))->assertForbidden();
    }

    /** @test */
    public function el_analista_solo_ve_pendientes_de_su_empresa_y_de_casos_abiertos(): void
    {
        $propia = $this->pedirSinStock($this->crearCaso($this->empresaA), $this->crearComponente($this->empresaA));

        $ajenaCaso = $this->crearCaso($this->empresaB);
        $this->pedirSinStock($ajenaCaso, $this->crearComponente($this->empresaB));

        // Un caso terminado cancela sus pendientes: no aparecen.
        $cerrado = $this->crearCaso($this->empresaA, 'En reparación');
        $this->pedirSinStock($cerrado, $this->crearComponente($this->empresaA));
        $cerrado->update(['estado' => 'Reparado']);
        $cerrado->evidencias()->create(['ruta_archivo' => 'x.jpg', 'tipo' => 'Después', 'subido_por_id' => $this->analista->id]);
        $cerrado->cerrar();

        $ids = Livewire::actingAs($this->analista)
            ->test(SolicitudesPendientes::class)
            ->instance()->solicitudes->pluck('id')->all();

        $this->assertSame([$propia->id], $ids);
    }

    /** @test */
    public function explica_por_que_no_se_puede_entregar_y_reserva_el_stock_para_la_mas_antigua(): void
    {
        $componente = $this->crearComponente($this->empresaA);
        $vieja = $this->pedirSinStock($this->crearCaso($this->empresaA), $componente);
        $nueva = $this->pedirSinStock($this->crearCaso($this->empresaA), $componente);

        $componente->registrarEntrada(1, $this->analista); // alcanza solo para una

        $solicitudes = Livewire::actingAs($this->analista)
            ->test(SolicitudesPendientes::class)
            ->instance()->solicitudes->keyBy('id');

        $this->assertTrue($solicitudes[$vieja->id]->entregable);
        $this->assertFalse($solicitudes[$nueva->id]->entregable);
        $this->assertStringContainsString('Falta stock', $solicitudes[$nueva->id]->motivo_no_entregable);
    }

    /** @test */
    public function no_es_entregable_si_el_caso_no_esta_en_trabajo(): void
    {
        $componente = $this->crearComponente($this->empresaA);
        $caso = $this->crearCaso($this->empresaA);
        $item = $this->pedirSinStock($caso, $componente);
        $componente->registrarEntrada(1, $this->analista);
        $caso->marcarReparado();

        $solicitud = Livewire::actingAs($this->analista)
            ->test(SolicitudesPendientes::class)
            ->instance()->solicitudes->firstWhere('id', $item->id);

        $this->assertFalse($solicitud->entregable);
        $this->assertStringContainsString('Reparado', $solicitud->motivo_no_entregable);
    }

    /** @test */
    public function entregar_desde_la_bandeja_descuenta_stock_y_marca_entregado(): void
    {
        $componente = $this->crearComponente($this->empresaA);
        $caso = $this->crearCaso($this->empresaA);
        $item = $this->pedirSinStock($caso, $componente, 2);
        $componente->registrarEntrada(2, $this->analista);

        Livewire::actingAs($this->analista)
            ->test(SolicitudesPendientes::class)
            ->call('entregar', $item->id);

        $this->assertSame('Entregado', $item->fresh()->estado);
        $this->assertSame(0, $componente->fresh()->stock_actual);
        $this->assertFalse($caso->fresh()->esperando_componente);
    }

    /** @test */
    public function no_se_puede_entregar_una_solicitud_de_otra_empresa(): void
    {
        $componenteB = $this->crearComponente($this->empresaB);
        $itemB = $this->pedirSinStock($this->crearCaso($this->empresaB), $componenteB);
        $componenteB->registrarEntrada(1, $this->analista);

        Livewire::actingAs($this->analista)
            ->test(SolicitudesPendientes::class)
            ->call('entregar', $itemB->id);

        $this->assertSame('Pendiente', $itemB->fresh()->estado);
        $this->assertSame(1, $componenteB->fresh()->stock_actual);
    }
}
