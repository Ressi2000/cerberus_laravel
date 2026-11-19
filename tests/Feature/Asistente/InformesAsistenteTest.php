<?php

namespace Tests\Feature\Asistente;

use App\Asistente\Herramientas;
use App\Asistente\Informes\Tabulador;
use App\Livewire\Asistente\ChatAsistente;
use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Models\User;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Informes PDF/Excel desde Cerberus Bot y búsqueda de personas.
 */
class InformesAsistenteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $analista;
    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);

        $this->empresa = Empresa::create(['nombre' => 'Empresa A']);
        $ubicacion = Ubicacion::create(['nombre' => 'Sede A', 'empresa_id' => $this->empresa->id, 'activo' => true]);
        $this->admin = User::factory()->create(['empresa_id' => $this->empresa->id, 'empresa_activa_id' => $this->empresa->id]);
        $this->admin->assignRole('Administrador');
        $this->analista = User::factory()->create(['empresa_id' => $this->empresa->id, 'empresa_activa_id' => $this->empresa->id, 'ubicacion_id' => $ubicacion->id]);
        $this->analista->assignRole('Analista');
        $this->analista->empresasAsignadas()->attach($this->empresa->id);

        $laptop = CategoriaEquipo::create(['nombre' => 'Laptop']);
        foreach (range(1, 130) as $i) {   // más que el límite del chat (100)
            Equipo::forceCreate([
                'empresa_id' => $this->empresa->id, 'categoria_id' => $laptop->id, 'ubicacion_id' => $ubicacion->id,
                'estado_id' => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
                'codigo_interno' => sprintf('EQ-%05d', $i), 'activo' => true,
            ]);
        }

        config(['asistente.habilitado' => true, 'asistente.gemini.api_key' => 'clave', 'asistente.gemini.modelo' => 'gemini-prueba']);
    }

    private function informe(User $actor, array $args): array
    {
        return app(Herramientas::class)->ejecutar('generar_informe', $actor, $args);
    }

    /** @test */
    public function genera_un_pdf_con_todos_los_resultados_y_solo_lo_descarga_quien_lo_pidio(): void
    {
        $r = $this->informe($this->analista, ['consulta' => 'buscar_equipos', 'parametros' => '{"categoria":"Laptop"}', 'titulo' => 'Laptops de la empresa', 'resumen' => 'Hay **130** laptops.']);

        $this->assertTrue($r['ok']);
        $this->assertSame(130, $r['datos']['filas']);            // sin el corte de 100 del chat
        $url = $r['datos']['_adjunto']['url'];

        $this->actingAs($this->analista)->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload();

        $this->actingAs($this->admin)->get($url)->assertForbidden();              // no es suyo
        $this->actingAs($this->analista)->get(str_replace('signature=', 'signature=x', $url))->assertForbidden();   // firma alterada
    }

    /** @test */
    public function genera_un_excel_con_una_hoja_por_tabla(): void
    {
        $r = $this->informe($this->admin, ['consulta' => 'resumen_inventario', 'parametros' => '{}', 'formato' => 'excel', 'titulo' => 'Inventario']);

        $respuesta = $this->actingAs($this->admin)->get($r['datos']['_adjunto']['url']);
        $respuesta->assertOk()->assertDownload();
        $this->assertStringEndsWith('.xlsx', $respuesta->headers->get('content-disposition'));
    }

    /** @test */
    public function el_informe_vence_y_las_consultas_invalidas_se_explican(): void
    {
        $r = $this->informe($this->admin, ['consulta' => 'resumen_inventario', 'titulo' => 'Inventario']);
        Cache::flush();
        $this->actingAs($this->admin)->get($r['datos']['_adjunto']['url'])->assertStatus(410);

        $this->assertStringContainsString('no es una consulta', $this->informe($this->admin, ['consulta' => 'generar_informe', 'titulo' => 'x'])['error']);
        $this->assertStringContainsString('objeto JSON', $this->informe($this->admin, ['consulta' => 'ver_equipo', 'parametros' => 'codigo', 'titulo' => 'x'])['error']);
        $this->assertStringContainsString('No encontré', $this->informe($this->admin, ['consulta' => 'ver_equipo', 'parametros' => '{"codigo":"NO-EXISTE"}', 'titulo' => 'x'])['error']);
    }

    /** @test */
    public function el_tabulador_separa_datos_conteos_secciones_y_tablas(): void
    {
        $partes = (new Tabulador)->partes([
            'total'      => 2,
            'por_estado' => ['Asignado' => 3, 'Disponible' => 1],
            'asignado_a' => ['receptor' => 'Ana Díaz', 'desde' => '01/01/2026'],
            'equipos'    => [['codigo' => 'EQ-1', 'url' => 'http://x', 'activo' => true], ['codigo' => 'EQ-2', 'estado' => 'Disponible']],
        ]);

        $this->assertSame(['Total' => '2'], $partes['datos']);
        $this->assertSame(['Asignado' => 3, 'Disponible' => 1], $partes['graficos']['Por estado']);
        $this->assertSame(['Receptor' => 'Ana Díaz', 'Desde' => '01/01/2026'], $partes['secciones']['Asignado a']);
        $this->assertSame(['Código', 'Activo', 'Estado'], $partes['tablas']['Equipos']['columnas']);   // sin la columna url
        $this->assertSame([['EQ-1', 'Sí', ''], ['EQ-2', '', 'Disponible']], $partes['tablas']['Equipos']['filas']);
    }

    /** @test */
    public function el_chat_muestra_el_boton_de_descarga_y_la_ia_no_recibe_el_enlace(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['functionCall' => ['name' => 'generar_informe', 'args' => [
                'consulta' => 'buscar_equipos', 'parametros' => '{"categoria":"Laptop"}', 'formato' => 'excel', 'titulo' => 'Laptops',
            ]]]]]]]])
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Listo, el Excel tiene las 130 laptops.']]]]]]),
        ]);

        Livewire::actingAs($this->analista)->test(ChatAsistente::class)
            ->set('pregunta', 'Pásame las laptops a Excel')->call('enviar')->call('responder')
            ->assertSee('Descargar Excel')
            ->assertSeeHtml('asistente/informes/');

        $this->assertStringNotContainsString('asistente/informes', Http::recorded()[1][0]->body());
    }

    /** @test */
    public function buscar_personas_encuentra_por_partes_del_nombre_sin_datos_personales(): void
    {
        foreach (['Luis Pérez', 'Luis Gómez', 'Ana Luisa Rojas'] as $nombre) {
            User::factory()->create(['name' => $nombre, 'empresa_id' => $this->empresa->id, 'ubicacion_id' => $this->analista->ubicacion_id, 'cedula' => 'V-999']);
        }

        $r = app(Herramientas::class)->ejecutar('buscar_personas', $this->analista, ['nombre' => 'luis']);
        $this->assertSame(3, $r['datos']['total']);

        $r = app(Herramientas::class)->ejecutar('buscar_personas', $this->analista, ['nombre' => 'luis pér']);
        $this->assertSame(['Luis Pérez'], array_column($r['datos']['personas'], 'nombre'));
        $this->assertStringNotContainsString('V-999', json_encode($r));
    }
}
