<?php

namespace App\Services;

use App\Models\AtributoEquipo;
use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EstadoEquipo;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ObsolescenciaService
 * ─────────────────────────────────────────────────────────────────────────────
 * Orquesta el "dar de baja" completo de un equipo: extrae cada pieza que el
 * usuario decidió rescatar (vía ExtraccionPiezaService, cada una con su
 * propio destino) y archiva el equipo mismo en un Depósito.
 *
 * Reutilizado desde dos puntos de entrada:
 *   - La ficha del equipo (EquipoDeleteModal) — baja directa.
 *   - Una reparación sin solución (Mantenimiento::marcarDadoDeBaja(), vía
 *     MantenimientoBajaModal) — aquí solo se usa extraerPiezas(), porque el
 *     modelo Mantenimiento archiva el equipo dentro de su propia
 *     transacción, junto con el resto de su lógica (cerrar asignación,
 *     motivo, aprobador).
 */
class ObsolescenciaService
{
    public function __construct(private ExtraccionPiezaService $extraccion)
    {
    }

    /**
     * Extrae las piezas seleccionadas y archiva el equipo en un depósito —
     * todo en una transacción. Uso directo, sin pasar por una reparación.
     *
     * @param array<int, array{tipo:string, atributo_id:int, grupo_instancia_id:?int, destino:string}> $piezas
     */
    public function darDeBaja(
        Equipo $equipo,
        Deposito $deposito,
        array $piezas,
        User $actor,
        ?string $observaciones = null,
    ): void {
        DB::transaction(function () use ($equipo, $deposito, $piezas, $actor, $observaciones) {
            $this->extraerPiezas($equipo, $piezas, $actor, $deposito, $observaciones);
            $this->archivarEquipo($equipo, $deposito);
        });
    }

    /**
     * Solo la extracción de piezas (sin tocar el equipo todavía). La usa
     * Mantenimiento::marcarDadoDeBaja(), que archiva el equipo por su cuenta
     * como parte de su propia transacción.
     *
     * @param array<int, array{tipo:string, atributo_id:int, grupo_instancia_id:?int, destino:string}> $piezas
     */
    public function extraerPiezas(
        Equipo $equipo,
        array $piezas,
        User $actor,
        Deposito $deposito,
        ?string $observaciones = null,
        ?Mantenimiento $mantenimiento = null,
    ): void {
        foreach ($piezas as $p) {
            if ($p['tipo'] === 'grupo') {
                $instancia = EquipoAtributoGrupoInstancia::findOrFail($p['grupo_instancia_id']);
                $this->extraccion->extraerDeGrupoInstancia(
                    $equipo, $instancia, $p['destino'], $actor,
                    PiezaExtraida::MOTIVO_BAJA_EQUIPO, $deposito, $mantenimiento, $observaciones,
                );
            } else {
                $atributo = AtributoEquipo::findOrFail($p['atributo_id']);
                $this->extraccion->extraerDeAtributoSimple(
                    $equipo, $atributo, $p['destino'], $actor,
                    PiezaExtraida::MOTIVO_BAJA_EQUIPO, $deposito, $mantenimiento, $observaciones,
                );
            }
        }
    }

    /** Marca el equipo como dado de baja y lo deja archivado en el depósito indicado. */
    public function archivarEquipo(Equipo $equipo, Deposito $deposito): void
    {
        $estadoBaja = EstadoEquipo::where('nombre', EstadoEquipo::BAJA)->value('id');

        $equipo->update([
            'activo'      => false,
            'estado_id'   => $estadoBaja ?? $equipo->estado_id,
            'deposito_id' => $deposito->id,
        ]);
    }
}
