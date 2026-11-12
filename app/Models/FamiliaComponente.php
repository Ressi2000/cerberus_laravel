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
 * Cada campo: {id, nombre, tipo, opciones, requerido}.
 *   - id: UUID estable; es la clave con la que se guardan los valores en
 *     los equipos y en las piezas, así que renombrar un campo no pierde datos.
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

    /** Variantes de esta familia en el almacén (lo que antes era un "componente" suelto). */
    public function componentes(): HasMany
    {
        return $this->hasMany(ComponenteAlmacen::class, 'familia_id');
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
     * Nombre de la variante a partir de los valores de la ficha, ej.
     * "RAM (Capacidad (GB): 8 / Tipo: DDR4)". Es el mismo texto que da
     * AtributoEquipo::describirValor() para una característica de esta
     * familia, así ambas caen en el mismo componente del almacén.
     */
    public function describirValor(array $valor): string
    {
        $campos = collect($this->campos ?? [])->keyBy('id');

        $texto = collect($valor)
            ->map(function ($v, $id) use ($campos) {
                if ($v === null || $v === '') {
                    return null;
                }
                $label = $campos[$id]['nombre'] ?? null;

                return $label ? "{$label}: {$v}" : (string) $v;
            })
            ->filter()
            ->implode(' / ');

        return trim($this->nombre . ($texto !== '' ? " ({$texto})" : ''));
    }

    /**
     * Una característica de equipo que usa esta familia: las piezas que se
     * registran desde el almacén se ligan a ella para poder instalarse
     * después en cualquier categoría que use la familia.
     */
    public function atributoRepresentativo(): ?AtributoEquipo
    {
        return $this->atributos()->orderBy('id')->first();
    }

    /** A granel: solo cantidad, sin piezas individuales. */
    public function esAGranel(): bool
    {
        return $this->control === self::CONTROL_GRANEL;
    }

    /** ¿Tiene ficha técnica? Solo las familias con ficha se pueden usar como característica de un equipo. */
    public function tieneFicha(): bool
    {
        return ! empty($this->campos);
    }

    /**
     * Cuando la familia pasa a "a granel", las piezas que estaban en el
     * almacén en sus componentes pasan a contarse solo por cantidad.
     * Devuelve cuántas piezas se agruparon.
     */
    public function aplicarControl(User $actor): int
    {
        if (! $this->esAGranel()) {
            return 0;
        }

        return $this->componentes()->withTrashed()->get()
            ->sum(fn (ComponenteAlmacen $c) => $c->agruparPiezasEnAlmacen($actor));
    }

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
            ])
            ->values()
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
