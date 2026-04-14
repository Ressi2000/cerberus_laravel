<?php

namespace App\Services;

use App\Models\CategoriaEquipo;
use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Models\User;
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
 * ── Cómo agregar nuevas tablas / campos ─────────────────────────────────────
 * 1. Agrega la tabla a $mapa con sus campos FK.
 * 2. Cada campo apunta a [ModelClass, 'columna_nombre'].
 * 3. El Service automáticamente agrega una clave campo_id__label con el texto.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class AuditoriaResolverService
{
    /**
     * Mapa de resolución: tabla → campo_fk → [Modelo, columna_nombre]
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
        $campos = $this->mapa[$tabla] ?? [];

        foreach ($campos as $campo => [$modelo, $columna]) {
            if (! array_key_exists($campo, $valores)) {
                continue;
            }

            $id = $valores[$campo];

            if ($id === null || $id === '') {
                $valores[$campo . '__label'] = '—';
                continue;
            }

            try {
                $registro = $modelo::find($id);
                $valores[$campo . '__label'] = $registro
                    ? ($registro->$columna ?? "(sin nombre)")
                    : "(eliminado — ID: {$id})";
            } catch (\Throwable) {
                $valores[$campo . '__label'] = "(error al resolver ID: {$id})";
            }
        }

        return $valores;
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