<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Asistente\HerramientaException;
use App\Asistente\Herramientas;
use App\Asistente\Informes\Tabulador;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Gráfico dentro del chat. Igual que los informes, la IA no aporta números:
 * dice qué consulta graficar y Cerberus calcula etiquetas y valores, ya sea
 * de un conteo que trae la consulta (por_estado, por_categoria…) o
 * agrupando las filas de su tabla por una columna (estado, ubicación,
 * responsable, mes de inicio…).
 */
class MostrarGrafico extends Herramienta
{
    /** Máximo de barras/porciones; el resto se suma en "Otros". */
    const MAX_ETIQUETAS = 15;

    public function nombre(): string
    {
        return 'mostrar_grafico';
    }

    public function descripcion(): string
    {
        return 'Dibuja un gráfico en el chat con el resultado de OTRA consulta. Dos maneras: (1) un conteo que ya trae la '
            . 'consulta, con "dato" (ej. resumen_inventario + dato "por_estado"/"por_categoria"/"por_ubicacion", '
            . 'casos_mantenimiento + "por_estado", renovacion_equipos + "conteo"); o (2) agrupando las filas por una '
            . 'columna con "agrupar_por" (ej. buscar_equipos agrupado por "ubicacion" o "estado"; casos_mantenimiento '
            . 'por "categoria", "responsable" o por "inicio" con por_mes=true para ver la evolución mensual). '
            . 'Cerberus calcula los números; el gráfico aparece solo en el chat.';
    }

    public function parametros(): array
    {
        return [
            'consulta'    => ['type' => 'string', 'description' => 'Herramienta cuyo resultado se grafica (ej. "resumen_inventario").'],
            'parametros'  => ['type' => 'string', 'description' => 'Parámetros de esa consulta como objeto JSON. "{}" si no lleva.'],
            'dato'        => ['type' => 'string', 'description' => 'Conteo de la consulta a graficar (ej. "por_estado").'],
            'agrupar_por' => ['type' => 'string', 'description' => 'O bien: columna de la tabla por la que contar (ej. "ubicacion", "estado", "responsable", "inicio").'],
            'por_mes'     => ['type' => 'boolean', 'description' => 'Con agrupar_por de una fecha: agrupa por mes.'],
            'tipo'        => ['type' => 'string', 'enum' => ['barras', 'dona', 'lineas'], 'description' => 'barras (por defecto), dona (partes de un total, pocas categorías) o lineas (evolución en el tiempo).'],
            'titulo'      => ['type' => 'string', 'description' => 'Título corto del gráfico.'],
        ];
    }

    public function requeridos(): array
    {
        return ['consulta', 'titulo'];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $consulta = $args['consulta'];
        if (in_array($consulta, ['mostrar_grafico', 'generar_informe'], true)) {
            throw new HerramientaException("No se puede graficar «{$consulta}».");
        }
        $parametros = json_decode($args['parametros'] ?? '{}', true);
        if (! is_array($parametros)) {
            throw new HerramientaException('"parametros" debe ser un objeto JSON, ej. {"categoria":"Laptop"}.');
        }

        $resultado = app(Herramientas::class)->ejecutar($consulta, $actor, $parametros, paraInforme: true);
        if (! $resultado['ok']) {
            throw new HerramientaException('No se pudo hacer el gráfico: ' . $resultado['error']);
        }

        $serie = isset($args['agrupar_por'])
            ? $this->agrupar($resultado['datos'], $args['agrupar_por'], (bool) ($args['por_mes'] ?? false))
            : $this->conteo($resultado['datos'], $args['dato'] ?? null);

        if (! $serie || array_sum($serie) == 0) {
            throw new HerramientaException('No hay datos para graficar con esos filtros.');
        }

        $porMes = (bool) ($args['por_mes'] ?? false);
        if (! $porMes) {
            arsort($serie);
        }
        $serie = $this->recortar($serie);
        $tipo = $args['tipo'] ?? ($porMes ? 'lineas' : 'barras');

        return [
            'listo'   => true,
            'total'   => array_sum($serie),
            'valores' => $serie,
            'nota'    => 'El gráfico ya aparece en el chat. Comenta en una o dos líneas lo más relevante; no repitas todos los números.',
            '_grafico' => [
                'titulo' => mb_substr($args['titulo'], 0, 100),
                'tipo'   => $tipo,
                'labels' => array_map('strval', array_keys($serie)),
                'values' => array_values($serie),
            ],
        ];
    }

    /** Un conteo que la consulta ya trae (ej. por_estado). */
    private function conteo(array $datos, ?string $dato): ?array
    {
        $graficos = collect($datos)->filter(fn ($v) => is_array($v) && $v && ! array_is_list($v)
            && collect($v)->every(fn ($n) => is_int($n) || is_float($n)));

        if ($graficos->isEmpty()) {
            throw new HerramientaException('Esa consulta no trae conteos. Usa "agrupar_por" con una de sus columnas.');
        }
        if ($dato === null) {
            return $graficos->first();
        }

        $clave = $graficos->keys()->first(fn ($k) => $this->igual($k, $dato) || $this->igual(Tabulador::etiqueta($k), $dato));
        if ($clave === null) {
            throw new HerramientaException('Esa consulta no trae «' . $dato . '». Trae: ' . $graficos->keys()->implode(', ') . '.');
        }

        return $graficos[$clave];
    }

    /** Cuenta las filas de la tabla de la consulta por una columna. */
    private function agrupar(array $datos, string $columna, bool $porMes): array
    {
        $tabla = collect($datos)->first(fn ($v) => is_array($v) && $v && array_is_list($v) && is_array($v[0] ?? null)
            && collect(array_keys($v[0]))->contains(fn ($k) => $this->igual($k, $columna) || $this->igual(Tabulador::etiqueta($k), $columna)));

        if (! $tabla) {
            $columnas = collect($datos)->first(fn ($v) => is_array($v) && array_is_list($v) && is_array($v[0] ?? null));
            throw new HerramientaException("La consulta no tiene la columna «{$columna}»"
                . ($columnas ? '. Columnas: ' . implode(', ', array_diff(array_keys($columnas[0]), ['url'])) : '') . '.');
        }

        $clave = collect(array_keys($tabla[0]))->first(fn ($k) => $this->igual($k, $columna) || $this->igual(Tabulador::etiqueta($k), $columna));
        $serie = [];
        foreach ($tabla as $fila) {
            $valor = trim((string) ($fila[$clave] ?? ''));
            if ($porMes) {
                try {
                    $valor = Carbon::createFromFormat('d/m/Y', substr($valor, 0, 10))->format('Y-m');
                } catch (\Throwable) {
                    continue;
                }
            }
            $valor = $valor === '' ? 'Sin dato' : $valor;
            $serie[$valor] = ($serie[$valor] ?? 0) + 1;
        }

        if ($porMes) {
            ksort($serie);
            $serie = collect($serie)->mapWithKeys(fn ($n, $mes) => [Carbon::createFromFormat('Y-m', $mes)->locale('es')->isoFormat('MMM YYYY') => $n])->all();
        }

        return $serie;
    }

    private function recortar(array $serie): array
    {
        if (count($serie) <= self::MAX_ETIQUETAS) {
            return $serie;
        }
        $primeros = array_slice($serie, 0, self::MAX_ETIQUETAS - 1, true);

        return $primeros + ['Otros' => array_sum(array_slice($serie, self::MAX_ETIQUETAS - 1))];
    }

    private function igual(string $a, string $b): bool
    {
        $n = fn ($t) => \App\Asistente\Caracteristicas::normalizar($t);

        return $n($a) === $n($b);
    }
}
