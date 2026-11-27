<?php

namespace Tests\Feature\Asistente;

use App\Asistente\Herramientas;
use App\Livewire\Asistente\ChatAsistente;
use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Mantenimiento;
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
 * Gráficos de Cerberus Bot: los números los calcula Cerberus.
 */
class GraficosAsistenteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);

        $empresa = Empresa::create(['nombre' => 'Empresa A']);
        $sedeA = Ubicacion::create(['nombre' => 'Sede A', 'empresa_id' => $empresa->id, 'activo' => true]);
        $sedeB = Ubicacion::create(['nombre' => 'Sede B', 'empresa_id' => $empresa->id, 'activo' => true]);
        $this->admin = User::factory()->create(['empresa_id' => $empresa->id, 'empresa_activa_id' => $empresa->id]);
        $this->admin->assignRole('Administrador');

        $laptop = CategoriaEquipo::create(['nombre' => 'Laptop']);
        $adaptador = CategoriaEquipo::create(['nombre' => 'Adaptador Laptop']);
        $disponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $n = 0;
        foreach ([[$laptop, $sedeA, 3], [$laptop, $sedeB, 1], [$adaptador, $sedeA, 2]] as [$cat, $sede, $cuantos]) {
            foreach (range(1, $cuantos) as $_) {
                $e = Equipo::forceCreate([
                    'empresa_id' => $empresa->id, 'categoria_id' => $cat->id, 'ubicacion_id' => $sede->id,
                    'estado_id' => $disponible, 'codigo_interno' => sprintf('EQ-%03d', ++$n), 'activo' => true,
                ]);
            }
        }
        foreach (['2026-09-10', '2026-10-05', '2026-10-20'] as $fecha) {
            Mantenimiento::create(['empresa_id' => $empresa->id, 'equipo_id' => $e->id, 'tipo' => 'Correctivo', 'estado' => 'Cerrado',
                'reportado_por_id' => $this->admin->id, 'fecha_inicio' => $fecha]);
        }

        config(['asistente.habilitado' => true, 'asistente.gemini.api_key' => 'clave', 'asistente.gemini.modelo' => 'gemini-prueba']);
    }

    private function grafico(array $args): array
    {
        return app(Herramientas::class)->ejecutar('mostrar_grafico', $this->admin, $args + ['titulo' => 'Gráfico']);
    }

    /** @test */
    public function grafica_un_conteo_que_trae_la_consulta(): void
    {
        $r = $this->grafico(['consulta' => 'resumen_inventario', 'dato' => 'por categoría']);

        $this->assertSame(['Laptop' => 4, 'Adaptador Laptop' => 2], $r['datos']['valores']);
        $this->assertSame(['Laptop', 'Adaptador Laptop'], $r['datos']['_grafico']['labels']);
        $this->assertSame('barras', $r['datos']['_grafico']['tipo']);
    }

    /** @test */
    public function agrupa_las_filas_por_una_columna_y_por_mes(): void
    {
        // "Laptop" es solo Laptop (no "Adaptador Laptop").
        $r = $this->grafico(['consulta' => 'buscar_equipos', 'parametros' => '{"categoria":"Laptop"}', 'agrupar_por' => 'ubicación']);
        $this->assertSame(['Sede A' => 3, 'Sede B' => 1], $r['datos']['valores']);

        $r = $this->grafico(['consulta' => 'casos_mantenimiento', 'parametros' => '{"situacion":"todos"}', 'agrupar_por' => 'inicio', 'por_mes' => true]);
        $this->assertSame([1, 2], $r['datos']['_grafico']['values']);
        $this->assertSame('lineas', $r['datos']['_grafico']['tipo']);
        $this->assertStringContainsString('2026', $r['datos']['_grafico']['labels'][0]);
    }

    /** @test */
    public function explica_cuando_no_se_puede_graficar(): void
    {
        $this->assertStringContainsString('Columnas: codigo', $this->grafico(['consulta' => 'buscar_equipos', 'agrupar_por' => 'color'])['error']);
        $this->assertStringContainsString('Trae: por_categoria', $this->grafico(['consulta' => 'resumen_inventario', 'dato' => 'por_color'])['error']);
        $this->assertStringContainsString('No se puede graficar', $this->grafico(['consulta' => 'generar_informe'])['error']);
    }

    /** @test */
    public function el_chat_dibuja_el_grafico_y_la_ia_no_recibe_los_datos_de_pantalla(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['functionCall' => ['name' => 'mostrar_grafico', 'args' => [
                'consulta' => 'resumen_inventario', 'dato' => 'por_estado', 'titulo' => 'Equipos por estado',
            ]]]]]]]])
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Todos están disponibles.']]]]]]),
        ]);

        Livewire::actingAs($this->admin)->test(ChatAsistente::class)
            ->set('pregunta', 'Gráfico por estado')->call('enviar')->call('responder')
            ->assertSee('Equipos por estado')
            ->assertSeeHtml('graficoBot(')
            ->assertSeeHtml('descargar()')
            ->assertSeeHtml('abrirAmpliado()');

        $this->assertStringNotContainsString('"_grafico"', Http::recorded()[1][0]->body());
        $this->assertStringContainsString('"valores"', Http::recorded()[1][0]->body());
    }
}
