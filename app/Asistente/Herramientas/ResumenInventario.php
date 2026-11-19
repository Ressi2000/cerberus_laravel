<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Models\ComponenteAlmacen;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Prestamo;
use App\Models\User;

class ResumenInventario extends Herramienta
{
    public function nombre(): string
    {
        return 'resumen_inventario';
    }

    public function descripcion(): string
    {
        return 'Números generales del inventario: equipos por categoría, por estado y por ubicación; cuántos están '
            . 'asignados o libres; casos de mantenimiento abiertos; préstamos vencidos y unidades en el almacén. '
            . 'Úsala para preguntas de "¿cuántos…?" o para un panorama general.';
    }

    public function parametros(): array
    {
        return [
            'categoria' => ['type' => 'string', 'description' => 'Limitar a una categoría (ej. "Laptop").'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $base = fn () => Equipo::visiblePara($actor)->where('equipos.activo', true)
            ->when($args['categoria'] ?? null, fn ($q, $cat) => $q->whereHas('categoria', fn ($c) => $c->where('nombre', 'like', $this->like($cat))));

        $agrupar = fn (string $relacion) => $base()->with($relacion)->get()
            ->countBy(fn ($e) => $e->{$relacion}?->nombre ?? 'Sin dato')->sortDesc()->all();

        $total = $base()->count();
        $asignados = $base()->has('asignacionItemActivo')->count();

        return [
            'equipos_activos'   => $total,
            'asignados'         => $asignados,
            'sin_asignar'       => $total - $asignados,
            'por_categoria'     => $agrupar('categoria'),
            'por_estado'        => $agrupar('estado'),
            'por_ubicacion'     => $agrupar('ubicacion'),
            'casos_abiertos'    => [
                'reparaciones' => Mantenimiento::visiblePara($actor)->abiertos()->correctivos()->count(),
                'preventivos'  => Mantenimiento::visiblePara($actor)->abiertos()->preventivos()->count(),
            ],
            'prestamos_vencidos' => Prestamo::visiblePara($actor)->vencidos()->count(),
            'almacen'           => [
                'variantes_con_stock' => ComponenteAlmacen::visiblePara($actor)->activos()->where('stock_actual', '>', 0)->count(),
                'unidades'            => (int) ComponenteAlmacen::visiblePara($actor)->activos()->sum('stock_actual'),
            ],
        ];
    }
}
