<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Caracteristicas;
use App\Asistente\Herramienta;
use App\Asistente\HerramientaException;
use App\Models\CategoriaEquipo;
use App\Models\Equipo;
use App\Models\User;

class BuscarEquipos extends Herramienta
{
    /** Tope de equipos que se revisan al filtrar por característica. */
    const MAX_REVISAR = 5000;

    public function nombre(): string
    {
        return 'buscar_equipos';
    }

    public function descripcion(): string
    {
        return 'Busca equipos por texto libre (código interno, serial, marca, modelo o cualquier característica, '
            . 'o el nombre de la persona/área a la que están asignados) y/o por categoría, estado o ubicación. '
            . 'También filtra con precisión por UNA característica técnica: "caracteristica" + "valor" + "comparacion" '
            . '(ej. RAM mayor_o_igual 8, Procesador contiene i5, Sistema Operativo no_contiene 11, Disco Duro/Tipo contiene SSD). '
            . 'En características de varias entradas (RAM, discos) las comparaciones de número usan el TOTAL (8 GB + 8 GB = 16). '
            . 'Devuelve una lista corta con dónde está cada equipo y a quién está asignado.';
    }

    public function parametros(): array
    {
        return [
            'texto'          => ['type' => 'string', 'description' => 'Código, serial, marca, modelo, valor de una característica o nombre de la persona asignada.'],
            'categoria'      => ['type' => 'string', 'description' => 'Nombre (o parte) de la categoría, ej. "Laptop".'],
            'estado'         => ['type' => 'string', 'description' => 'Nombre (o parte) del estado, ej. "Operativo", "En reparación".'],
            'ubicacion'      => ['type' => 'string', 'description' => 'Nombre (o parte) de la ubicación.'],
            'asignado'       => ['type' => 'boolean', 'description' => 'true: solo equipos asignados; false: solo sin asignar.'],
            'caracteristica' => ['type' => 'string', 'description' => 'Nombre de la característica o de uno de sus campos (ej. "RAM", "Procesador", "Capacidad (GB)", "Tipo"). Si dudas del nombre, usa caracteristicas_equipos.'],
            'valor'          => ['type' => 'string', 'description' => 'Valor a comparar con la característica (texto o número).'],
            'comparacion'    => ['type' => 'string', 'enum' => ['contiene', 'no_contiene', 'igual', 'mayor_o_igual', 'menor_o_igual'], 'description' => 'Cómo comparar (por defecto contiene).'],
            'limite'         => ['type' => 'integer', 'description' => 'Máximo de resultados (por defecto 20, máximo 100).'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $q = Equipo::visiblePara($actor)->with(['categoria', 'estado', 'ubicacion', 'asignacionItemActivo.asignacion.usuario', 'asignacionItemActivo.asignacion.areaDepartamento', 'asignacionItemActivo.asignacion.areaEmpresa']);
        $ocultas = Caracteristicas::ocultas();

        if ($texto = $args['texto'] ?? null) {
            $like = $this->like($texto);
            $q->where(function ($w) use ($like, $ocultas) {
                $w->where('codigo_interno', 'like', $like)
                  ->orWhere('observaciones', 'like', $like)
                  ->orWhereHas('atributosActuales', fn ($a) => $a->where('valor', 'like', $like)->whereNotIn('atributo_id', $ocultas))
                  ->orWhereHas('grupoInstancias', fn ($g) => $g->where('valores', 'like', $like)->whereNotIn('atributo_id', $ocultas))
                  ->orWhereHas('asignacionItemActivo.asignacion', fn ($a) => $a
                      ->whereHas('usuario', fn ($u) => $u->where('name', 'like', $like)->orWhere('username', 'like', $like))
                      ->orWhereHas('areaDepartamento', fn ($d) => $d->where('nombre', 'like', $like)));
            });
        }
        if ($cat = $args['categoria'] ?? null) {
            $q->whereIn('categoria_id', $this->idsCategoria($cat));
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

        if (isset($args['caracteristica'])) {
            return $this->conCaracteristica($q, $args);
        }

        $total = (clone $q)->count();
        $equipos = $q->orderBy('codigo_interno')->limit($this->limite($args))->get();

        return [
            'total'     => $total,
            'mostrados' => $equipos->count(),
            'equipos'   => $equipos->map(fn ($e) => $this->resumenEquipo($e))->all(),
        ];
    }

    /** Filtro preciso por una característica (se evalúa en PHP: sirve igual para valores simples y de varias entradas). */
    private function conCaracteristica($q, array $args): array
    {
        if (! isset($args['valor'])) {
            throw new HerramientaException('Indica el "valor" con el que comparar la característica.');
        }

        $categorias = isset($args['categoria'])
            ? collect($this->idsCategoria($args['categoria']))
            : null;
        $objetivos = Caracteristicas::resolver($args['caracteristica'], $categorias);

        if (! $objetivos) {
            throw new HerramientaException("No hay ninguna característica llamada «{$args['caracteristica']}»"
                . ($categorias ? ' en esa categoría' : '') . '. Usa caracteristicas_equipos para ver los nombres disponibles.');
        }

        $comparacion = $args['comparacion'] ?? 'contiene';
        $numerica = in_array($comparacion, ['mayor_o_igual', 'menor_o_igual'], true);
        $porCategoria = collect($objetivos)->keyBy(fn ($o) => $o[0]->categoria_id);
        $ids = collect($objetivos)->map(fn ($o) => $o[0]->id)->all();

        if ($numerica && Caracteristicas::numero($args['valor']) === null) {
            throw new HerramientaException('Para comparar «mayor o igual» / «menor o igual» el valor tiene que ser un número.');
        }

        $equipos = $q->whereIn('categoria_id', $porCategoria->keys())
            ->with([
                'atributosActuales' => fn ($w) => $w->whereIn('atributo_id', $ids),
                'grupoInstancias'   => fn ($w) => $w->whereIn('atributo_id', $ids),
            ])
            ->orderBy('codigo_interno')->limit(self::MAX_REVISAR)->get();

        $buscado = Caracteristicas::normalizar($args['valor']);
        $coinciden = collect();

        foreach ($equipos as $equipo) {
            [$atributo, $campoId] = $porCategoria[$equipo->categoria_id];
            if ($numerica && $atributo->esGrupo() && ! $campoId) {
                $campoId = Caracteristicas::campoNumerico($atributo);
            }
            $valores = Caracteristicas::valores($equipo, $atributo, $campoId);
            if (! $valores) {
                continue; // sin el dato cargado: no se puede decir si cumple
            }

            $nombreCampo = $campoId ? (collect($atributo->sub_campos)->firstWhere('id', $campoId)['nombre'] ?? $atributo->nombre) : $atributo->nombre;
            $total = null;
            if ($numerica) {
                $nums = array_filter(array_map(fn ($v) => Caracteristicas::numero($v, $nombreCampo), $valores), fn ($n) => $n !== null);
                if (! $nums) {
                    continue;
                }
                $total = array_sum($nums);
                $limite = Caracteristicas::numero($args['valor'], $nombreCampo);
                $cumple = $comparacion === 'mayor_o_igual' ? $total >= $limite : $total <= $limite;
            } else {
                $norm = array_map(fn ($v) => Caracteristicas::normalizar($v), $valores);
                $alguno = fn (callable $f) => (bool) array_filter($norm, $f);
                $cumple = match ($comparacion) {
                    'igual'       => $alguno(fn ($v) => $v === $buscado),
                    'no_contiene' => ! $alguno(fn ($v) => str_contains($v, $buscado)),
                    default       => $alguno(fn ($v) => str_contains($v, $buscado)),
                };
            }

            if ($cumple) {
                $etiqueta = $atributo->nombre . ($campoId && $atributo->esGrupo() ? " · {$nombreCampo}" : '');
                $coinciden->push($this->resumenEquipo($equipo) + [
                    'caracteristica' => $etiqueta,
                    'valor'          => implode(' + ', $valores) . ($total !== null && count($valores) > 1 ? " (total {$total})" : ''),
                ]);
            }
        }

        return array_filter([
            'filtro'    => "{$args['caracteristica']} {$comparacion} {$args['valor']}",
            'revisados' => $equipos->count(),
            'total'     => $coinciden->count(),
            'mostrados' => min($coinciden->count(), $this->limite($args)),
            'equipos'   => $coinciden->take($this->limite($args))->values()->all(),
            'nota'      => $equipos->count() >= self::MAX_REVISAR ? 'Se revisaron solo los primeros ' . self::MAX_REVISAR . ' equipos; agrega filtros para acotar.' : null,
        ], fn ($v) => $v !== null);
    }
}
