<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Asistente\HerramientaException;
use App\Asistente\Informes\Informes;
use App\Models\User;

class GenerarInforme extends Herramienta
{
    public function nombre(): string
    {
        return 'generar_informe';
    }

    public function descripcion(): string
    {
        return 'Genera un informe descargable en PDF o Excel con el resultado de OTRA consulta (ej. buscar_equipos, '
            . 'historial_equipo, casos_mantenimiento, recorrido_pieza, stock_almacen, renovacion_equipos…). Cerberus vuelve a '
            . 'ejecutar esa consulta con TODOS sus resultados (sin el límite del chat) y arma el archivo con su diseño. '
            . 'Úsala cuando pidan un informe, reporte, PDF, Excel o "pásalo a…". El botón de descarga aparece solo en el chat: '
            . 'no escribas enlaces.';
    }

    public function parametros(): array
    {
        return [
            'consulta'   => ['type' => 'string', 'description' => 'Nombre de la herramienta cuyo resultado va en el informe (ej. "historial_equipo").'],
            'parametros' => ['type' => 'string', 'description' => 'Parámetros de esa consulta como objeto JSON, ej. {"codigo":"EQ-00057"} o {"categoria":"Laptop","caracteristica":"RAM","valor":"8","comparacion":"menor_o_igual"}. "{}" si no lleva.'],
            'formato'    => ['type' => 'string', 'enum' => ['pdf', 'excel'], 'description' => 'pdf o excel. Si no lo dicen, pdf.'],
            'titulo'     => ['type' => 'string', 'description' => 'Título del informe, ej. "Historial del equipo EQ-00057".'],
            'resumen'    => ['type' => 'string', 'description' => 'Opcional: resumen ejecutivo breve (3 a 6 líneas, Markdown) basado SOLO en los datos consultados.'],
        ];
    }

    public function requeridos(): array
    {
        return ['consulta', 'titulo'];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $parametros = json_decode($args['parametros'] ?? '{}', true);
        if (! is_array($parametros)) {
            throw new HerramientaException('"parametros" debe ser un objeto JSON, ej. {"codigo":"EQ-00057"}.');
        }

        $informe = app(Informes::class)->preparar(
            $actor, $args['consulta'], $parametros, $args['formato'] ?? 'pdf', $args['titulo'], $args['resumen'] ?? null,
        );

        return [
            'listo'   => true,
            'formato' => $informe['formato'],
            'filas'   => $informe['filas'],
            'nota'    => 'El usuario ya ve el botón para descargarlo. Confírmale brevemente qué contiene.',
            // Solo para la pantalla (el proveedor no se lo pasa a la IA).
            '_adjunto' => ['tipo' => $informe['formato'], 'archivo' => $informe['archivo'], 'url' => $informe['url']],
        ];
    }
}
