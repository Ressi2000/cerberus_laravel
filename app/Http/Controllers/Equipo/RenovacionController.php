<?php

namespace App\Http\Controllers\Equipo;

use App\Exports\CollectionExport;
use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Services\RenovacionService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/** Renovación de equipos: la página y su exportación a Excel. */
class RenovacionController extends Controller
{
    public function index()
    {
        return view('equipos.renovacion');
    }

    /** Excel con la clasificación de cada equipo y el porqué (mismos filtros que la página). */
    public function exportar(Request $request, RenovacionService $renovacion)
    {
        $actor = $request->user();
        $empresa = $actor->hasRole('Administrador') ? $request->input('empresa') : $actor->empresa_activa_id;

        $equipos = Equipo::visiblePara($actor)
            ->where('activo', true)
            ->when($empresa, fn ($q) => $q->where('empresa_id', $empresa))
            ->when($request->input('categoria'), fn ($q, $c) => $q->where('categoria_id', $c))
            ->orderBy('codigo_interno')
            ->get();

        $filas = $renovacion->evaluarEquipos($equipos)
            ->filter(fn ($r) => $r['criterio']->evaluar)
            ->when($request->input('clasificacion'), fn ($c, $cl) => $c->filter(fn ($r) => $r['resultado']['clasificacion'] === $cl))
            ->when($request->boolean('incompletos'), fn ($c) => $c->filter(fn ($r) => $r['resultado']['faltan']))
            ->map(function ($r) {
                $e = $r['equipo']; $res = $r['resultado']; $d = $res['datos'];

                return [
                    $e->codigo_interno,
                    $e->categoria->nombre ?? '',
                    $e->empresa->nombre ?? '',
                    RenovacionService::CLASIFICACIONES[$res['clasificacion']],
                    $d['procesador_texto'] ?? '',
                    $d['procesador'] ?? '',
                    $d['windows11'] === null ? 'No se sabe' : ($d['windows11'] ? 'Sí' : 'No'),
                    $d['ram'] ?? '',
                    $d['discos'] ?? '',
                    $d['so'] ?? '',
                    $d['edad'] === null ? '' : $d['edad'] . ($d['edad_estimada'] ? ' (estimada)' : ''),
                    $d['reparaciones'],
                    implode(' | ', $res['obsoleto']),
                    implode(' | ', $res['mejoras']),
                    implode(' | ', $res['revisar']),
                    implode(' | ', $res['faltan']),
                ];
            })
            ->values()
            ->prepend(['Código', 'Tipo', 'Empresa', 'Clasificación', 'Procesador (ficha)', 'Procesador (leído)', 'Cumple Windows 11',
                'RAM', 'Disco', 'Sistema operativo', 'Edad (años)', 'Reparaciones 12 meses',
                'Por qué reemplazarlo', 'Mejoras recomendadas', 'A vigilar', 'Falta en la ficha']);

        return Excel::download(new CollectionExport($filas), 'renovacion_equipos_' . now()->format('Ymd_His') . '.xlsx');
    }
}
