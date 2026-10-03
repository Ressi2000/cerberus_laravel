<?php

namespace App\Services;

use App\Models\AtributoEquipo;
use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * DesarmePiezaService
 * ─────────────────────────────────────────────────────────────────────────────
 * Saca una pieza reutilizable de un equipo que SIGUE ACTIVO — a diferencia de
 * ObsolescenciaService (el equipo se archiva en un depósito) y de
 * SustitucionPiezaService (la extracción ocurre dentro de una reparación
 * abierta), acá el equipo no cambia de estado: solo pierde ese atributo y
 * sigue disponible para asignar/trabajar con normalidad. Ej: un técnico
 * encuentra que un equipo bueno tiene una RAM de sobra y la retira para
 * usarla en otro — el equipo de origen no se toca para nada más.
 *
 * Reutiliza ExtraccionPiezaService (misma validación de "solo atributos
 * reutilizable", mismo bucket de Almacén, misma traza en PiezaExtraida) con
 * su propio motivo (MOTIVO_DESARME_MANUAL) para que quede clara la diferencia
 * en el historial del equipo y en la trazabilidad de la pieza.
 */
class DesarmePiezaService
{
    public function __construct(private ExtraccionPiezaService $extraccion)
    {
    }

    /**
     * @param array<int, array{tipo:string, atributo_id:int, grupo_instancia_id:?int, destino:string}> $piezas
     */
    public function desarmar(
        Equipo $equipo,
        array $piezas,
        User $actor,
        ?Deposito $deposito = null,
        ?string $observaciones = null,
    ): void {
        if (! $equipo->activo) {
            throw new \InvalidArgumentException(
                'Solo se puede desarmar un equipo activo. Un equipo dado de baja ya pasó por el flujo de obsolescencia.'
            );
        }

        if (Mantenimiento::where('equipo_id', $equipo->id)->abiertos()->exists()) {
            throw new \InvalidArgumentException(
                'Este equipo tiene un mantenimiento o reparación abierto. Retira la pieza desde ese caso para no duplicar la trazabilidad.'
            );
        }

        DB::transaction(function () use ($equipo, $piezas, $actor, $deposito, $observaciones) {
            foreach ($piezas as $p) {
                if ($p['tipo'] === 'grupo') {
                    $instancia = EquipoAtributoGrupoInstancia::findOrFail($p['grupo_instancia_id']);
                    $this->extraccion->extraerDeGrupoInstancia(
                        $equipo, $instancia, $p['destino'], $actor,
                        PiezaExtraida::MOTIVO_DESARME_MANUAL, $deposito, null, $observaciones,
                    );
                } else {
                    $atributo = AtributoEquipo::findOrFail($p['atributo_id']);
                    $this->extraccion->extraerDeAtributoSimple(
                        $equipo, $atributo, $p['destino'], $actor,
                        PiezaExtraida::MOTIVO_DESARME_MANUAL, $deposito, null, $observaciones,
                    );
                }
            }
        });
    }
}
