<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtributoEquipo extends Model
{
    use Auditable;

    // CRÍTICO: Eloquent inferiría "atributo_equipos" sin esto
    protected $table = 'atributos_equipos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'slug',
        'tipo',
        'requerido',
        'filtrable',
        'visible_en_tabla',
        'orden',
        'opciones', // JSON — solo aplica cuando tipo = 'select'
    ];

    protected $casts = [
        'requerido'        => 'boolean',
        'filtrable'        => 'boolean',
        'visible_en_tabla' => 'boolean',
        'orden'            => 'integer',
        'opciones'         => 'array',
    ];

    // ── Tipos de atributo disponibles ────────────────────────────────────────
    // Fuente única de verdad para validaciones, formularios y vistas.
    // Usar estas constantes en lugar de strings literales dispersos.

    const TIPO_STRING  = 'string';
    const TIPO_INTEGER = 'integer';
    const TIPO_DECIMAL = 'decimal';
    const TIPO_BOOLEAN = 'boolean';
    const TIPO_DATE    = 'date';
    const TIPO_TEXT    = 'text';
    const TIPO_SELECT  = 'select';

    /** Lista completa para select/validación */
    const TIPOS = [
        self::TIPO_STRING  => 'Texto corto',
        self::TIPO_INTEGER => 'Número entero',
        self::TIPO_DECIMAL => 'Número decimal',
        self::TIPO_BOOLEAN => 'Sí / No',
        self::TIPO_DATE    => 'Fecha',
        self::TIPO_TEXT    => 'Texto largo',
        self::TIPO_SELECT  => 'Lista de opciones',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Categoría a la que pertenece este atributo */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }

    /** Todos los valores registrados para este atributo (histórico completo) */
    public function valores(): HasMany
    {
        return $this->hasMany(EquipoAtributoValor::class, 'atributo_id');
    }

    /** Solo los valores vigentes (es_actual = true) */
    public function valoresActuales(): HasMany
    {
        return $this->hasMany(EquipoAtributoValor::class, 'atributo_id')
                    ->where('es_actual', true);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Atributos que aparecen en los filtros de la tabla de equipos */
    public function scopeFiltrables($query)
    {
        return $query->where('filtrable', true);
    }

    /** Atributos que se muestran como columna en el listado de equipos */
    public function scopeVisiblesEnTabla($query)
    {
        return $query->where('visible_en_tabla', true);
    }

    /** Atributos obligatorios al crear/editar un equipo */
    public function scopeRequeridos($query)
    {
        return $query->where('requerido', true);
    }

    /** Atributos de tipo select (tienen opciones JSON) */
    public function scopeDeTipoSelect($query)
    {
        return $query->where('tipo', self::TIPO_SELECT);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** ¿Este atributo es de tipo select? */
    public function esSelect(): bool
    {
        return $this->tipo === self::TIPO_SELECT;
    }

    /** ¿Este atributo es de tipo numérico (integer o decimal)? */
    public function esNumerico(): bool
    {
        return in_array($this->tipo, [self::TIPO_INTEGER, self::TIPO_DECIMAL]);
    }

    /** ¿Este atributo es de tipo boolean? */
    public function esBooleano(): bool
    {
        return $this->tipo === self::TIPO_BOOLEAN;
    }

    /**
     * Retorna la regla de validación Laravel correspondiente al tipo.
     * Usado en CrearEquipo y EditarEquipo para construir las reglas dinámicas.
     */
    public function reglaDeTipo(): string
    {
        return match ($this->tipo) {
            self::TIPO_INTEGER => 'integer',
            self::TIPO_DECIMAL => 'numeric',
            self::TIPO_BOOLEAN => 'boolean',
            self::TIPO_DATE    => 'date',
            self::TIPO_TEXT    => 'string',
            default            => 'string|max:500',
        };
    }

    /**
     * Retorna el label legible del tipo actual.
     * Útil para mostrar en vistas de administración.
     */
    public function labelTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}