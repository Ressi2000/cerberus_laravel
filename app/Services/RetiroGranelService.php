<?php

namespace App\Services;

use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
use App\Models\RetiroGranel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * RetiroGranelService
 * ─────────────────────────────────────────────────────────────────────────────
 * Lo A GRANEL (bisagras, tornillos, pasta térmica…) no tiene serial, pero sí
 * traza: cada vez que sale de donde estaba queda un RetiroGranel.
 *
 * - devolver():          en un caso abierto se registró de más → vuelve al almacén.
 * - retirarDelEquipo():  estaba puesto en el equipo y se saca (en cualquier caso
 *                        abierto de ese equipo) → almacén, depósito o desecho.
 * - danado():            se dañó estando guardado → depósito o desecho.
 *
 * Cuánto hay puesto en un equipo = lo entregado en sus casos − devoluciones − retiros.
 */
class RetiroGranelService
{
    const MOTIVO_MINIMO = 5;

    // ── Consultas ────────────────────────────────────────────────────────────

    /**
     * Lo a granel puesto hoy en el equipo, por variante.
     *
     * @return Collection<int, object{componente: ComponenteAlmacen, cantidad: int}>
     */
    public function instaladoEnEquipo(Equipo $equipo): Collection
    {
        $entregado = MantenimientoComponente::query()
            ->where('estado', MantenimientoComponente::ESTADO_ENTREGADO)
            ->whereNull('pieza_id')
            ->whereHas('mantenimiento', fn ($q) => $q->where('equipo_id', $equipo->id))
            ->whereHas('componente.familia', fn ($q) => $q->where('control', \App\Models\FamiliaComponente::CONTROL_GRANEL))
            ->selectRaw('componente_id, sum(cantidad_requerida) as total')
            ->groupBy('componente_id')
            ->pluck('total', 'componente_id');

        $sacado = RetiroGranel::where('equipo_id', $equipo->id)
            ->whereIn('tipo', [RetiroGranel::TIPO_DEVOLUCION, RetiroGranel::TIPO_RETIRO])
            ->selectRaw('componente_id, sum(cantidad) as total')
            ->groupBy('componente_id')
            ->pluck('total', 'componente_id');

        $componentes = ComponenteAlmacen::withTrashed()->whereIn('id', $entregado->keys())->get()->keyBy('id');

        return $entregado
            ->map(fn ($total, $id) => (object) [
                'componente' => $componentes[$id] ?? null,
                'cantidad'   => (int) $total - (int) ($sacado[$id] ?? 0),
            ])
            ->filter(fn ($r) => $r->componente && $r->cantidad > 0)
            ->values();
    }

    /** Cuánto de un renglón de "lo usado" todavía se puede devolver. */
    public function devolvible(MantenimientoComponente $item): int
    {
        $devuelto = (int) RetiroGranel::where('mantenimiento_componente_id', $item->id)
            ->where('tipo', RetiroGranel::TIPO_DEVOLUCION)
            ->sum('cantidad');

        return max(0, (int) $item->cantidad_requerida - $devuelto);
    }

    /** Lo a granel que hay hoy en un depósito, por variante, con su detalle. */
    public function enDeposito(Deposito $deposito): Collection
    {
        return RetiroGranel::with(['equipo:id,codigo_interno', 'mantenimiento:id', 'registradoPor:id,name'])
            ->where('deposito_id', $deposito->id)
            ->where('destino', RetiroGranel::DESTINO_DEPOSITO)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('variante')
            ->map(fn ($g, $variante) => (object) ['variante' => $variante, 'cantidad' => (int) $g->sum('cantidad'), 'entradas' => $g])
            ->sortKeys()
            ->values();
    }

    // ── Operaciones ──────────────────────────────────────────────────────────

    /** Se registró de más en el caso (error): vuelve al almacén. */
    public function devolver(MantenimientoComponente $item, int $cantidad, string $motivo, User $actor): RetiroGranel
    {
        $caso = $item->mantenimiento;
        $this->exigirCasoAbierto($caso);
        $motivo = $this->exigirMotivo($motivo);

        $componente = $item->componente;
        if (! $componente?->esAGranel() || $item->estado !== MantenimientoComponente::ESTADO_ENTREGADO || $item->pieza_id) {
            throw new \InvalidArgumentException('Solo se devuelve lo usado a granel.');
        }

        return DB::transaction(function () use ($item, $cantidad, $motivo, $actor, $caso, $componente) {
            MantenimientoComponente::whereKey($item->id)->lockForUpdate()->first();

            // No más de lo usado en este renglón, ni de lo que sigue puesto en el
            // equipo (parte pudo retirarse después en otro caso).
            $puesto = $this->instaladoEnEquipo($caso->equipo)->firstWhere('componente.id', $componente->id)?->cantidad ?? 0;
            $maximo = min($this->devolvible($item), $puesto);

            if ($cantidad < 1 || $cantidad > $maximo) {
                throw new \InvalidArgumentException("Se pueden devolver como máximo {$maximo}.");
            }

            $mov = $componente->registrarEntrada($cantidad, $actor, "Devolución del caso #{$caso->id}", $motivo);
            $mov->update(['mantenimiento_id' => $caso->id]);

            return $this->registrar($componente, RetiroGranel::TIPO_DEVOLUCION, $cantidad, RetiroGranel::DESTINO_ALMACEN, null,
                $caso->equipo, $caso, $item, $mov->id, $motivo, $actor);
        });
    }

