<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Asistente\HerramientaException;
use App\Models\PiezaExtraida;
use App\Models\User;

class RecorridoPieza extends Herramienta
{
    public function nombre(): string
    {
        return 'recorrido_pieza';
    }

    public function descripcion(): string
    {
        return 'Ficha e historial completo de UNA pieza (por su serial/código o su número interno): de qué equipo salió, '
            . 'cuándo entró al almacén, en qué caso y en qué equipo se instaló, si volvió a salir, etc. '
            . 'Úsala para "hazme un informe del historial de la pieza X".';
    }

    public function parametros(): array
    {
        return [
            'serial' => ['type' => 'string', 'description' => 'Serial o código de la pieza (ej. "SN123" o "AUTO-…").'],
            'id'     => ['type' => 'integer', 'description' => 'Número interno de la pieza (el "#" que muestra su ficha).'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $base = fn () => PiezaExtraida::visiblePara($actor);

        if (isset($args['id'])) {
            $pieza = $base()->find($args['id']);
        } elseif (isset($args['serial'])) {
            $serial = $args['serial'];
            $pieza = $base()->where('identificador', $serial)->first();
            if (! $pieza) {
                $parecidas = $base()->where('identificador', 'like', $this->like($serial))->limit(6)->get();
                if ($parecidas->count() > 1) {
                    throw new HerramientaException('Hay varias piezas parecidas: ' . $parecidas->map->codigo()->implode(', ') . '. ¿Cuál de ellas?');
                }
                $pieza = $parecidas->first();
            }
        } else {
            throw new HerramientaException('Indica el serial o el número interno de la pieza.');
        }

        if (! $pieza) {
            throw new HerramientaException('No encontré esa pieza (o no tienes acceso a ella).');
        }

        $pieza->load(['componenteAlmacen.familia', 'atributo', 'equipoOrigen', 'equipoDestino', 'deposito', 'empresa']);
        $movimientos = $pieza->movimientos()->reorder()->orderBy('created_at')->orderBy('id')
            ->with(['equipoRelacionado', 'depositoRelacionado', 'registradoPor'])->get();

        return array_filter([
            'id'          => $pieza->id,
            'codigo'      => $pieza->codigo(),
            'pieza'       => $pieza->componenteAlmacen?->nombre ?? $pieza->descripcion(),
            'familia'     => $pieza->componenteAlmacen?->familia?->nombre,
            'empresa'     => $pieza->empresa?->nombre,
            'estado'      => $pieza->labelEstado(),
            'donde_esta'  => $pieza->ubicacion(),
            'condicion'   => $pieza->labelCondicion(),
            'origen'      => (PiezaExtraida::MOTIVOS[$pieza->motivo] ?? $pieza->motivo)
                . ($pieza->equipoOrigen ? " · equipo {$pieza->equipoOrigen->codigo_interno}" : ''),
            'ficha'       => $this->ficha($pieza) ?: null,
            'observaciones' => $pieza->observaciones,
            'recorrido'   => $movimientos->map(fn ($m) => array_filter([
                'fecha'       => $this->fecha($m->created_at, true),
                'que_paso'    => $m->labelTipo(),
                'equipo'      => $m->equipoRelacionado?->codigo_interno,
                'deposito'    => $m->depositoRelacionado?->nombre,
                'caso'        => $m->mantenimiento_id ? "#{$m->mantenimiento_id}" : null,
                'registro'    => $m->registradoPor?->name ?? 'Sistema',
                'nota'        => $m->observaciones,
            ]))->all(),
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function ficha(PiezaExtraida $pieza): array
    {
        if (empty($pieza->valor_extraido) || ! $pieza->atributo) {
            return [];
        }
        if (! $pieza->atributo->esGrupo()) {
            $v = $pieza->valor_extraido['valor'] ?? null;

            return $v === null || $v === '' ? [] : [$pieza->atributo->nombre => (string) $v];
        }
        $campos = collect($pieza->atributo->sub_campos ?? [])->keyBy('id');

        return collect($pieza->valor_extraido)->reject(fn ($v) => $v === null || $v === '')
            ->mapWithKeys(fn ($v, $id) => [($campos[$id]['nombre'] ?? 'Campo') => (string) $v])->all();
    }
}
