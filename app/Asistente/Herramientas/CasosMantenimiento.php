<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Asistente\HerramientaException;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Support\Carbon;

class CasosMantenimiento extends Herramienta
{
    public function nombre(): string
    {
        return 'casos_mantenimiento';
    }

    public function descripcion(): string
    {
        return 'Lista casos de mantenimiento (preventivos y reparaciones) con filtros: abiertos o cerrados, tipo, estado, '
            . 'equipo, responsable, categoría y rango de fechas. Devuelve también cuántos hay por estado. '
            . 'Úsala para "¿qué reparaciones hay abiertas?", "¿cuántos mantenimientos se hicieron en marzo?".';
    }

    public function parametros(): array
    {
        return [
            'situacion'   => ['type' => 'string', 'enum' => ['abiertos', 'cerrados', 'todos'], 'description' => 'Por defecto: abiertos.'],
            'tipo'        => ['type' => 'string', 'enum' => ['preventivo', 'correctivo'], 'description' => 'correctivo = reparación.'],
            'estado'      => ['type' => 'string', 'description' => 'Estado exacto: ' . implode(', ', array_unique(array_merge(Mantenimiento::ESTADOS_PREVENTIVO, Mantenimiento::ESTADOS_CORRECTIVO))) . '.'],
            'equipo'      => ['type' => 'string', 'description' => 'Código interno del equipo.'],
            'responsable' => ['type' => 'string', 'description' => 'Nombre del analista responsable.'],
            'categoria'   => ['type' => 'string', 'description' => 'Categoría de equipo (ej. "Laptop").'],
            'desde'       => ['type' => 'string', 'description' => 'Fecha inicial (AAAA-MM-DD) según la fecha de inicio del caso.'],
            'hasta'       => ['type' => 'string', 'description' => 'Fecha final (AAAA-MM-DD).'],
            'limite'      => ['type' => 'integer', 'description' => 'Máximo de casos (por defecto 30).'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $q = Mantenimiento::visiblePara($actor)->with(['equipo.categoria', 'responsable']);

        match ($args['situacion'] ?? 'abiertos') {
            'abiertos' => $q->whereNotIn('estado', Mantenimiento::ESTADOS_TERMINALES),
            'cerrados' => $q->whereIn('estado', Mantenimiento::ESTADOS_TERMINALES),
            default    => null,
        };

        if ($tipo = $args['tipo'] ?? null) {
            $tipo === 'preventivo' ? $q->preventivos() : $q->correctivos();
        }
        if ($estado = $args['estado'] ?? null) {
            $q->where('estado', $estado);
        }
        if ($codigo = $args['equipo'] ?? null) {
            $q->where('equipo_id', $this->encontrarEquipo($actor, $codigo)->id);
        }
        if ($resp = $args['responsable'] ?? null) {
            $q->whereHas('responsable', fn ($u) => $u->where('name', 'like', $this->like($resp)));
        }
        if ($cat = $args['categoria'] ?? null) {
            $q->whereHas('equipo.categoria', fn ($c) => $c->where('nombre', 'like', $this->like($cat)));
        }
        if ($desde = $args['desde'] ?? null) {
            $q->whereDate('fecha_inicio', '>=', $this->dia($desde));
        }
        if ($hasta = $args['hasta'] ?? null) {
            $q->whereDate('fecha_inicio', '<=', $this->dia($hasta));
        }

        $porEstado = (clone $q)->reorder()->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado')->map(fn ($n) => (int) $n);
        $casos = $q->latest('fecha_inicio')->latest('id')->limit($this->limite($args, 30))->get();

        return [
            'total'      => $porEstado->sum(),
            'por_estado' => $porEstado->all(),
            'casos'      => $casos->map(fn (Mantenimiento $m) => array_filter([
                'numero'      => $m->id,
                'tipo'        => $m->esCorrectivo() ? 'Reparación' : 'Preventivo',
                'estado'      => $m->estado,
                'equipo'      => $m->equipo?->codigo_interno,
                'categoria'   => $m->equipo?->categoria?->nombre,
                'responsable' => $m->responsable?->name,
                'inicio'      => $this->fecha($m->fecha_inicio),
                'fin'         => $this->fecha($m->fecha_fin_real),
                'falla'       => $m->falla_reportada,
                'esperando_repuesto' => $m->esperando_componente ? true : null,
                'url'         => route('admin.mantenimientos.show', $m),
            ], fn ($v) => $v !== null && $v !== ''))->all(),
        ];
    }

    private function dia(string $texto): string
    {
        try {
            return Carbon::parse($texto)->toDateString();
        } catch (\Throwable) {
            throw new HerramientaException("No entendí la fecha «{$texto}». Usa el formato AAAA-MM-DD.");
        }
    }
}
