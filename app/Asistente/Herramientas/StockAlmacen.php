<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Models\ComponenteAlmacen;
use App\Models\User;

class StockAlmacen extends Herramienta
{
    public function nombre(): string
    {
        return 'stock_almacen';
    }

    public function descripcion(): string
    {
        return 'Existencias del almacén de repuestos por variante (ej. "Memoria RAM DDR4 8 GB": 5 unidades), '
            . 'filtrando por texto o familia. Úsala para "¿cuántos discos SSD tenemos?" o "¿qué hay en el almacén?".';
    }

    public function parametros(): array
    {
        return [
            'texto'          => ['type' => 'string', 'description' => 'Nombre o parte del nombre de la variante (ej. "DDR4", "SSD 480").'],
            'familia'        => ['type' => 'string', 'description' => 'Familia de piezas (ej. "Memoria RAM", "Discos").'],
            'solo_con_stock' => ['type' => 'boolean', 'description' => 'true (por defecto): solo variantes con existencias.'],
            'limite'         => ['type' => 'integer', 'description' => 'Máximo de variantes (por defecto 50).'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $q = ComponenteAlmacen::visiblePara($actor)->activos()->with(['familia', 'empresa']);

        if ($texto = $args['texto'] ?? null) {
            $q->where('nombre', 'like', $this->like($texto));
        }
        if ($fam = $args['familia'] ?? null) {
            $q->whereHas('familia', fn ($f) => $f->where('nombre', 'like', $this->like($fam)));
        }
        if ($args['solo_con_stock'] ?? true) {
            $q->where('stock_actual', '>', 0);
        }

        $total = (clone $q)->count();
        $unidades = (clone $q)->sum('stock_actual');
        $variantes = $q->orderBy('nombre')->limit($this->limite($args, 50))->get();
        $variasEmpresas = $variantes->pluck('empresa_id')->unique()->count() > 1;

        return [
            'variantes'       => $total,
            'unidades_total'  => (int) $unidades,
            'detalle'         => $variantes->map(fn (ComponenteAlmacen $c) => array_filter([
                'variante' => $c->nombre,
                'familia'  => $c->familia?->nombre,
                'stock'    => (int) $c->stock_actual,
                'unidad'   => $c->unidad,
                'a_granel' => $c->esAGranel() ?: null,
                'empresa'  => $variasEmpresas ? $c->empresa?->nombre : null,
            ], fn ($v) => $v !== null))->all(),
        ];
    }
}
