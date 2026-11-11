<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Familia de piezas (RAM, Discos, Procesador…).
 *
 * Es dueña de la FICHA TÉCNICA de la pieza: los campos se definen una sola
 * vez aquí y los comparten todas las categorías cuya característica "usa"
 * esta familia (AtributoEquipo::familia_id). Así una RAM de una Laptop y
 * otra de un Desktop se describen con los mismos campos y se pueden comparar.
 *
 * Cada campo: {id, nombre, tipo, opciones, requerido, debe_coincidir}.
 *   - id: UUID estable; es la clave con la que se guardan los valores en
 *     los equipos y en las piezas, así que renombrar un campo no pierde datos.
 *   - debe_coincidir: el campo tiene que ser igual entre la pieza y el
 *     equipo para que la pieza encaje físicamente (ej. Tipo DDR4, Formato SO-DIMM).
 */
class FamiliaComponente extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'familias_componentes';

    const CONTROL_UNIDAD = 'unidad';
    const CONTROL_GRANEL = 'granel';

    const CONTROLES = [
        self::CONTROL_UNIDAD => 'Por unidad (cada pieza con su serial)',
        self::CONTROL_GRANEL => 'A granel (solo cantidad)',
    ];

    /** Tipos de campo que admite una ficha. */
    const TIPOS_CAMPO = [
        'string'  => 'Texto corto',
        'integer' => 'Número entero',
        'decimal' => 'Número decimal',
        'select'  => 'Lista de opciones',
    ];

    protected $fillable = [
        'nombre',
        'descripcion',
        'control',
        'campos',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'campos' => 'array',
        'activo' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Características de cada categoría que usan esta ficha. */
    public function atributos(): HasMany
    {
        return $this->hasMany(AtributoEquipo::class, 'familia_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Los campos con la forma de AtributoEquipo::sub_campos, que es lo que
     * leen los formularios, la tabla y la ficha de equipos.
     */
    public function camposComoSubCampos(): array
    {
        return collect($this->campos ?? [])
            ->map(fn ($c) => [
                'id'             => $c['id'],
                'nombre'         => $c['nombre'],
                'tipo'           => $c['tipo'] ?? 'string',
                'opciones'       => $c['opciones'] ?? [],
                'requerido'      => (bool) ($c['requerido'] ?? false),
                'debe_coincidir' => (bool) ($c['debe_coincidir'] ?? false),
            ])
            ->values()
            ->all();
    }

    /** IDs de los campos que deben coincidir para que una pieza encaje. */
    public function camposQueDebenCoincidir(): array
    {
        return collect($this->campos ?? [])
            ->filter(fn ($c) => $c['debe_coincidir'] ?? false)
            ->pluck('id')
            ->all();
    }

    /** ¿Algún equipo o pieza ya tiene valores guardados con esta ficha? */
    public function tieneDatos(): bool
    {
        $atributos = $this->atributos()->pluck('id');

        return $atributos->isNotEmpty() && (
            EquipoAtributoGrupoInstancia::whereIn('atributo_id', $atributos)->exists()
            || PiezaExtraida::whereIn('atributo_id', $atributos)->exists()
        );
    }
}
