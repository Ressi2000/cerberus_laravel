<?php

namespace App\Asistente\Informes;

/**
 * Convierte el resultado de CUALQUIER herramienta del asistente en las
 * partes de un informe, sin que haya que programar un informe por consulta:
 *
 *  - datos:     valores sueltos ("Total: 12", "Equipo: EQ-00057").
 *  - secciones: grupos de pares campo → valor ("Asignado a", "Características").
 *  - graficos:  conteos (campo → número), que el PDF dibuja como barras.
 *  - tablas:    listas de elementos (equipos, casos, eventos…), una por lista.
 *
 * Todo sale de los datos que devolvió Cerberus; la IA no aporta números.
 */
class Tabulador
{
    /** Nombres más claros para las claves que devuelven las herramientas. */
    const ETIQUETAS = [
        'codigo' => 'Código', 'categoria' => 'Categoría', 'ubicacion' => 'Ubicación', 'asignado_a' => 'Asignado a',
        'prestado_a' => 'Prestado a', 'fecha_adquisicion' => 'Fecha de adquisición', 'garantia_hasta' => 'Garantía hasta',
        'en_deposito' => 'En depósito', 'caso_abierto' => 'Caso abierto', 'piezas_instaladas_del_almacen' => 'Piezas del almacén instaladas',
        'renovacion' => 'Renovación', 'caracteristicas' => 'Características', 'caracteristica' => 'Característica',
        'numero' => 'N.º', 'devolucion_esperada' => 'Devolución esperada', 'que_paso' => 'Movimiento', 'registro' => 'Registró',
        'donde' => 'Dónde está', 'donde_esta' => 'Dónde está', 'condicion' => 'Condición', 'salio_de' => 'Salió de',
        'por_estado' => 'Por estado', 'por_categoria' => 'Por categoría', 'por_ubicacion' => 'Por ubicación',
        'casos_abiertos' => 'Casos abiertos', 'prestamos_vencidos' => 'Préstamos vencidos', 'equipos_activos' => 'Equipos activos',
        'sin_asignar' => 'Sin asignar', 'variantes_con_stock' => 'Variantes con stock', 'unidades_total' => 'Unidades en total',
        'a_granel' => 'A granel', 'esperando_repuesto' => 'Esperando repuesto', 'clasificacion' => 'Clasificación',
        'evaluados' => 'Equipos evaluados', 'conteo' => 'Conteo', 'mostrados' => 'Mostrados', 'mostradas' => 'Mostradas',
        'revisados' => 'Revisados', 'eventos' => 'Historial', 'recorrido' => 'Recorrido', 'detalle' => 'Detalle',
        'asignados' => 'Equipos asignados', 'prestados' => 'Equipos prestados', 'falla' => 'Falla reportada',
    ];

    /** Claves que no tienen sentido en un archivo (enlaces internos). */
    const OMITIR = ['url'];

    public static function etiqueta(string $clave): string
    {
        return self::ETIQUETAS[$clave] ?? ucfirst(str_replace('_', ' ', $clave));
    }

    public function partes(array $datos): array
    {
        $partes = ['datos' => [], 'secciones' => [], 'graficos' => [], 'tablas' => []];

        foreach ($datos as $clave => $valor) {
            if (in_array($clave, self::OMITIR, true) || $valor === null || $valor === [] || $valor === '') {
                continue;
            }
            $titulo = self::etiqueta((string) $clave);

            if (! is_array($valor)) {
                $partes['datos'][$titulo] = $this->texto($valor);
            } elseif (array_is_list($valor) && $this->esListaDeFilas($valor)) {
                $partes['tablas'][$titulo] = $this->tabla($valor);
            } elseif (array_is_list($valor)) {
                $partes['secciones'][$titulo] = ['' => implode("\n", array_map(fn ($v) => $this->texto($v), $valor))];
            } elseif ($this->todoNumerico($valor)) {
                $partes['graficos'][$titulo] = collect($valor)
                    ->mapWithKeys(fn ($v, $k) => [mb_strtoupper(mb_substr((string) $k, 0, 1)) . mb_substr((string) $k, 1) => $v + 0])->all();
            } else {
                $partes['secciones'][$titulo] = collect($valor)
                    ->reject(fn ($v, $k) => in_array($k, self::OMITIR, true) || $v === null || $v === [])
                    ->mapWithKeys(fn ($v, $k) => [self::etiqueta((string) $k) => $this->texto($v)])
                    ->all();
            }
        }

        return $partes;
    }

    /** Filas con las columnas en el orden en que aparecen (sin enlaces). */
    private function tabla(array $filas): array
    {
        $columnas = [];
        foreach ($filas as $fila) {
            foreach (array_keys($fila) as $k) {
                if (! in_array($k, self::OMITIR, true) && ! in_array($k, $columnas, true)) {
                    $columnas[] = $k;
                }
            }
        }

        return [
            'columnas' => array_map(fn ($c) => self::etiqueta((string) $c), $columnas),
            'filas'    => array_map(fn ($fila) => array_map(fn ($c) => $this->texto($fila[$c] ?? ''), $columnas), $filas),
        ];
    }

    private function esListaDeFilas(array $lista): bool
    {
        return $lista !== [] && collect($lista)->every(fn ($v) => is_array($v) && ! array_is_list($v));
    }

    private function todoNumerico(array $valor): bool
    {
        return collect($valor)->every(fn ($v) => is_int($v) || is_float($v));
    }

    /** Cualquier valor como texto legible (listas y objetos en una línea). */
    public function texto(mixed $v): string
    {
        return match (true) {
            is_bool($v)  => $v ? 'Sí' : 'No',
            is_null($v)  => '',
            is_array($v) => array_is_list($v)
                ? implode('; ', array_map(fn ($x) => $this->texto($x), $v))
                : collect($v)->reject(fn ($x, $k) => in_array($k, self::OMITIR, true))
                    ->map(fn ($x, $k) => self::etiqueta((string) $k) . ': ' . $this->texto($x))->implode(' · '),
            default      => (string) $v,
        };
    }
}
