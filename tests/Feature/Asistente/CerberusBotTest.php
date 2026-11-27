<?php

namespace Tests\Feature\Asistente;

use App\Asistente\Asistente;
use App\Asistente\AsistenteException;
use App\Asistente\Proveedores\Gemini;
use App\Livewire\Asistente\ChatAsistente;
use App\Models\AsistenteConsulta;
use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Models\User;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cerberus Bot con Gemini (simulado con Http::fake: no sale a internet).
 */
class CerberusBotTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);

        $empresa = Empresa::create(['nombre' => 'Empresa A']);
        $ubicacion = Ubicacion::create(['nombre' => 'Sede A', 'empresa_id' => $empresa->id, 'activo' => true]);
        $this->admin = User::factory()->create(['empresa_id' => $empresa->id, 'empresa_activa_id' => $empresa->id]);
        $this->admin->assignRole('Administrador');

        Equipo::forceCreate([
            'empresa_id' => $empresa->id, 'categoria_id' => CategoriaEquipo::create(['nombre' => 'Laptop'])->id,
            'ubicacion_id' => $ubicacion->id, 'estado_id' => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
            'codigo_interno' => 'EQ-00057', 'activo' => true,
        ]);

        config([
            'asistente.habilitado'      => true,
            'asistente.gemini.api_key'  => 'clave-de-prueba',
            'asistente.gemini.modelo'   => 'gemini-prueba',
            'asistente.gemini.modelo_respaldo' => 'gemini-respaldo',
            'asistente.gemini.espera_ms' => 0,
        ]);
    }

    private static function texto(string $t): array
    {
        return ['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => $t]]]]]];
    }

    private static function llamada(string $nombre, array $args): array
    {
        return ['candidates' => [['content' => ['role' => 'model', 'parts' => [
            ['functionCall' => ['name' => $nombre, 'args' => $args], 'thoughtSignature' => 'firma-123'],
        ]]]]];
    }

    /** @test */
    public function gemini_pide_una_consulta_cerberus_la_ejecuta_y_devuelve_la_respuesta(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(self::llamada('ver_equipo', ['codigo' => 'EQ-00057']))
            ->push(self::texto('El equipo [EQ-00057](http://x) está **Disponible** en Sede A.')),
        ]);

        $r = app(Asistente::class)->preguntar($this->admin, '¿Dónde está el EQ-00057?');

        $this->assertStringContainsString('Disponible', $r->texto);
        $this->assertSame([['nombre' => 'ver_equipo', 'args' => ['codigo' => 'EQ-00057'], 'ok' => true]], $r->herramientas);
        $this->assertFalse($r->sinRespuesta);

        // Segunda petición: lleva el resultado real de la consulta y la firma de Gemini intacta.
        $segunda = Http::recorded()[1][0];
        $contenidos = $segunda->data()['contents'];
        $this->assertSame('firma-123', $contenidos[1]['parts'][0]['thoughtSignature']);
        $respuestaHerramienta = $contenidos[2]['parts'][0]['functionResponse'];
        $this->assertSame('ver_equipo', $respuestaHerramienta['name']);
        $this->assertSame('Sede A', $respuestaHerramienta['response']['datos']['ubicacion']);
        $this->assertSame('clave-de-prueba', $segunda->header('x-goog-api-key')[0]);

        $log = AsistenteConsulta::sole();
        $this->assertSame('¿Dónde está el EQ-00057?', $log->pregunta);
        $this->assertSame('gemini-prueba', $log->modelo);
        $this->assertFalse($log->sin_respuesta);
    }

    /** @test */
    public function las_preguntas_fuera_de_alcance_quedan_marcadas_en_la_bitacora(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            self::texto('No tengo datos de nómina; puedo consultar equipos y casos. [[SIN_DATOS]]')
        )]);

        $r = app(Asistente::class)->preguntar($this->admin, '¿Cuánto gana Pedro?');

        $this->assertTrue($r->sinRespuesta);
        $this->assertStringNotContainsString('SIN_DATOS', $r->texto);
        $this->assertTrue(AsistenteConsulta::sole()->sin_respuesta);
    }

    /** @test */
    public function las_declaraciones_usan_el_formato_de_gemini(): void
    {
        $decl = collect(app(Gemini::class)->declaraciones())->keyBy('name');

        $this->assertSame('OBJECT', $decl['ver_equipo']['parameters']['type']);
        $this->assertSame('STRING', $decl['ver_equipo']['parameters']['properties']['codigo']['type']);
        $this->assertSame(['codigo'], $decl['ver_equipo']['parameters']['required']);
        $this->assertArrayNotHasKey('required', $decl['buscar_equipos']['parameters']);
        $this->assertSame('INTEGER', $decl['buscar_equipos']['parameters']['properties']['limite']['type']);
    }

    /** @test */
    public function un_error_de_gemini_se_explica_y_queda_registrado(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid']], 400)]);

        try {
            app(Asistente::class)->preguntar($this->admin, 'Hola');
            $this->fail('Debió fallar');
        } catch (AsistenteException $e) {
            // Al usuario: mensaje neutro; el detalle técnico queda para el administrador y la bitácora.
            $this->assertStringNotContainsString('Gemini', $e->getMessage());
            $this->assertStringContainsString('clave de Gemini no es válida', $e->detalle);
        }

        $this->assertStringContainsString('clave de Gemini', AsistenteConsulta::sole()->error);
    }

    /** @test */
    public function si_gemini_esta_saturado_reintenta_y_luego_usa_el_modelo_de_respaldo(): void
    {
        Http::fake([
            '*/models/gemini-prueba:*'    => Http::response(['error' => ['message' => 'The model is overloaded']], 503),
            '*/models/gemini-respaldo:*'  => Http::response(self::texto('Hola desde el respaldo.')),
        ]);

        $r = app(Asistente::class)->preguntar($this->admin, 'Hola');

        $this->assertSame('Hola desde el respaldo.', $r->texto);
        $this->assertSame('gemini-respaldo', $r->modelo);
        $this->assertCount(2, Http::recorded()); // sin esperar al saturado: 1 intento al principal y pasa al respaldo
        $this->assertSame('gemini-respaldo', AsistenteConsulta::sole()->modelo);
    }

    /** @test */
    public function recuerda_el_modelo_saturado_y_empieza_por_el_respaldo(): void
    {
        Http::fake([
            '*/models/gemini-prueba:*'   => Http::response(['error' => ['message' => 'overloaded']], 503),
            '*/models/gemini-respaldo:*' => Http::response(self::texto('Hola.')),
        ]);

        app(Asistente::class)->preguntar($this->admin, 'Hola');
        app(Asistente::class)->preguntar($this->admin, 'Hola otra vez');

        // 2 llamadas la primera vez; la segunda va directo al respaldo.
        $this->assertCount(3, Http::recorded());
        $this->assertStringContainsString('gemini-respaldo', Http::recorded()[2][0]->url());
    }

    /** @test */
    public function pide_razonamiento_bajo_y_si_el_modelo_no_lo_acepta_reintenta_sin_el(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'thinking_level is not supported for this model']], 400)
            ->push(self::texto('Hola.')),
        ]);

        $r = app(Asistente::class)->preguntar($this->admin, 'Hola');

        $this->assertSame('Hola.', $r->texto);
        $this->assertSame('low', Http::recorded()[0][0]->data()['generationConfig']['thinkingConfig']['thinkingLevel']);
        $this->assertArrayNotHasKey('generationConfig', Http::recorded()[1][0]->data());
    }

    /** @test */
    public function si_gemini_no_responde_a_tiempo_lo_dice_sin_romper_la_pagina(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out after 30001 milliseconds'));

        $this->expectExceptionMessage('tardó demasiado');
        app(Asistente::class)->preguntar($this->admin, 'Hola');
    }

    /** @test */
    public function si_los_dos_modelos_estan_saturados_lo_explica(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'overloaded']], 503)]);

        $this->expectExceptionMessage('muy ocupado');
        app(Asistente::class)->preguntar($this->admin, 'Hola');
    }

    /** @test */
    public function un_fallo_de_conexion_explica_que_revisar_en_el_servidor(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 60: SSL certificate problem: unable to get local issuer certificate'));

        try {
            app(Asistente::class)->preguntar($this->admin, 'Hola');
            $this->fail('Debió fallar');
        } catch (AsistenteException $e) {
            $this->assertStringContainsString('No pude conectarme', $e->getMessage());
            $this->assertStringContainsString('curl.cainfo', $e->detalle);
            $this->assertStringContainsString('cURL error 60', $e->detalle);
        }

        $this->assertStringContainsString('cacert.pem', AsistenteConsulta::sole()->error);
    }

    /** @test */
    public function una_consulta_sin_parametros_se_reenvia_como_objeto_vacio(): void
    {
        // Gemini manda "args": {} y PHP lo lee como []: al reenviarlo debe volver a ser {}.
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push('{"candidates":[{"content":{"role":"model","parts":[{"functionCall":{"name":"resumen_inventario","args":{}}}]}}]}')
            ->push(self::texto('Hay 1 equipo.')),
        ]);

        $r = app(Asistente::class)->preguntar($this->admin, '¿Cuántos equipos hay?');

        $this->assertSame('Hay 1 equipo.', $r->texto);
        $cuerpo = Http::recorded()[1][0]->body();
        $this->assertStringContainsString('"args":{}', $cuerpo);
        $this->assertStringNotContainsString('"args":[]', $cuerpo);

        // Y queda registrado cuánto tardó cada paso.
        $tiempos = AsistenteConsulta::sole()->tiempos;
        $this->assertSame(['IA gemini-prueba', 'consulta resumen_inventario', 'IA gemini-prueba'], array_column($tiempos, 'que'));
    }

    /** @test */
    public function la_memoria_se_recorta_al_inicio_de_una_pregunta(): void
    {
        config(['asistente.max_turnos_memoria' => 2]);
        $historial = [];
        foreach (range(1, 4) as $i) {
            $historial[] = ['role' => 'user', 'parts' => [['text' => "pregunta {$i}"]]];
            $historial[] = ['role' => 'model', 'parts' => [['functionCall' => ['name' => 'x', 'args' => []]]]];
            $historial[] = ['role' => 'user', 'parts' => [['functionResponse' => ['name' => 'x', 'response' => []]]]];
            $historial[] = ['role' => 'model', 'parts' => [['text' => "respuesta {$i}"]]];
        }

        $recortado = app(Asistente::class)->recortar($historial);

        $this->assertCount(8, $recortado);
        $this->assertSame('pregunta 3', $recortado[0]['parts'][0]['text']);
    }

    /** @test */
    public function el_chat_muestra_la_respuesta_y_guarda_la_conversacion_en_la_sesion(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(self::texto('Hay **1** equipo.'))
            ->push(self::texto('Sigue siendo 1.')),
        ]);

        $chat = Livewire::actingAs($this->admin)->test(ChatAsistente::class)
            ->set('pregunta', '¿Cuántos equipos hay?')
            ->call('enviar')
            ->assertSee('¿Cuántos equipos hay?')
            ->call('responder')
            ->assertSeeHtml('<strong>1</strong>');

        $chat->set('pregunta', '¿Y ahora?')->call('enviar')->call('responder')->assertSee('Sigue siendo 1.');

        // La segunda pregunta viaja con la conversación anterior como memoria.
        $this->assertCount(3, Http::recorded()[1][0]->data()['contents']);

        $chat->call('nuevaConversacion')->assertDontSee('Sigue siendo 1.');
    }

    /** @test */
    public function el_bot_no_aparece_si_no_esta_habilitado_ni_para_usuarios_finales(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertSee('Cerberus Bot');

        config(['asistente.habilitado' => false]);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertDontSee('Cerberus Bot');
        $this->actingAs($this->admin)->get(route('admin.asistente.consultas'))->assertNotFound();

        config(['asistente.habilitado' => true]);
        $usuario = User::factory()->create(['empresa_id' => $this->admin->empresa_id]);
        $usuario->assignRole('Usuario');
        $this->assertFalse(Asistente::disponiblePara($usuario));
    }

    /** @test */
    public function el_administrador_ve_la_bitacora(): void
    {
        AsistenteConsulta::create(['user_id' => $this->admin->id, 'pregunta' => '¿Cuánto gana Pedro?', 'respuesta' => 'No sé', 'sin_respuesta' => true]);

        $this->actingAs($this->admin)->get(route('admin.asistente.consultas'))
            ->assertOk()->assertSee('¿Cuánto gana Pedro?');
    }
}
