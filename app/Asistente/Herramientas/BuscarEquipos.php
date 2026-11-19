<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Models\Equipo;
use App\Models\User;

class BuscarEquipos extends Herramienta
{
    public function nombre(): string
    {
        return 'buscar_equipos';
    }

    public function descripcion(): string
    {
        return 'Busca equipos por texto libre (código interno, serial, marca, modelo o cualquier característica, '
            . 'o el nombre de la persona/área a la que están asignados) y/o por categoría, estado o ubicación. '
            . 'Devuelve una lista corta con dónde está cada uno y a quién está asignado. '
            . 'Úsala para "¿dónde está…?", "¿qué equipos hay en…?" o para encontrar el código de un equipo.';
    }

    public function parametros(): array
    {
        return [
            'texto'     => ['type' => 'string', 'description' => 'Código, serial, marca, modelo, valor de una característica o nombre de la persona asignada.'],
            'categoria' => ['type' => 'string', 'description' => 'Nombre (o parte) de la categoría, ej. "Laptop".'],
            'estado'    => ['type' => 'string', 'description' => 'Nombre (o parte) del estado, ej. "Operativo", "En reparación".'],
            'ubicacion' => ['type' => 'string', 'description' => 'Nombre (o parte) de la ubicación.'],
            'asignado'  => ['type' => 'boolean', 'description' => 'true: solo equipos asignados; false: solo sin asignar.'],
            'limite'    => ['type' => 'integer', 'description' => 'Máximo de resultados (por defecto 20, máximo 100).'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $q = Equipo::visiblePara($actor)->with(['categoria', 'estado', 'ubicacion', 'asignacionItemActivo.asignacion.usuario', 'asignacionItemActivo.asignacion.areaDepartamento', 'asignacionItemActivo.asignacion.areaEmpresa']);

        if ($texto = $args['texto'] ?? null) {
            $like = $this->like($texto);
            $q->where(function ($w) use ($like) {
                $w->where('codigo_interno', 'like', $like)
                  ->orWhere('observaciones', 'like', $like)
                  ->orWhereHas('atributosActuales', fn ($a) => $a->where('valor', 'like', $like))
                  ->orWhereHas('grupoInstancias', fn ($g) => $g->where('valores', 'like', $like))
                  ->orWhereHas('asignacionItemActivo.asignacion', fn ($a) => $a
                      ->whereHas('usuario', fn ($u) => $u->where('name', 'like', $like)->orWhere('username', 'like', $like))
                      ->orWhereHas('areaDepartamento', fn ($d) => $d->where('nombre', 'like', $like)));
            });
        }
        if ($cat = $args['categoria'] ?? null) {
            $q->whereHas('categoria', fn ($c) => $c->where('nombre', 'like', $this->like($cat)));
        }
        if ($estado = $args['estado'] ?? null) {
            $q->whereHas('estado', fn ($c) => $c->where('nombre', 'like', $this->like($estado)));
        }
        if ($ubic = $args['ubicacion'] ?? null) {
            $q->whereHas('ubicacion', fn ($c) => $c->where('nombre', 'like', $this->like($ubic)));
        }
        if (array_key_exists('asignado', $args)) {
            $args['asignado'] ? $q->has('asignacionItemActivo') : $q->doesntHave('asignacionItemActivo');
        }

        $total = (clone $q)->count();
        $equipos = $q->orderBy('codigo_interno')->limit($this->limite($args))->get();

        return [
            'total'    => $total,
            'mostrados' => $equipos->count(),
            'equipos'  => $equipos->map(fn ($e) => $this->resumenEquipo($e))->all(),
        ];
    }
}
