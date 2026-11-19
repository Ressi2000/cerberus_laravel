<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Asistente\HerramientaException;
use App\Models\AsignacionItem;
use App\Models\PrestamoItem;
use App\Models\User;

class EquiposDePersona extends Herramienta
{
    public function nombre(): string
    {
        return 'equipos_de_persona';
    }

    public function descripcion(): string
    {
        return 'Equipos que tiene hoy una persona (asignados y prestados), buscándola por nombre, usuario, ficha o cédula. '
            . 'Úsala para "¿qué equipos tiene Fulano?" o "¿qué tiene asignado el departamento X?" (con departamento).';
    }

    public function parametros(): array
    {
        return [
            'persona'      => ['type' => 'string', 'description' => 'Nombre, usuario, ficha o cédula de la persona.'],
            'departamento' => ['type' => 'string', 'description' => 'Nombre del departamento: equipos asignados a sus personas o al área común.'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $persona = $args['persona'] ?? null;
        $depto = $args['departamento'] ?? null;

        if (! $persona && ! $depto) {
            throw new HerramientaException('Indica el nombre de la persona o el departamento.');
        }

        $usuario = null;
        if ($persona) {
            $like = $this->like($persona);
            $candidatos = User::visiblePara($actor)
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('username', 'like', $like)
                    ->orWhere('ficha', $persona)->orWhere('cedula', $persona))
                ->limit(6)->get();

            if ($candidatos->isEmpty()) {
                throw new HerramientaException("No encontré a ninguna persona que coincida con «{$persona}».");
            }
            if ($candidatos->count() > 1) {
                $exacto = $candidatos->first(fn ($u) => mb_strtolower($u->name) === mb_strtolower($persona));
                if (! $exacto) {
                    throw new HerramientaException('Hay varias personas que coinciden: ' . $candidatos->pluck('name')->implode(', ') . '. ¿Cuál de ellas?');
                }
                $candidatos = collect([$exacto]);
            }
            $usuario = $candidatos->first();
        }

        $filtroReceptor = function ($q) use ($usuario, $depto) {
            if ($usuario) {
                $q->where('usuario_id', $usuario->id);
            }
            if ($depto) {
                $like = $this->like($depto);
                $q->where(fn ($w) => $w->whereHas('areaDepartamento', fn ($d) => $d->where('nombre', 'like', $like))
                    ->orWhereHas('usuario.departamento', fn ($d) => $d->where('nombre', 'like', $like)));
            }
        };

        $asignados = AsignacionItem::where('devuelto', false)
            ->whereHas('asignacion', fn ($a) => $a->visiblePara($actor)->where($filtroReceptor))
            ->with('equipo.categoria', 'equipo.estado', 'equipo.ubicacion', 'asignacion.usuario', 'asignacion.areaDepartamento', 'asignacion.areaEmpresa')
            ->get()
            ->filter(fn ($i) => $i->equipo)
            ->map(fn ($i) => $this->resumenEquipo($i->equipo) + [
                'asignado_a' => $i->asignacion->receptorNombre(),
                'desde'      => $this->fecha($i->asignacion->fecha_asignacion),
            ]);

        $prestados = PrestamoItem::where('devuelto', false)
            ->whereHas('prestamo', fn ($p) => $p->visiblePara($actor)->where($filtroReceptor))
            ->with('equipo.categoria', 'equipo.estado', 'equipo.ubicacion', 'prestamo.usuario', 'prestamo.areaDepartamento', 'prestamo.areaEmpresa')
            ->get()
            ->filter(fn ($i) => $i->equipo)
            ->map(fn ($i) => $this->resumenEquipo($i->equipo) + [
                'prestado_a'          => $i->prestamo->receptorNombre(),
                'desde'               => $this->fecha($i->prestamo->fecha_prestamo),
                'devolucion_esperada' => $this->fecha($i->prestamo->fecha_devolucion_esperada),
                'vencido'             => $i->prestamo->estaVencido(),
            ]);

        return array_filter([
            'persona'      => $usuario ? array_filter([
                'nombre'       => $usuario->name,
                'departamento' => $usuario->departamento?->nombre,
                'cargo'        => $usuario->cargo?->nombre,
                'ubicacion'    => $usuario->ubicacion?->nombre,
                'url'          => route('admin.usuarios.show', $usuario),
            ]) : null,
            'departamento' => $depto,
            'asignados'    => $asignados->values()->all(),
            'prestados'    => $prestados->values()->all(),
        ], fn ($v) => $v !== null);
    }
}
