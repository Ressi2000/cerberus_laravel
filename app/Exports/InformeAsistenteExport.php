<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Informe de Cerberus Bot en Excel: una hoja "Resumen" (título, datos,
 * conteos) y una hoja por cada tabla del informe.
 */
class InformeAsistenteExport implements WithMultipleSheets
{
    public function __construct(private array $contenido)
    {
    }

    public function sheets(): array
    {
        $c = $this->contenido;
        $p = $c['partes'];

        $resumen = [[$c['titulo']], ['Generado el ' . $c['fecha'] . ' por ' . $c['generadoPor'] . ($c['empresa'] ? " · {$c['empresa']}" : '')], []];
        $negritas = [1];
        if ($c['resumen']) {
            $resumen[] = ['Resumen del asistente (verifique los datos en las tablas)'];
            $negritas[] = count($resumen);
            foreach (preg_split('/\R/', $this->sinMarkdown($c['resumen'])) as $linea) {
                $resumen[] = [$linea];
            }
            $resumen[] = [];
        }
        foreach ($p['datos'] as $k => $v) {
            $resumen[] = [$k, $v];
        }
        foreach (array_merge($p['secciones'], $p['graficos']) as $titulo => $pares) {
            $resumen[] = [];
            $resumen[] = [$titulo];
            $negritas[] = count($resumen);
            foreach ($pares as $k => $v) {
                $resumen[] = $k === '' ? [$v] : [$k, $v];
            }
        }

        $hojas = [new HojaInforme('Resumen', $resumen, $negritas)];
        $usados = ['resumen'];
        foreach ($p['tablas'] as $titulo => $tabla) {
            $nombre = $this->nombreHoja($titulo, $usados);
            $hojas[] = new HojaInforme($nombre, array_merge([$tabla['columnas']], $tabla['filas']), [1], true);
        }

        return $hojas;
    }

    /** Excel exige nombres de hoja de hasta 31 caracteres, sin []:*?/\ y sin repetir. */
    private function nombreHoja(string $titulo, array &$usados): string
    {
        $base = mb_substr(trim(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $titulo)) ?: 'Datos', 0, 28);
        $nombre = $base;
        for ($i = 2; in_array(mb_strtolower($nombre), $usados, true); $i++) {
            $nombre = "{$base} {$i}";
        }
        $usados[] = mb_strtolower($nombre);

        return $nombre;
    }

    private function sinMarkdown(string $texto): string
    {
        return trim(preg_replace(['/\*\*(.*?)\*\*/', '/^#{1,6}\s*/m', '/\[(.*?)\]\((.*?)\)/'], ['$1', '', '$1'], $texto));
    }
}
