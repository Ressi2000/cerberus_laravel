<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'familia_id',
        'nombre',
        'slug',
        'tipo',
        'requerido',
        'filtrable',
        'visible_en_tabla',
        'ver_en_reporte',
        'reutilizable',
        'orden',
        'opciones',    // JSON — solo aplica cuando tipo = 'select'
        'sub_campos',  // JSON — solo aplica cuando tipo = 'group'
    ];

    protected $casts = [
        'requerido'        => 'boolean',
        'filtrable'        => 'boolean',
        'visible_en_tabla' => 'boolean',
        'ver_en_reporte'   => 'boolean',
        'reutilizable'     => 'boolean',
        'orden'            => 'integer',
        'opciones'         => 'array',
        // sub_campos NO va en casts: lo maneja el accessor subCampos(), que
        // devuelve la ficha de la familia cuando la característica usa una.
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
    const TIPO_FILE    = 'file';
    const TIPO_GROUP   = 'group'; // ← Grupo de sub-campos multi-instancia (ej: discos, RAM sticks)

    /** Lista completa para select/validación */
    const TIPOS = [
        self::TIPO_STRING  => 'Texto corto',
        self::TIPO_INTEGER => 'Número entero',
        self::TIPO_DECIMAL => 'Número decimal',
        self::TIPO_BOOLEAN => 'Sí / No',
        self::TIPO_DATE    => 'Fecha',
        self::TIPO_TEXT    => 'Texto largo',
        self::TIPO_SELECT  => 'Lista de opciones',
        self::TIPO_FILE    => 'Archivo adjunto',
        self::TIPO_GROUP   => 'Grupo de campos',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Categoría a la que pertenece este atributo */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }

    /**
     * Familia de piezas cuya ficha técnica usa esta característica (ej. la
     * "RAM" de Laptop y la de Desktop usan la familia RAM). Null si la
     * característica define sus propios sub-campos.
     */
    public function familia(): BelongsTo
    {
        return $this->belongsTo(FamiliaComponente::class, 'familia_id');
    }

    /**
     * Sub-campos del grupo. Si la característica usa una familia, son los
     * campos de la familia (los mismos para todas las categorías); si no,
     * los propios guardados en la columna sub_campos.
     *
     * Todo el código que ya lee $atributo->sub_campos (formularios, tabla y
     * ficha de equipos, exportaciones, almacén) recibe así la ficha correcta
     * sin cambios. La columna conserva los sub-campos originales por si se
     * desvincula la familia.
     */
    protected function subCampos(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->familia_id && $this->familia) {
                    return $this->familia->camposComoSubCampos();
                }

                if ($value === null || is_array($value)) {
                    return $value;
                }

                return json_decode($value, true);
            },
            set: fn ($value) => $value === null ? null : (is_string($value) ? $value : json_encode($value)),
        );
    }

    /** ¿Esta característica toma su ficha de una familia de piezas? */
    public function usaFamilia(): bool
    {
        return $this->familia_id !== null;
    }

    /**
     * ¿Algún equipo o pieza tiene datos guardados con esta característica?
     * Incluye los valores de grupo (instancias) y las piezas del almacén,
     * no solo los valores simples.
     */
    public function tieneDatos(): bool
    {
        return $this->valores()->exists()
            || EquipoAtributoGrupoInstancia::where('atributo_id', $this->id)->exists()
            || $this->piezasExtraidas()->exists();
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

    /** Instancias multi-valor vigentes (solo aplica cuando tipo = 'group') */
    public function instanciasGrupo(): HasMany
    {
        return $this->hasMany(EquipoAtributoGrupoInstancia::class, 'atributo_id')
                    ->where('es_actual', true)
                    ->orderBy('orden');
    }

    /** Piezas extraídas de este tipo (ej: todas las RAM extraídas de cualquier equipo). */
    public function piezasExtraidas(): HasMany
    {
        return $this->hasMany(PiezaExtraida::class, 'atributo_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Atributos que aparecen en los filtros de la tabla de equipos */
    public function scopeFiltrables($query)
    {
        // Los atributos tipo 'file' NUNCA son filtrables (no tiene sentido buscar por path).
        // Los tipo 'group' sí pueden marcarse filtrables: se filtran por sus sub-campos,
        // contra equipo_atributo_grupo_instancias (ver EquiposTable::render()).
        return $query->where('filtrable', true)
            ->where('tipo', '!=', self::TIPO_FILE);
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

    /** Atributos que se incluyen en las planillas/reportes descargables */
    public function scopeVerEnReporte($query)
    {
        return $query->where('ver_en_reporte', true);
    }

    /** Atributos de tipo select (tienen opciones JSON) */
    public function scopeDeTipoSelect($query)
    {
        return $query->where('tipo', self::TIPO_SELECT);
    }

    /** Atributos que se pueden rescatar como pieza (Almacén/Depósito) al extraerlos de un equipo. */
    public function scopeReutilizables($query)
    {
        return $query->where('reutilizable', true);
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

    /** ¿Este atributo es de tipo file? */
    public function esFile(): bool
    {
        return $this->tipo === self::TIPO_FILE;
    }

    /** ¿Este atributo es un grupo de sub-campos multi-instancia? */
    public function esGrupo(): bool
    {
        return $this->tipo === self::TIPO_GROUP;
    }

    /**
     * Retorna la regla de validación Laravel correspondiente al tipo.
     * Usado en CrearEquipo y EditarEquipo para construir las reglas dinámicas.
     *
     * NOTA: Para tipo 'file' la validación del objeto UploadedFile se maneja
     * por separado en los componentes Livewire (array $archivos[]).
     * Esta regla cubre solo el campo $valores[] que guarda el path.
     */
    public function reglaDeTipo(): string
    {
        return match ($this->tipo) {
            self::TIPO_INTEGER => 'integer',
            self::TIPO_DECIMAL => 'numeric',
            self::TIPO_BOOLEAN => 'boolean',
            self::TIPO_DATE    => 'date',
            self::TIPO_TEXT    => 'string',
            self::TIPO_FILE    => 'nullable|string', // el path se asigna post-upload
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

    /**
     * Describe en texto un valor "crudo" de este atributo — el mismo shape
     * que usa PiezaExtraida::valor_extraido: ['valor' => ...] para un
     * atributo simple, o [sub_campo_id => valor, ...] para uno tipo 'group'.
     * Usado para armar el nombre del bucket de stock al rescatar una pieza
     * y para mostrar su ficha de trazabilidad.
     */
    public function describirValor(array $valor): string
    {
        if ($this->esGrupo()) {
            $subCampos = collect($this->sub_campos ?? [])->keyBy('id');
            $texto = collect($valor)
                ->map(function ($v, $subCampoId) use ($subCampos) {
                    if ($v === null || $v === '') {
                        return null;
                    }
                    $label = $subCampos[$subCampoId]['nombre'] ?? null;

                    return $label ? "{$label}: {$v}" : (string) $v;
                })
                ->filter()
                ->implode(' / ');
        } else {
            $texto = (string) ($valor['valor'] ?? '');
        }

        // Con familia, el nombre es el de la familia: así la misma RAM se
        // describe igual venga de una Laptop o de un Desktop (y cae en el
        // mismo componente del almacén).
        $nombre = $this->usaFamilia() && $this->familia ? $this->familia->nombre : $this->nombre;

        return trim($nombre . ($texto !== '' ? " ({$texto})" : ''));
    }
}