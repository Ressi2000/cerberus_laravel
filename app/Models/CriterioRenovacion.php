<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Criterios de renovación de una categoría de equipos (ver RenovacionService).
 * Las categorías sin fila usan VALORES_SUGERIDOS.
 */
class CriterioRenovacion extends Model
{
    use Auditable;

    protected $table = 'criterios_renovacion';

    /**
     * Valores sugeridos, pensados para que tengan una razón que se pueda
     * explicar a quien aprueba una compra:
     *
     * - Procesador: Intel 8.ª generación y AMD Ryzen 2000 en adelante son lo
     *   mínimo que Microsoft acepta para Windows 11. Windows 10 dejó de tener
     *   soporte en octubre de 2025: lo que no puede pasar a Windows 11 queda
     *   sin actualizaciones de seguridad.
     * - Edad: a los 5 años conviene revisarlo; a los 8 la falla y el costo de
     *   repuestos suelen no compensar.
     * - RAM: 8 GB es lo mínimo cómodo para Windows 11 y ofimática. DDR y DDR2
     *   ya no se consiguen.
     * - Disco: un SSD es la mejora que más se nota; menos de 240 GB se llena
     *   con Windows y las actualizaciones.
     * - Reparaciones: 3 o más en un año indican un equipo que cuesta más
     *   mantener que reemplazar.
     */
    const VALORES_SUGERIDOS = [
        'aplicar_procesador'      => true,
        'aplicar_so'              => true,
        'aplicar_ram'             => true,
        'aplicar_disco'           => true,
        'edad_revisar'            => 5,
        'edad_obsoleto'           => 8,
        'intel_generacion_minima' => 8,
        'ryzen_serie_minima'      => 2,
        'ram_minima_gb'           => 8,
        'ram_tipos_obsoletos'     => ['DDR', 'DDR2'],
        'requiere_ssd'            => true,
        'disco_minimo_gb'         => 240,
        'reparaciones_max_12m'    => 3,
        'so_sin_soporte'          => ['XP', 'Vista', '7', '8', '8.1', '10'],
    ];

    protected $fillable = [
        'categoria_id', 'evaluar', 'aplicar_procesador', 'aplicar_so', 'aplicar_ram', 'aplicar_disco',
        'atributo_procesador_id', 'atributo_ram_id', 'atributo_disco_id', 'atributo_so_id',
        'edad_revisar', 'edad_obsoleto', 'intel_generacion_minima', 'ryzen_serie_minima',
        'ram_minima_gb', 'ram_tipos_obsoletos', 'requiere_ssd', 'disco_minimo_gb', 'reparaciones_max_12m',
        'so_sin_soporte', 'actualizado_por',
    ];

    protected $casts = [
        'evaluar'             => 'boolean',
        'aplicar_procesador'  => 'boolean',
        'aplicar_so'          => 'boolean',
        'aplicar_ram'         => 'boolean',
        'aplicar_disco'       => 'boolean',
        'requiere_ssd'        => 'boolean',
        'ram_tipos_obsoletos' => 'array',
        'so_sin_soporte'      => 'array',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }

    /**
     * Los criterios de la categoría: los guardados, o los sugeridos (sin
     * guardar) si nadie los ha configurado. Por defecto se evalúan las
     * categorías que tienen RAM o disco en su ficha (computadoras y servidores),
     * y cada criterio técnico se enciende solo si la ficha tiene ese dato.
     * Vida útil y reparaciones se aplican siempre.
     */
    public static function paraCategoria(CategoriaEquipo $categoria): self
    {
        $guardado = static::where('categoria_id', $categoria->id)->first();
        if ($guardado) {
            return $guardado;
        }

        $criterio = new static(['categoria_id' => $categoria->id] + self::VALORES_SUGERIDOS);
        $detectados = app(\App\Services\RenovacionService::class)->atributosDe($categoria, $criterio);

        // Cada criterio técnico arranca encendido solo si la ficha tiene ese dato.
        foreach (['procesador', 'so', 'ram', 'disco'] as $dato) {
            $criterio->{'aplicar_' . $dato} = $detectados[$dato] !== null;
        }
        $criterio->evaluar = $detectados['ram'] !== null || $detectados['disco'] !== null;
        $criterio->setRelation('categoria', $categoria);

        return $criterio;
    }
}
