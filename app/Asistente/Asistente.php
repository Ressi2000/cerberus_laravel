<?php

namespace App\Asistente;

use App\Asistente\Proveedores\Gemini;
use App\Asistente\Proveedores\ProveedorIA;
use App\Models\AsistenteConsulta;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Cerberus Bot: recibe una pregunta en lenguaje natural, se la pasa a la IA
 * con las herramientas de consulta y deja constancia en la bitácora.
 */
class Asistente
{
    /** Marca que la IA pone cuando no pudo responder con los datos disponibles. */
    const MARCA_SIN_DATOS = '[[SIN_DATOS]]';

    public function __construct(private Herramientas $herramientas)
    {
    }

    /** ¿Se muestra el bot a este usuario? Oculto si no está habilitado (producción). */
    public static function disponiblePara(?User $usuario): bool
    {
        return config('asistente.habilitado') && $usuario && app(Herramientas::class)->puedeUsar($usuario);
    }

    public function proveedor(): ProveedorIA
    {
        return app(match (config('asistente.proveedor')) {
            default => Gemini::class,
        });
    }

    /**
     * Responde una pregunta. $historial es la conversación anterior en el
     * formato del proveedor (la devuelve la respuesta anterior).
     */
    public function preguntar(User $actor, string $pregunta, array $historial = []): Respuesta
    {
        abort_unless(self::disponiblePara($actor), 403);

        $clave = 'cerberus-bot:' . $actor->id;
        if (RateLimiter::tooManyAttempts($clave, config('asistente.preguntas_por_hora', 40))) {
            $min = (int) ceil(RateLimiter::availableIn($clave) / 60);
            throw new AsistenteException("Hiciste muchas preguntas en la última hora. Podrás seguir en {$min} min.");
        }
        RateLimiter::hit($clave, 3600);

        $inicio = microtime(true);
        $registro = fn (array $datos) => AsistenteConsulta::create([
            'user_id'     => $actor->id,
            'empresa_id'  => $actor->empresa_activa_id,
            'pregunta'    => mb_substr($pregunta, 0, 2000),
            'duracion_ms' => (int) ((microtime(true) - $inicio) * 1000),
        ] + $datos);

        // Los nombres de las personas viajan a la IA como "Persona-12".
        $privacidad = Privacidad::desdeConfig();

        try {
            $respuesta = $this->proveedor()->responder(
                $actor,
                $privacidad->ocultar($pregunta),
                $this->recortar($historial),
                $privacidad->ocultar($this->instrucciones($actor)),
                $privacidad,
            );
        } catch (AsistenteException $e) {
            $registro(['error' => mb_substr($e->detalle ?? $e->getMessage(), 0, 500), 'modelo' => config('asistente.gemini.modelo')]);
            throw $e;
        }

        $respuesta = new Respuesta(
            $privacidad->revelar($respuesta->texto),
            $respuesta->herramientas,
            $respuesta->sinRespuesta,
            $respuesta->historial,
            $respuesta->modelo,
        );

        $registro([
            'respuesta'     => $respuesta->texto,
            'herramientas'  => $respuesta->herramientas,
            'sin_respuesta' => $respuesta->sinRespuesta,
            'modelo'        => $respuesta->modelo,
        ]);

        return $respuesta;
    }

    /**
     * Deja solo las últimas preguntas como memoria (para no mandar
     * conversaciones enormes). Corta siempre al inicio de una pregunta del
     * usuario, nunca en medio de una consulta.
     */
    public function recortar(array $historial): array
    {
        $inicios = [];
        foreach ($historial as $i => $c) {
            if (($c['role'] ?? '') === 'user' && isset($c['parts'][0]['text'])) {
                $inicios[] = $i;
            }
        }

        $max = config('asistente.max_turnos_memoria', 10);
        if (count($inicios) <= $max) {
            return $historial;
        }

        return array_values(array_slice($historial, $inicios[count($inicios) - $max]));
    }

    /** Instrucciones de sistema: quién es, qué puede hacer y cómo responder. */
    public function instrucciones(User $actor): string
    {
        $rol = $actor->hasRole('Administrador') ? 'Administrador (ve todas las empresas)' : 'Analista';
        $empresa = $actor->empresa_activa_id ? Empresa::find($actor->empresa_activa_id)?->nombre : null;
        $hoy = now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $marca = self::MARCA_SIN_DATOS;

        return <<<TXT
        Eres Cerberus Bot, el asistente del sistema Cerberus: inventario de equipos de tecnología, asignaciones,
        préstamos, traslados, mantenimientos/reparaciones y almacén de repuestos.

        Datos de la sesión: hoy es {$hoy}. Hablas con {$actor->name}, rol {$rol}{$this->conEmpresa($empresa)}.

        Identidad:
        - Eres Cerberus Bot y nada más. Nunca digas qué modelo, empresa o tecnología de IA te impulsa (no menciones
          Gemini, Google, OpenAI, etc.). Si te lo preguntan, responde que eres el asistente de Cerberus.

        Reglas:
        1. Responde SOLO con datos que obtengas de las herramientas. Nunca inventes códigos, seriales, nombres,
           fechas ni cantidades. Si una herramienta no trae un dato, dilo.
        2. Combina herramientas cuando haga falta (ej.: buscar_equipos para encontrar el código y luego
           historial_equipo). Si el usuario da un código parcial o un nombre, búscalo primero.
        3. Para filtrar por una característica técnica (RAM, procesador, disco, sistema operativo, marca…) usa
           buscar_equipos con "caracteristica" y "valor". Si no sabes cómo se llama exactamente la característica
           en esa categoría o qué valores tiene, consulta antes caracteristicas_equipos.
        4. Si una herramienta responde que hay varias coincidencias, pregúntale al usuario cuál quiere.
        5. Solo consultas: no puedes crear, modificar ni borrar nada. Si te lo piden, explica que eso se hace
           en las pantallas de Cerberus.
        6. Privacidad: las personas aparecen como "Persona-N" (por ejemplo Persona-12). Úsalas EXACTAMENTE así,
           sin cambiarlas ni comentar el código: el sistema las muestra con su nombre real. No tienes datos
           personales (cédula, teléfono, correo, dirección); si los piden, indica que se consultan en la sección
           Usuarios de Cerberus.
        7. Si la pregunta no se puede responder con las herramientas disponibles (o no es sobre Cerberus),
           explícalo en una frase, sugiere qué sí puedes consultar y escribe al final exactamente {$marca}.
        8. Responde en español, claro y breve, en Markdown. Usa listas o tablas para varios elementos.
           Cuando la herramienta trae "url", enlaza el código: [EQ-00057](url).
        9. Para "informes" o "reportes", organiza la respuesta con títulos cortos (###), un resumen arriba y
           el detalle después.
        10. Usa las fechas tal como vienen (dd/mm/aaaa).
        TXT;
    }

    private function conEmpresa(?string $empresa): string
    {
        return $empresa ? ", empresa activa {$empresa}" : '';
    }
}
