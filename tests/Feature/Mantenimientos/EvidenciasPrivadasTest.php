<?php

namespace Tests\Feature\Mantenimientos;

use App\Livewire\Mantenimientos\MantenimientoEvidenciasPanel;
use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
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
 * Las fotos de evidencia viven en el disco privado y solo se sirven a quien
 * puede ver el caso.
 */
class EvidenciasPrivadasTest extends TestCase
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

        Storage::fake(MantenimientoEvidencia::DISCO);
        Storage::fake(MantenimientoEvidencia::DISCO_ANTERIOR);

        $this->empresaA  = Empresa::create(['nombre' => 'Empresa A']);
        $this->empresaB  = Empresa::create(['nombre' => 'Empresa B']);
        $this->categoria = CategoriaEquipo::create(['nombre' => 'Laptop']);

        $this->analista = $this->crearAnalista($this->empresaA);
    }

    private function crearAnalista(Empresa $empresa): User
    {
        $user = User::factory()->create(['empresa_id' => $empresa->id, 'empresa_activa_id' => $empresa->id]);
        $user->assignRole('Analista');
        $user->empresasAsignadas()->attach($empresa->id);

        return $user;
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
            'empresa_id'       => $empresa->id,
            'equipo_id'        => $equipo->id,
            'tipo'             => 'Correctivo',
            'estado'           => $estado,
            'reportado_por_id' => $this->analista->id,
            'fecha_inicio'     => now()->toDateString(),
        ]);
    }

    /** Foto guardada en el disco indicado (por defecto, el privado). */
    private function crearFoto(Mantenimiento $caso, string $disco = MantenimientoEvidencia::DISCO): MantenimientoEvidencia
    {
        $ruta = 'mantenimientos/' . uniqid() . '.jpg';
        Storage::disk($disco)->put($ruta, 'contenido-de-prueba');

        return $caso->evidencias()->create([
            'ruta_archivo'  => $ruta,
            'tipo'          => 'Antes',
            'subido_por_id' => $this->analista->id,
        ]);
    }

    /** @test */
    public function una_foto_nueva_se_guarda_en_el_disco_privado(): void
    {
        $caso = $this->crearCaso($this->empresaA);

        Livewire::actingAs($this->analista)
            ->test(MantenimientoEvidenciasPanel::class, ['mantenimientoId' => $caso->id])
            ->set('foto', UploadedFile::fake()->image('durante.jpg'))
            ->set('tipoFoto', 'Durante')
            ->call('subir');

        $ruta = $caso->evidencias()->value('ruta_archivo');

        Storage::disk(MantenimientoEvidencia::DISCO)->assertExists($ruta);
        Storage::disk(MantenimientoEvidencia::DISCO_ANTERIOR)->assertMissing($ruta);
    }

    /** @test */
    public function la_url_de_la_foto_es_la_ruta_protegida(): void
    {
        $foto = $this->crearFoto($this->crearCaso($this->empresaA));

        $this->assertSame(
            route('admin.mantenimientos.evidencias.show', [$foto->mantenimiento_id, $foto->id]),
            $foto->url(),
        );
    }

    /** @test */
    public function solo_quien_puede_ver_el_caso_ve_la_foto(): void
    {
        $foto = $this->crearFoto($this->crearCaso($this->empresaA));
        $url  = $foto->url();

        $this->get($url)->assertRedirect(route('login'));

        $this->actingAs($this->analista)->get($url)->assertOk();

        $this->actingAs($this->crearAnalista($this->empresaB))->get($url)->assertForbidden();

        $usuario = User::factory()->create();
        $usuario->assignRole('Usuario');
        $this->actingAs($usuario)->get($url)->assertForbidden();

        // La URL directa /storage/... (sin firma) tampoco la entrega.
        $directa = $this->actingAs($this->analista)->get('/storage/' . $foto->ruta_archivo);
        $this->assertContains($directa->status(), [403, 404]);
    }

    /** @test */
    public function no_se_puede_pedir_una_foto_por_otro_caso(): void
    {
        $foto = $this->crearFoto($this->crearCaso($this->empresaA));
        $otroCaso = $this->crearCaso($this->empresaA);

        $this->actingAs($this->analista)
            ->get(route('admin.mantenimientos.evidencias.show', [$otroCaso->id, $foto->id]))
            ->assertNotFound();
    }

    /** @test */
    public function una_foto_antigua_en_el_disco_publico_se_sigue_mostrando(): void
    {
        $foto = $this->crearFoto($this->crearCaso($this->empresaA), MantenimientoEvidencia::DISCO_ANTERIOR);

        $this->actingAs($this->analista)->get($foto->url())->assertOk();
    }

    /** @test */
    public function el_comando_mueve_las_fotos_al_disco_privado(): void
    {
        $caso = $this->crearCaso($this->empresaA);
        $antigua = $this->crearFoto($caso, MantenimientoEvidencia::DISCO_ANTERIOR);
        Storage::disk(MantenimientoEvidencia::DISCO_ANTERIOR)->put('mantenimientos/huerfana.jpg', 'x');

        $this->artisan('cerberus:mover-evidencias-privadas')->assertExitCode(0);

        Storage::disk(MantenimientoEvidencia::DISCO)->assertExists($antigua->ruta_archivo);
        Storage::disk(MantenimientoEvidencia::DISCO_ANTERIOR)->assertMissing($antigua->ruta_archivo);
        $this->assertSame('contenido-de-prueba', Storage::disk(MantenimientoEvidencia::DISCO)->get($antigua->ruta_archivo));

        // La huérfana solo se borra si se pide.
        Storage::disk(MantenimientoEvidencia::DISCO_ANTERIOR)->assertExists('mantenimientos/huerfana.jpg');

        $this->artisan('cerberus:mover-evidencias-privadas', ['--borrar-huerfanas' => true])->assertExitCode(0);

        Storage::disk(MantenimientoEvidencia::DISCO_ANTERIOR)->assertMissing('mantenimientos/huerfana.jpg');
        Storage::disk(MantenimientoEvidencia::DISCO)->assertExists($antigua->ruta_archivo);
    }
}
