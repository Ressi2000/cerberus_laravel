<?php

namespace Tests\Feature\Asistente;

use App\Asistente\Asistente;
use App\Asistente\Herramientas;
use App\Asistente\Privacidad;
use App\Livewire\Asistente\ChatAsistente;
use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EquipoAtributoValor;
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
 * Cerberus Bot: privacidad (seudónimos, datos personales, características
 * ocultas), identidad y filtro preciso por características.
 */
class PrivacidadAsistenteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $maria;
    private Equipo $laptop;
    private CategoriaEquipo $categoria;
    private AtributoEquipo $ram;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);

        $empresa = Empresa::create(['nombre' => 'Empresa A']);
        $ubicacion = Ubicacion::create(['nombre' => 'Sede A', 'empresa_id' => $empresa->id, 'activo' => true]);
        $this->admin = User::factory()->create(['name' => 'Admin Pruebas', 'empresa_id' => $empresa->id, 'empresa_activa_id' => $empresa->id]);
        $this->admin->assignRole('Administrador');

        $this->maria = User::factory()->create([
            'name' => 'María Pérez', 'empresa_id' => $empresa->id, 'ubicacion_id' => $ubicacion->id,
            'email' => 'maria.privado@empresa.test', 'cedula' => 'V-12345678', 'telefono' => '0414-5550000',
        ]);

        $this->categoria = CategoriaEquipo::create(['nombre' => 'Laptop']);
        $puk = AtributoEquipo::create(['categoria_id' => $this->categoria->id, 'nombre' => 'PUK', 'slug' => 'puk', 'tipo' => 'string']);
        $so = AtributoEquipo::create(['categoria_id' => $this->categoria->id, 'nombre' => 'Sistema Operativo', 'slug' => 'so', 'tipo' => 'string']);
        $this->ram = AtributoEquipo::create(['categoria_id' => $this->categoria->id, 'nombre' => 'RAM', 'slug' => 'ram', 'tipo' => 'group',
            'sub_campos' => [['id' => 'cap', 'nombre' => 'Capacidad (GB)', 'tipo' => 'integer'], ['id' => 'tipo', 'nombre' => 'Tipo', 'tipo' => 'string']]]);

        $this->laptop = $this->equipo('EQ-001', $empresa, $ubicacion, 'Windows 10 Pro', [['4', 'DDR4'], ['4', 'DDR4']]);
        $this->equipo('EQ-002', $empresa, $ubicacion, 'Windows 11 Pro', [['16', 'DDR5']]);
        EquipoAtributoValor::create(['equipo_id' => $this->laptop->id, 'atributo_id' => $puk->id, 'valor' => '99887766', 'es_actual' => true]);

        $asignacion = Asignacion::forceCreate([
            'empresa_id' => $empresa->id, 'usuario_id' => $this->maria->id, 'analista_id' => $this->admin->id,
            'fecha_asignacion' => now()->toDateString(), 'estado' => 'Activa',
        ]);
        AsignacionItem::forceCreate(['asignacion_id' => $asignacion->id, 'equipo_id' => $this->laptop->id, 'devuelto' => false]);

        config(['asistente.habilitado' => true, 'asistente.gemini.api_key' => 'clave', 'asistente.gemini.modelo' => 'gemini-prueba']);
    }

    private function equipo(string $codigo, Empresa $empresa, Ubicacion $ubicacion, string $so, array $ram): Equipo
    {
        $e = Equipo::forceCreate([
            'empresa_id' => $empresa->id, 'categoria_id' => $this->categoria->id, 'ubicacion_id' => $ubicacion->id,
            'estado_id' => EstadoEquipo::where('nombre', 'Disponible')->value('id'), 'codigo_interno' => $codigo, 'activo' => true,
        ]);
        EquipoAtributoValor::create(['equipo_id' => $e->id, 'atributo_id' => AtributoEquipo::where('nombre', 'Sistema Operativo')->value('id'), 'valor' => $so, 'es_actual' => true]);
        foreach ($ram as $i => [$gb, $tipo]) {
            EquipoAtributoGrupoInstancia::create(['equipo_id' => $e->id, 'atributo_id' => $this->ram->id, 'valores' => ['cap' => $gb, 'tipo' => $tipo], 'orden' => $i, 'es_actual' => true]);
        }

        return $e;
    }

    private function usar(string $h, array $args = []): array
    {
        return app(Herramientas::class)->ejecutar($h, $this->admin, $args);
    }

    /** @test */
    public function ninguna_herramienta_devuelve_datos_personales_ni_caracteristicas_ocultas(): void
    {
        $salidas = json_encode([
            $this->usar('ver_equipo', ['codigo' => 'EQ-001']),
            $this->usar('buscar_equipos', ['texto' => 'EQ']),
            $this->usar('equipos_de_persona', ['persona' => 'María Pérez']),
            $this->usar('historial_equipo', ['codigo' => 'EQ-001']),
            $this->usar('caracteristicas_equipos', ['categoria' => 'Laptop']),
            $this->usar('resumen_inventario'),
        ], JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('María Pérez', $salidas);   // el nombre sí (luego se seudonimiza)
        foreach (['maria.privado@empresa.test', 'V-12345678', '0414-5550000', '99887766', 'PUK'] as $sensible) {
            $this->assertStringNotContainsString($sensible, $salidas, "Se filtró «{$sensible}»");
        }
        // Ni buscando el valor oculto a propósito.
        $this->assertSame(0, $this->usar('buscar_equipos', ['texto' => '99887766'])['datos']['total']);
    }

    /** @test */
    public function los_nombres_viajan_como_codigos_y_vuelven_con_el_nombre_real(): void
    {
        $p = new Privacidad(true);
        $id = $this->maria->id;

        $this->assertSame("Equipos de Persona-{$id}", $p->ocultar('Equipos de maria   PEREZ'));
        $this->assertSame(['asignado_a' => "Persona-{$id}"], $p->ocultar(['asignado_a' => 'María Pérez']));
        $this->assertSame('La tiene María Pérez.', $p->revelar("La tiene Persona-{$id}."));
        $this->assertSame('Tiene 3 personas a cargo', $p->ocultar('Tiene 3 personas a cargo'));
    }

    /** @test */
    public function gemini_nunca_recibe_el_nombre_real_y_el_usuario_lo_ve_completo(): void
    {
        $id = $this->maria->id;
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['functionCall' => ['name' => 'equipos_de_persona', 'args' => ['persona' => "Persona-{$id}"]]]]]]]])
            ->push(['candidates' => [['content' => ['parts' => [['text' => "Persona-{$id} tiene el EQ-001."]]]]]]),
        ]);

        $r = app(Asistente::class)->preguntar($this->admin, '¿Qué equipos tiene María Pérez?');

        $this->assertSame('María Pérez tiene el EQ-001.', $r->texto);
        $this->assertSame('María Pérez', $r->herramientas[0]['args']['persona']);   // la consulta se hizo con el nombre real

        foreach (Http::recorded() as [$request]) {
            $cuerpo = $request->body();
            $this->assertStringNotContainsString('María', $cuerpo);
            $this->assertStringNotContainsString('María', $cuerpo);
            $this->assertStringNotContainsString('Admin Pruebas', $cuerpo);   // tampoco el de quien pregunta
        }
        // El resultado de la consulta llegó a la IA con el código.
        $this->assertStringContainsString("Persona-{$id}", Http::recorded()[1][0]->body());
    }

    /** @test */
    public function el_bot_no_se_presenta_como_gemini_y_el_detalle_tecnico_es_solo_para_el_admin(): void
    {
        $instrucciones = app(Asistente::class)->instrucciones($this->admin);
        $this->assertStringContainsString('Nunca digas qué modelo, empresa o tecnología de IA', $instrucciones);

        config(['asistente.gemini.api_key' => null]);
        $analista = User::factory()->create(['empresa_activa_id' => $this->admin->empresa_activa_id]);
        $analista->assignRole('Analista');

        Livewire::actingAs($analista)->test(ChatAsistente::class)
            ->set('pregunta', 'Hola')->call('enviar')->call('responder')
            ->assertSee('todavía no está configurado')
            ->assertDontSee('GEMINI_API_KEY')
            ->assertDontSee('Gemini');

        Livewire::actingAs($this->admin)->test(ChatAsistente::class)
            ->set('pregunta', 'Hola')->call('enviar')->call('responder')
            ->assertSee('GEMINI_API_KEY');
    }

    /** @test */
    public function filtra_por_caracteristica_con_total_de_varias_entradas(): void
    {
        $r = $this->usar('buscar_equipos', ['caracteristica' => 'ram', 'valor' => '8', 'comparacion' => 'mayor_o_igual']);
        $this->assertSame(['EQ-001', 'EQ-002'], array_column($r['datos']['equipos'], 'codigo'));
        $this->assertSame('4 + 4 (total 8)', $r['datos']['equipos'][0]['valor']);

        $r = $this->usar('buscar_equipos', ['caracteristica' => 'RAM', 'valor' => '8', 'comparacion' => 'menor_o_igual']);
        $this->assertSame(['EQ-001'], array_column($r['datos']['equipos'], 'codigo'));

        $r = $this->usar('buscar_equipos', ['caracteristica' => 'Tipo', 'valor' => 'ddr5']);
        $this->assertSame(['EQ-002'], array_column($r['datos']['equipos'], 'codigo'));

        $r = $this->usar('buscar_equipos', ['caracteristica' => 'sistema operativo', 'valor' => '11', 'comparacion' => 'no_contiene']);
        $this->assertSame(['EQ-001'], array_column($r['datos']['equipos'], 'codigo'));

        $this->assertStringContainsString('caracteristicas_equipos', $this->usar('buscar_equipos', ['caracteristica' => 'Pantalla', 'valor' => 'x'])['error']);
        $this->assertFalse($this->usar('buscar_equipos', ['caracteristica' => 'PUK', 'valor' => '9'])['ok']);
    }

    /** @test */
    public function lista_las_caracteristicas_con_valores_que_se_repiten(): void
    {
        $r = $this->usar('caracteristicas_equipos', ['categoria' => 'Laptop'])['datos']['categorias'][0];
        $nombres = array_column($r['caracteristicas'], 'nombre');

        $this->assertNotContains('PUK', $nombres);
        $ram = collect($r['caracteristicas'])->firstWhere('nombre', 'RAM');
        $this->assertSame(['Capacidad (GB)', 'Tipo'], array_column($ram['campos'], 'nombre'));
        $this->assertSame(['DDR4', 'DDR5'], collect($ram['campos'])->firstWhere('nombre', 'Tipo')['valores']);
    }
}
