<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Models\PiezaExtraida;
use App\Models\User;

class BuscarPiezas extends Herramienta
{
    public function nombre(): string
    {
        return 'buscar_piezas';
    }

    public function descripcion(): string
    {
        return 'Busca piezas con trazabilidad (RAM, discos, fuentes… cada una con su serial o código) por serial, '
            . 'nombre de la variante o característica, y dice dónde está cada una hoy: en el almacén, instalada en '
            . 'qué equipo, en un depósito o retirada. Úsala para "¿dónde está el disco con serial X?" o '
            . '"¿qué memorias de 8 GB hay en el almacén?".';
    }

    public function parametros(): array
    {
        return [
            'texto'   => ['type' => 'string', 'description' => 'Serial, código, nombre de la variante o valor de su ficha (ej. "DDR4", "512").'],
            'estado'  => ['type' => 'string', 'enum' => array_keys(PiezaExtraida::ESTADOS), 'description' => 'Dónde está: ' . implode(', ', array_map(fn ($k, $v) => "{$k} ({$v})", array_keys(PiezaExtraida::ESTADOS), PiezaExtraida::ESTADOS)) . '.'],
            'equipo'  => ['type' => 'string', 'description' => 'Código de un equipo: piezas que salieron de él o están instaladas en él.'],
            'limite'  => ['type' => 'integer', 'description' => 'Máximo de resultados (por defecto 20).'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $q = PiezaExtraida::visiblePara($actor)->with(['componenteAlmacen.familia', 'equipoOrigen', 'equipoDestino', 'deposito']);

        if ($texto = $args['texto'] ?? null) {
            $like = $this->like($texto);
            $q->where(fn ($w) => $w->where('identificador', 'like', $like)
                ->orWhere('valor_extraido', 'like', $like)
                ->orWhere('observaciones', 'like', $like)
                ->orWhereHas('componenteAlmacen', fn ($c) => $c->where('nombre', 'like', $like)));
        }
        if ($estado = $args['estado'] ?? null) {
            $q->where('estado', $estado);
        }
        if ($codigo = $args['equipo'] ?? null) {
            $equipo = $this->encontrarEquipo($actor, $codigo);
            $q->where(fn ($w) => $w->where('equipo_origen_id', $equipo->id)->orWhere('equipo_destino_id', $equipo->id));
        }

        $total = (clone $q)->count();
        $piezas = $q->latest('updated_at')->limit($this->limite($args))->get();

        return [
            'total'     => $total,
            'mostradas' => $piezas->count(),
            'piezas'    => $piezas->map(fn (PiezaExtraida $p) => array_filter([
                'id'        => $p->id,
                'codigo'    => $p->codigo(),
                'pieza'     => $p->componenteAlmacen?->nombre ?? $p->descripcion(),
                'familia'   => $p->componenteAlmacen?->familia?->nombre,
                'estado'    => $p->labelEstado(),
                'donde'     => $p->ubicacion(),
                'condicion' => $p->labelCondicion(),
                'salio_de'  => $p->equipoOrigen?->codigo_interno,
            ]))->all(),
        ];
    }
}
