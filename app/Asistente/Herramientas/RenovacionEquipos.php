<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Models\Equipo;
use App\Models\User;
use App\Services\RenovacionService;

class RenovacionEquipos extends Herramienta
{
    public function nombre(): string
    {
        return 'renovacion_equipos';
    }

    public function descripcion(): string
    {
        return 'Clasificación de renovación de los equipos según los criterios configurados en Cerberus '
            . '(Obsoleto, Repotenciable, Por revisar, Sano), con los motivos. Úsala para "¿qué equipos hay que '
            . 'cambiar?", "¿qué laptops se pueden repotenciar?". Las reglas las define la empresa; no inventes otras.';
    }

    public function parametros(): array
    {
        return [
            'clasificacion' => ['type' => 'string', 'enum' => ['obsoleto', 'repotenciable', 'revisar', 'sano'], 'description' => 'Solo los de esta clasificación.'],
            'categoria'     => ['type' => 'string', 'description' => 'Categoría de equipo (ej. "Laptop").'],
            'limite'        => ['type' => 'integer', 'description' => 'Máximo de equipos listados (por defecto 30).'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $q = Equipo::visiblePara($actor)->where('activo', true)->with(['categoria', 'estado', 'ubicacion']);
        if ($cat = $args['categoria'] ?? null) {
            $q->whereHas('categoria', fn ($c) => $c->where('nombre', 'like', $this->like($cat)));
        }

        $evaluados = app(RenovacionService::class)->evaluarEquipos($q->get())
            ->filter(fn ($r) => $r['criterio']->evaluar)
            ->reject(fn ($r) => $r['resultado']['clasificacion'] === 'sin_datos');

        $conteo = $evaluados->countBy(fn ($r) => RenovacionService::CLASIFICACIONES[$r['resultado']['clasificacion']]);

        if ($clase = $args['clasificacion'] ?? null) {
            $evaluados = $evaluados->filter(fn ($r) => $r['resultado']['clasificacion'] === $clase);
        }

        return [
            'evaluados' => $conteo->sum(),
            'conteo'    => $conteo->all(),
            'equipos'   => $evaluados->take($this->limite($args, 30))->map(fn ($r) => $this->resumenEquipo($r['equipo']) + [
                'clasificacion' => RenovacionService::CLASIFICACIONES[$r['resultado']['clasificacion']],
                'motivos'       => array_values(array_merge($r['resultado']['obsoleto'], $r['resultado']['mejoras'], $r['resultado']['revisar'])),
                'datos'         => array_filter($r['resultado']['datos'] ?? [], fn ($v) => is_scalar($v) && $v !== ''),
            ])->values()->all(),
            'url'       => route('admin.renovacion.index'),
        ];
    }
}
