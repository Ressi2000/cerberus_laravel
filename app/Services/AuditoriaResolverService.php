<?php

namespace App\Services;

use App\Models\CategoriaEquipo;
use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * AuditoriaResolverService
 *
 * Convierte los valores crudos (IDs) guardados en valores_previos / valores_nuevos
 * de la tabla auditoria en etiquetas legibles, sin tocar los datos originales.
 *
 * Principio: la BD guarda el estado técnico exacto (IDs). Este servicio
 * resuelve la presentación solo al momento de mostrar, nunca al guardar.
 *
 * ── Cómo resuelve cada campo "_id" ──────────────────────────────────────────
 * 1. Si está en $mapa, se usa ese modelo/columna (para casos que no siguen
 *    la convención de abajo — ej. un accessor calculado, o un campo que no
 *    es una FK real de la tabla).
 * 2. Si no, se resuelve SOLO: se lee la foreign key real que la migración
 *    declaró para ese campo (Schema::getForeignKeys) para saber a qué tabla
 *    apunta, y se prueba una lista de columnas típicas ('nombre', 'name',
 *    'codigo_interno'...) para mostrar el valor.
 *
 * Con esto, cualquier tabla o FK nueva (presente o futura) queda cubierta
 * automáticamente sin tocar este archivo — $mapa solo hace falta para las
 * excepciones que se salen de esa convención.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class AuditoriaResolverService
{
    /** Columnas candidatas para mostrar el valor legible, en orden de preferencia. */
    private const COLUMNAS_CANDIDATAS = ['nombre', 'name', 'codigo_interno', 'titulo', 'email', 'username'];

    /** Cache en memoria (por request) de las FK ya leídas, por tabla. */
    private array $fksCache = [];

    /** Cache en memoria (por request) de la columna candidata elegida, por tabla destino. */
    private array $columnaCache = [];

    /**
     * Mapa de resolución manual: tabla → campo_fk → [Modelo, columna_nombre]
     *
     * Si el registro fue eliminado, se mostrará "(eliminado)" en lugar de null.
     */
    protected array $mapa = [

        // ── Equipos ───────────────────────────────────────────────────────────
        'equipos' => [
            'categoria_id'  => [CategoriaEquipo::class, 'nombre'],
            'estado_id'     => [EstadoEquipo::class,    'nombre'],
            'ubicacion_id'  => [Ubicacion::class,       'nombre'],
            'empresa_id'    => [Empresa::class,         'nombre'],
        ],

        // ── Usuarios ──────────────────────────────────────────────────────────
        'users' => [
            'empresa_id'        => [Empresa::class,     'nombre'],
            'empresa_activa_id' => [Empresa::class,     'nombre'],
            'departamento_id'   => [Departamento::class,'nombre'],
            'cargo_id'          => [Cargo::class,       'nombre'],
            'ubicacion_id'      => [Ubicacion::class,   'nombre'],
            'rol_id'            => [Role::class,        'name'],   // Spatie usa 'name'
            'jefe_id'           => [User::class,        'name'],
        ],

        // ── Asignaciones ──────────────────────────────────────────────────────
        'asignaciones' => [
            'empresa_id'  => [Empresa::class,  'nombre'],
            'usuario_id'  => [User::class,     'name'],
            'analista_id' => [User::class,     'name'],
        ],

        // ── Préstamos ─────────────────────────────────────────────────────────
        'prestamos' => [
            'empresa_id'  => [Empresa::class,  'nombre'],
            'usuario_id'  => [User::class,     'name'],
            'analista_id' => [User::class,     'name'],
            'equipo_id'   => [\App\Models\Equipo::class, 'codigo_interno'],
        ],

        // ── Mantenimientos ────────────────────────────────────────────────────
        'mantenimientos' => [
            'empresa_id' => [Empresa::class, 'nombre'],
            'equipo_id'  => [\App\Models\Equipo::class, 'codigo_interno'],
        ],

        // ── Movimientos ───────────────────────────────────────────────────────
        'movimientos' => [
            'empresa_id'           => [Empresa::class,  'nombre'],
            'equipo_id'            => [\App\Models\Equipo::class, 'codigo_interno'],
            'origen_id'            => [Ubicacion::class,'nombre'],
            'destino_id'           => [Ubicacion::class,'nombre'],
            'usuario_responsable_id' => [User::class,   'name'],
        ],

        // ── Software por equipo ───────────────────────────────────────────────
        'software_por_equipo' => [
            'equipo_id'   => [\App\Models\Equipo::class,   'codigo_interno'],
            'software_id' => [\App\Models\Software::class, 'nombre'],
        ],

        // ── Licencias ─────────────────────────────────────────────────────────
        'licencias' => [
            'software_id' => [\App\Models\Software::class, 'nombre'],
        ],
    ];

    /**
     * Etiquetas amigables para los nombres de campo (snake_case → legible)
     */
    protected array $etiquetasCampos = [
        'categoria_id'           => 'Categoría',
        'estado_id'              => 'Estado',
        'ubicacion_id'           => 'Ubicación',
        'empresa_id'             => 'Empresa',
        'empresa_activa_id'      => 'Empresa activa',
        'departamento_id'        => 'Departamento',
        'cargo_id'               => 'Cargo',
        'rol_id'                 => 'Rol',
        'jefe_id'                => 'Jefe directo',
        'usuario_id'             => 'Usuario',
        'analista_id'            => 'Analista',
        'equipo_id'              => 'Equipo',
        'origen_id'              => 'Origen',
        'destino_id'             => 'Destino',
        'usuario_responsable_id' => 'Responsable',
        'software_id'            => 'Software',
        'nombre'                 => 'Nombre',
        'email'                  => 'Correo electrónico',
        'username'               => 'Usuario',
        'estado'                 => 'Estado',
        'activo'                 => 'Activo',
        'codigo_interno'         => 'Código interno',
        'serial'                 => 'Serial',
        'nombre_maquina'         => 'Nombre de máquina',
        'fecha_adquisicion'      => 'Fecha de adquisición',
        'fecha_garantia_fin'     => 'Fin de garantía',
        'observaciones'          => 'Observaciones',
        'telefono'               => 'Teléfono',
        'cedula'                 => 'Cédula',
        'ficha'                  => 'Ficha',
        'fecha_inicio'           => 'Fecha de inicio',
        'fecha_fin_prevista'     => 'Fecha fin prevista',
        'fecha_devolucion'       => 'Fecha de devolución',
        'motivo'                 => 'Motivo',
        'tipo'                   => 'Tipo',
        'costo'                  => 'Costo',
        'tecnico'                => 'Técnico',
        'fecha_ingreso'          => 'Fecha de ingreso',
        'fecha_salida'           => 'Fecha de salida',
        'version'                => 'Versión',
        'proveedor'              => 'Proveedor',
        'cantidad_total'         => 'Cantidad total',
        'cantidad_usada'         => 'Cantidad usada',
        'fecha_vencimiento'      => 'Fecha de vencimiento',
        'clave'                  => 'Clave de licencia',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // API pública
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resuelve un array de valores (de valores_previos o valores_nuevos)
     * para una tabla dada. Devuelve el array original MÁS claves __label
     * para cada FK que pudo resolver.
     *
     * Ejemplo de retorno para tabla 'equipos':
     * [
     *   'categoria_id'       => 3,
     *   'categoria_id__label'=> 'Laptop',
     *   'estado_id'          => 1,
     *   'estado_id__label'   => 'Disponible',
     * ]
     */
    public function resolver(string $tabla, array $valores): array
    {
        $manual = $this->mapa[$tabla] ?? [];

        foreach ($valores as $campo => $id) {
            if (! str_ends_with($campo, '_id') || array_key_exists($campo . '__label', $valores)) {
                continue;
            }

            if ($id === null || $id === '') {
                $valores[$campo . '__label'] = '—';
                continue;
            }

            if (isset($manual[$campo])) {
                [$modelo, $columna] = $manual[$campo];
                $valores[$campo . '__label'] = $this->resolverConModelo($modelo, $columna, $id);
                continue;
            }

            $tablaDestino = $this->tablaDestinoDeLaFk($tabla, $campo);

            if ($tablaDestino) {
                $valores[$campo . '__label'] = $this->resolverConTabla($tablaDestino, $id);
            }
        }

        return $valores;
    }

    private function resolverConModelo(string $modelo, string $columna, mixed $id): string
    {
        try {
            $registro = $modelo::find($id);
            return $registro
                ? ($registro->$columna ?? '(sin nombre)')
                : "(eliminado — ID: {$id})";
        } catch (\Throwable) {
            return "(error al resolver ID: {$id})";
        }
    }

    /** Resuelve un valor leyendo directamente la tabla destino (sin depender de un modelo Eloquent). */
    private function resolverConTabla(string $tabla, mixed $id): string
    {
        $columna = $this->columnaVisibleDe($tabla);

        if (! $columna) {
            return "ID: {$id}";
        }

        try {
            $valor = DB::table($tabla)->where('id', $id)->value($columna);
            return $valor !== null ? (string) $valor : "(eliminado — ID: {$id})";
        } catch (\Throwable) {
            return "(error al resolver ID: {$id})";
        }
    }

    /** A qué tabla apunta un campo, según la foreign key real declarada en la migración. */
    private function tablaDestinoDeLaFk(string $tabla, string $campo): ?string
    {
        foreach ($this->foreignKeysDe($tabla) as $fk) {
            if (in_array($campo, $fk['columns'], true)) {
                return $fk['foreign_table'];
            }
        }

        return null;
    }

    private function foreignKeysDe(string $tabla): array
    {
        if (! array_key_exists($tabla, $this->fksCache)) {
            try {
                $this->fksCache[$tabla] = Schema::getForeignKeys($tabla);
            } catch (\Throwable) {
                $this->fksCache[$tabla] = [];
            }
        }

        return $this->fksCache[$tabla];
    }

    /** Primera columna candidata que realmente existe en la tabla destino. */
    private function columnaVisibleDe(string $tabla): ?string
    {
        if (! array_key_exists($tabla, $this->columnaCache)) {
            $this->columnaCache[$tabla] = collect(self::COLUMNAS_CANDIDATAS)
                ->first(fn($col) => Schema::hasColumn($tabla, $col));
        }

        return $this->columnaCache[$tabla];
    }

    /**
     * Construye la lista de cambios legibles comparando previos y nuevos.
     * Retorna una colección de objetos con: campo, etiqueta, antes, despues.
     *
     * Campos de sistema (timestamps, FK técnicas) se excluyen automáticamente.
     */
    public function cambiosLegibles(string $tabla, ?array $previos, ?array $nuevos): array
    {
        $previos = $previos ? $this->resolver($tabla, $previos) : [];
        $nuevos  = $nuevos  ? $this->resolver($tabla, $nuevos)  : [];

        $excluir = [
            'updated_at', 'created_at', 'deleted_at',
            'remember_token', 'email_verified_at', 'password',
        ];

        $cambios = [];

        // Unimos todas las claves (sin las __label y sin excluidas)
        $claves = collect(array_merge(array_keys($previos), array_keys($nuevos)))
            ->unique()
            ->reject(fn($k) => str_ends_with($k, '__label') || in_array($k, $excluir))
            ->values();

        foreach ($claves as $campo) {
            $valorAntes  = $previos[$campo] ?? null;
            $valorDespues = $nuevos[$campo]  ?? null;

            // Si no cambió, omitir
            if ((string) $valorAntes === (string) $valorDespues) {
                continue;
            }

            // Preferir label legible si existe
            $labelAntes   = $previos[$campo . '__label'] ?? $this->formatearValor($valorAntes);
            $labelDespues = $nuevos[$campo  . '__label']  ?? $this->formatearValor($valorDespues);

            $cambios[] = [
                'campo'   => $campo,
                'etiqueta'=> $this->etiquetasCampos[$campo] ?? $this->humanizarCampo($campo),
                'antes'   => $labelAntes,
                'despues' => $labelDespues,
            ];
        }

        return $cambios;
    }

    /**
     * Devuelve la etiqueta amigable de un campo, o lo humaniza si no está mapeado.
     */
    public function etiquetaCampo(string $campo): string
    {
        return $this->etiquetasCampos[$campo] ?? $this->humanizarCampo($campo);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convierte snake_case en "Texto Legible", quitando el sufijo _id.
     */
    private function humanizarCampo(string $campo): string
    {
        $campo = preg_replace('/_id$/', '', $campo); // quitar _id final
        return ucfirst(str_replace('_', ' ', $campo));
    }

    /**
     * Formatea un valor crudo para mostrarlo en pantalla.
     */
    private function formatearValor(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }
        if (is_bool($valor) || $valor === 1 || $valor === 0 || $valor === '1' || $valor === '0') {
            return filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? 'Sí' : 'No';
        }
        return (string) $valor;
    }
}