    /** Estaba puesto en el equipo del caso y se saca. */
    public function retirarDelEquipo(
        Mantenimiento $caso,
        ComponenteAlmacen $componente,
        int $cantidad,
        string $destino,
        ?Deposito $deposito,
        string $motivo,
        User $actor,
    ): RetiroGranel {
        $this->exigirCasoAbierto($caso);
        $motivo = $this->exigirMotivo($motivo);
        $this->exigirDestino($destino, $deposito, (int) $caso->empresa_id, conAlmacen: true);

        return DB::transaction(function () use ($caso, $componente, $cantidad, $destino, $deposito, $motivo, $actor) {
            $puesto = $this->instaladoEnEquipo($caso->equipo)->firstWhere('componente.id', $componente->id)?->cantidad ?? 0;

            if ($cantidad < 1 || $cantidad > $puesto) {
                throw new \InvalidArgumentException("El equipo tiene puestas {$puesto} de «{$componente->nombre}».");
            }

            $movId = null;
            if ($destino === RetiroGranel::DESTINO_ALMACEN) {
                if ($componente->trashed()) {
                    $componente->restore();
                }
                $mov = $componente->registrarEntrada($cantidad, $actor, "Retirado de {$caso->equipo->codigo_interno} (caso #{$caso->id})", $motivo);
                $mov->update(['mantenimiento_id' => $caso->id]);
                $movId = $mov->id;
            }

            return $this->registrar($componente, RetiroGranel::TIPO_RETIRO, $cantidad, $destino, $deposito,
                $caso->equipo, $caso, null, $movId, $motivo, $actor);
        });
    }

    /** Se dañó estando guardado en el almacén: a un depósito o se desecha. */
    public function danado(ComponenteAlmacen $componente, int $cantidad, string $destino, ?Deposito $deposito, string $motivo, User $actor): RetiroGranel
    {
        $motivo = $this->exigirMotivo($motivo);
        $this->exigirDestino($destino, $deposito, (int) $componente->empresa_id, conAlmacen: false);

        return DB::transaction(function () use ($componente, $cantidad, $destino, $deposito, $motivo, $actor) {
            $mov = $componente->registrarSalida($cantidad, $actor,
                $destino === RetiroGranel::DESTINO_DEPOSITO ? "Dañado — a depósito {$deposito->nombre}" : 'Dañado — desechado',
                null, [], $motivo);

            return $this->registrar($componente, RetiroGranel::TIPO_DANADO, $cantidad, $destino, $deposito,
                null, null, null, $mov->id, $motivo, $actor);
        });
    }

    // ── Internos ─────────────────────────────────────────────────────────────

    private function registrar(ComponenteAlmacen $componente, string $tipo, int $cantidad, string $destino, ?Deposito $deposito,
        ?Equipo $equipo, ?Mantenimiento $caso, ?MantenimientoComponente $item, ?int $movId, string $motivo, User $actor): RetiroGranel
    {
        return RetiroGranel::create([
            'empresa_id'                  => $componente->empresa_id,
            'componente_id'               => $componente->id,
            'variante'                    => $componente->nombre,
            'tipo'                        => $tipo,
            'cantidad'                    => $cantidad,
            'destino'                     => $destino,
            'deposito_id'                 => $destino === RetiroGranel::DESTINO_DEPOSITO ? $deposito?->id : null,
            'equipo_id'                   => $equipo?->id,
            'mantenimiento_id'            => $caso?->id,
            'mantenimiento_componente_id' => $item?->id,
            'movimiento_componente_id'    => $movId,
            'motivo'                      => $motivo,
            'registrado_por'              => $actor->id,
        ]);
    }

    private function exigirCasoAbierto(?Mantenimiento $caso): void
    {
        if (! $caso || ! $caso->permiteRegistrarTrabajo()) {
            throw new \InvalidArgumentException('Solo se puede mientras el caso está en trabajo (abierto o reabierto).');
        }
    }

    private function exigirMotivo(string $motivo): string
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < self::MOTIVO_MINIMO) {
            throw new \InvalidArgumentException('Escribe el motivo.');
        }

        return $motivo;
    }

    private function exigirDestino(string $destino, ?Deposito $deposito, int $empresaId, bool $conAlmacen): void
    {
        $validos = $conAlmacen
            ? [RetiroGranel::DESTINO_ALMACEN, RetiroGranel::DESTINO_DEPOSITO, RetiroGranel::DESTINO_DESECHO]
            : [RetiroGranel::DESTINO_DEPOSITO, RetiroGranel::DESTINO_DESECHO];

        if (! in_array($destino, $validos, true)) {
            throw new \InvalidArgumentException('Elige a dónde va.');
        }

        if ($destino === RetiroGranel::DESTINO_DEPOSITO && (! $deposito || (int) $deposito->empresa_id !== $empresaId)) {
            throw new \InvalidArgumentException('Elige un depósito de la misma empresa.');
        }
    }
}
