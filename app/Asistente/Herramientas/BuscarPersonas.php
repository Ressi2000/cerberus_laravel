<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Models\AsignacionItem;
use App\Models\User;

class BuscarPersonas extends Herramienta
{
    public function nombre(): string
    {
        return 'buscar_personas';
    }

    public function descripcion(): string
    {
        return 'Busca personas por nombre o parte del nombre (ej. "Luis") y devuelve los candidatos con su departamento, '
            . 'cargo y ubicación, y cuántos equipos tienen. Úsala cuando el nombre es incompleto o ambiguo para '
            . 'preguntar "¿cuál de ellos?" o para elegir el correcto según una pista ("el Luis de Contabilidad"). '
            . 'No devuelve datos personales (cédula, teléfono, correo).';
    }

    public function parametros(): array
    {
        return [
            'nombre'       => ['type' => 'string', 'description' => 'Nombre o parte del nombre.'],
            'departamento' => ['type' => 'string', 'description' => 'Opcional: departamento para acotar.'],
            'limite'       => ['type' => 'integer', 'description' => 'Máximo de personas (por defecto 10).'],
        ];
    }

    public function requeridos(): array
    {
        return ['nombre'];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $palabras = preg_split('/\s+/', trim($args['nombre']));
        $q = User::visiblePara($actor)->with(['departamento', 'cargo', 'ubicacion']);
        foreach ($palabras as $p) {
            $q->where('name', 'like', $this->like($p));   // "luis per" encuentra "Luis Pérez"
        }
        if ($depto = $args['departamento'] ?? null) {
            $q->whereHas('departamento', fn ($d) => $d->where('nombre', 'like', $this->like($depto)));
        }

        $total = (clone $q)->count();
        $personas = $q->orderBy('name')->limit($this->limite($args, 10, 50))->get();
        $equipos = AsignacionItem::where('devuelto', false)
            ->whereHas('asignacion', fn ($a) => $a->whereIn('usuario_id', $personas->pluck('id')))
            ->with('asignacion:id,usuario_id')->get()
            ->countBy(fn ($i) => $i->asignacion->usuario_id);

        return [
            'total'    => $total,
            'personas' => $personas->map(fn (User $u) => array_filter([
                'nombre'           => $u->name,
                'departamento'     => $u->departamento?->nombre,
                'cargo'            => $u->cargo?->nombre,
                'ubicacion'        => $u->ubicacion?->nombre,
                'equipos_asignados' => (int) ($equipos[$u->id] ?? 0),
                'activo'           => $u->estado === 'Activo' ? null : false,
                'url'              => route('admin.usuarios.show', $u),
            ], fn ($v) => $v !== null))->all(),
        ];
    }
}
