<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Almacen\RegistrarEntradaModal;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\FamiliaComponente;
use App\Models\Mantenimiento;
use App\Models\MantenimientoEvidencia;
use App\Models\User;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Lo que reportaron los analistas de soporte: 403 al registrar una entrada
 * y al abrir las fotos de un caso de su empresa. Los permisos comparaban la
 * empresa con === y fallaban cuando uno de los dos lados venía como texto.
 */
class AnalistaPermisosTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $analista;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);

        $this->empresa  = Empresa::create(['nombre' => 'Empresa A']);
        $this->analista = User::factory()->create(['empresa_id' => $this->empresa->id, 'empresa_activa_id' => $this->empresa->id]);
        $this->analista->assignRole('Analista');
        $this->analista->empresasAsignadas()->attach($this->empresa->id);
    }

    /** @test */
    public function el_analista_registra_la_entrada_de_una_variante_nueva(): void
    {
        $familia = FamiliaComponente::create(['nombre' => 'Bisagras', 'control' => 'granel', 'campos' => []]);

        Livewire::actingAs($this->analista)
            ->test(RegistrarEntradaModal::class)
            ->call('abrir')
            ->assertOk()
            ->set('familiaId', (string) $familia->id)
            ->set('componenteId', 'nueva')
            ->set('nuevoNombre', 'Bisagra Dell 15"')
            ->set('cantidad', 4)
            ->call('guardar')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSame(4, ComponenteAlmacen::where('nombre', 'Bisagra Dell 15"')->value('stock_actual'));
    }

    /** @test */
    public function el_analista_ve_las_fotos_de_un_caso_de_su_empresa(): void
    {
        Storage::fake(MantenimientoEvidencia::DISCO);

        $equipo = Equipo::forceCreate([
            'empresa_id' => $this->empresa->id, 'categoria_id' => CategoriaEquipo::create(['nombre' => 'Laptop'])->id,
            'estado_id' => EstadoEquipo::where('nombre', 'En reparación')->value('id'),
            'codigo_interno' => 'EQ-1', 'activo' => true,
        ]);
        // empresa_id como texto, igual que llega de un formulario.
        $caso = Mantenimiento::create([
            'empresa_id' => (string) $this->empresa->id, 'equipo_id' => $equipo->id, 'tipo' => 'Correctivo',
            'estado' => 'En reparación', 'reportado_por_id' => $this->analista->id, 'fecha_inicio' => now()->toDateString(),
        ]);
        $ruta = UploadedFile::fake()->image('antes.jpg')->store('mantenimientos', MantenimientoEvidencia::DISCO);
        $foto = $caso->evidencias()->create(['ruta_archivo' => $ruta, 'tipo' => 'Antes', 'subido_por_id' => $this->analista->id]);

        $this->actingAs($this->analista)->get($foto->url())->assertOk();

        // Y el permiso con los dos lados de distinto tipo.
        $this->analista->empresa_activa_id = (string) $this->empresa->id;
        $this->assertTrue($this->analista->can('view', $caso->fresh()));
    }
}
