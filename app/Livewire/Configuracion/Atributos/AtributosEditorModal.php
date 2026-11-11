<?php

namespace App\Livewire\Configuracion\Atributos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\FamiliaComponente;
use App\Services\VinculacionFamiliaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * AtributosEditorModal
 * ─────────────────────────────────────────────────────────────────────────────
 * Editor en bloque de atributos por categoría.
 *
 * Flujo:
 *   1. Se abre pasando un categoria_id (desde la tabla de categorías o atributos).
 *   2. Carga todos los atributos existentes de esa categoría como filas editables.
 *   3. El usuario puede agregar nuevas filas, editar cualquier campo,
 *      reordenar (campo orden) y marcar filas para eliminar.
 *   4. Al guardar, se ejecuta un upsert inteligente en una sola transacción:
 *       - Filas con id existente → UPDATE
 *       - Filas sin id           → INSERT
 *       - Filas marcadas eliminar con id existente:
 *           · Sin valores en equipos → DELETE
 *           · Con valores            → error toast, se omiten silenciosamente
 *
 * Estructura de cada fila en $filas:
 *   [
 *     'uid'             => string (uuid temporal para wire:key, no va a BD)
 *     'id'              => int|null (null = nuevo)
 *     'nombre'          => string
 *     'tipo'            => string
 *     'requerido'       => bool
 *     'filtrable'       => bool
 *     'visible_en_tabla'=> bool
 *     'orden'           => int
 *     'opciones'        => array   (solo para tipo 'select')
 *     'opciones_raw'    => string  (textarea: una opción por línea, más fácil de editar)
 *     'tiene_valores'   => bool    (si ya tiene registros en equipos — no se puede eliminar)
 *     'eliminar'        => bool    (marcado para eliminar al guardar)
 *   ]
 */
class AtributosEditorModal extends Component
{
    public bool   $open        = false;
    public ?int   $categoriaId = null;
    public string $categoriaNombre = '';

    public array $filas = [];

    public array $tiposDisponibles = [
        'string'  => 'Texto corto',
        'text'    => 'Texto largo',
        'integer' => 'Número entero',
        'decimal' => 'Número decimal',
        'boolean' => 'Sí / No',
        'date'    => 'Fecha',
        'select'  => 'Lista desplegable',
        'file'    => 'Archivo adjunto',
        'group'   => 'Grupo de campos',
    ];

    public const TIPOS_SUB_CAMPO = [
        'string'  => 'Texto corto',
        'integer' => 'Número entero',
        'decimal' => 'Número decimal',
        'boolean' => 'Sí / No',
        'date'    => 'Fecha',
        'select'  => 'Lista desplegable',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Abrir editor — acepta categoria_id desde cualquier evento
    // ─────────────────────────────────────────────────────────────────────────
    #[On('openAtributosEditor')]
    public function abrir(int $categoriaId): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        $categoria = CategoriaEquipo::findOrFail($categoriaId);

        $this->categoriaId     = $categoria->id;
        $this->categoriaNombre = $categoria->nombre;

        // Cargar atributos existentes como filas editables
        $atributos = AtributoEquipo::where('categoria_id', $categoriaId)
            ->with('familia')
            ->orderBy('orden')
            ->get();

        $servicio = app(VinculacionFamiliaService::class);

        $this->filas = $atributos->map(fn($a) => [
            'uid'              => Str::uuid()->toString(),
            'id'               => $a->id,
            'nombre'           => $a->nombre,
            'tipo'             => $a->tipo,
            'requerido'        => (bool) $a->requerido,
            'filtrable'        => (bool) $a->filtrable,
            'visible_en_tabla' => (bool) $a->visible_en_tabla,
            'ver_en_reporte'   => (bool) $a->ver_en_reporte,
            'reutilizable'     => (bool) $a->reutilizable,
            'orden'            => (int) $a->orden,
            'opciones_raw'     => implode("\n", $a->opciones ?? []),
            // Familia cuya ficha usa (null = sub-campos propios).
            'familia_id'       => $a->familia_id,
            'familia_original' => $a->familia_id,
            // Con familia, los sub-campos no se editan aquí (son los de la
            // familia); se cargan los propios guardados por si se desvincula.
            'sub_campos_data'  => collect($a->usaFamilia() ? $servicio->subCamposPropios($a) : ($a->sub_campos ?? []))->map(fn($sc) => [
                'id'           => $sc['id'] ?? Str::uuid()->toString(),
                'nombre'       => $sc['nombre'] ?? '',
                'tipo'         => $sc['tipo'] ?? 'string',
                'opciones_raw' => implode("\n", $sc['opciones'] ?? []),
                'requerido'    => (bool) ($sc['requerido'] ?? false),
            ])->values()->toArray(),
            // Incluye los valores de grupo y las piezas del almacén, no solo
            // los valores simples (antes un grupo con datos figuraba vacío).
            'tiene_valores'    => $a->tieneDatos(),
            'eliminar'         => false,
        ])->values()->toArray();

        // Si no hay atributos, arrancar con una fila vacía
        if (empty($this->filas)) {
            $this->agregarFila();
        }

        $this->resetValidation();
        $this->open = true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Agregar fila vacía al final
    // ─────────────────────────────────────────────────────────────────────────
    public function agregarFila(): void
    {
        $siguienteOrden = collect($this->filas)->max('orden') + 1;

        $this->filas[] = [
            'uid'              => Str::uuid()->toString(),
            'id'               => null,
            'nombre'           => '',
            'tipo'             => 'string',
            'requerido'        => false,
            'filtrable'        => false,
            'visible_en_tabla' => true,
            'ver_en_reporte'   => true,
            'reutilizable'     => false,
            'orden'            => $siguienteOrden,
            'opciones_raw'     => '',
            'familia_id'       => null,
            'familia_original' => null,
            'sub_campos_data'  => [],
            'tiene_valores'    => false,
            'eliminar'         => false,
        ];
    }

    /** Familias de piezas que se pueden usar como ficha. */
    #[Computed]
    public function familias()
    {
        return FamiliaComponente::activas()->orderBy('nombre')->get(['id', 'nombre', 'campos']);
    }

    /**
     * Elegir familia en una fila. Solo para filas sin datos: una
     * característica que ya tiene valores en equipos se vincula con el
     * asistente (VincularFamiliaModal), que convierte esos valores.
     */
    public function updatedFilas($valor, string $clave): void
    {
        if (! str_ends_with($clave, '.familia_id')) {
            return;
        }

        $i = (int) explode('.', $clave)[0];
        $this->filas[$i]['familia_id'] = $valor !== '' && $valor !== null ? (int) $valor : null;

        if ($this->filas[$i]['familia_id']) {
            $this->filas[$i]['tipo']         = 'group';
            $this->filas[$i]['reutilizable'] = true;
        }
    }

    /** Abre el asistente para vincular a una familia una característica que ya tiene datos. */
    public function abrirVinculacion(int $atributoId): void
    {
        $categoriaId = $this->categoriaId;
        $this->close();
        $this->dispatch('openVincularFamilia', atributoId: $atributoId, categoriaId: $categoriaId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Marcar / desmarcar fila para eliminar
    // ─────────────────────────────────────────────────────────────────────────
    public function toggleEliminar(string $uid): void
    {
        foreach ($this->filas as &$fila) {
            if ($fila['uid'] === $uid) {
                // Si es nueva (sin id) la removemos directo
                if ($fila['id'] === null) {
                    $this->filas = array_values(
                        array_filter($this->filas, fn($f) => $f['uid'] !== $uid)
                    );
                    return;
                }
                $fila['eliminar'] = ! $fila['eliminar'];
                return;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sub-campos (tipo group)
    // ─────────────────────────────────────────────────────────────────────────
    public function agregarSubCampo(int $filaIndex): void
    {
        $this->filas[$filaIndex]['sub_campos_data'][] = [
            'id'           => Str::uuid()->toString(),
            'nombre'       => '',
            'tipo'         => 'string',
            'opciones_raw' => '',
            'requerido'    => false,
        ];
    }

    public function eliminarSubCampo(int $filaIndex, string $subCampoId): void
    {
        $this->filas[$filaIndex]['sub_campos_data'] = array_values(
            array_filter(
                $this->filas[$filaIndex]['sub_campos_data'],
                fn($sc) => $sc['id'] !== $subCampoId
            )
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar en bloque — upsert + eliminaciones controladas
    // ─────────────────────────────────────────────────────────────────────────
    public function guardar(): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        // Validar filas activas (no marcadas para eliminar)
        $rules    = [];
        $messages = [];

        foreach ($this->filas as $i => $fila) {
            if ($fila['eliminar']) continue;

            $tipos = implode(',', array_keys(AtributoEquipo::TIPOS));
            $rules["filas.{$i}.nombre"] = 'required|string|max:100';
            $rules["filas.{$i}.tipo"]   = "required|in:{$tipos}";
            $rules["filas.{$i}.orden"]  = 'integer|min:0';

            if ($fila['tipo'] === 'select') {
                $rules["filas.{$i}.opciones_raw"] = 'required|string';
            }

            if ($fila['tipo'] === 'group' && empty($fila['familia_id'])) {
                $tiposSub = implode(',', array_keys(self::TIPOS_SUB_CAMPO));
                $rules["filas.{$i}.sub_campos_data"]              = 'required|array|min:1';
                $rules["filas.{$i}.sub_campos_data.*.nombre"]     = 'required|string|max:100';
                $rules["filas.{$i}.sub_campos_data.*.tipo"]       = "required|in:{$tiposSub}";
                $messages["filas.{$i}.sub_campos_data.required"]  = "La fila " . ($i + 1) . " (Grupo) necesita al menos un sub-campo.";
                $messages["filas.{$i}.sub_campos_data.min"]       = "La fila " . ($i + 1) . " (Grupo) necesita al menos un sub-campo.";
                $messages["filas.{$i}.sub_campos_data.*.nombre.required"] = "Cada sub-campo de la fila " . ($i + 1) . " necesita un nombre.";
            }

            $messages["filas.{$i}.nombre.required"]      = "La fila " . ($i + 1) . " necesita un nombre.";
            $messages["filas.{$i}.opciones_raw.required"] = "La fila " . ($i + 1) . " (Lista) necesita al menos una opción.";
        }

        $this->validate($rules, $messages);

        // Una familia se usa a lo sumo una vez por categoría, y una fila con
        // datos no puede pasar a una familia desde aquí (necesita conversión).
        $familiasUsadas = collect($this->filas)->reject(fn ($f) => $f['eliminar'] || empty($f['familia_id']))->pluck('familia_id');
        if ($familiasUsadas->duplicates()->isNotEmpty()) {
            $this->addError('general', 'Una misma familia no se puede usar en dos características de la misma categoría.');
            return;
        }

        foreach ($this->filas as $i => $fila) {
            if (! $fila['eliminar'] && $fila['id'] && $fila['tiene_valores'] && $fila['familia_id'] && ! $fila['familia_original']) {
                $this->addError("filas.{$i}.familia_id", 'Esta característica ya tiene datos: vincúlala con «Vincular a una familia…», que convierte sus valores.');
                return;
            }
        }

        $omitidos     = 0;
        $eliminados   = 0;
        $creados      = 0;
        $actualizados = 0;

        try {
            DB::transaction(function () use (&$omitidos, &$eliminados, &$creados, &$actualizados) {

                foreach ($this->filas as $fila) {

                    // ── Eliminar ─────────────────────────────────────────────
                    if ($fila['eliminar'] && $fila['id']) {
                        $atributo = AtributoEquipo::find($fila['id']);
                        if (! $atributo) continue;

                        if ($atributo->tieneDatos()) {
                            $omitidos++;
                            continue; // no se puede eliminar, tiene historial
                        }

                        $atributo->delete();
                        $eliminados++;
                        continue;
                    }

                    if ($fila['eliminar']) continue; // nueva marcada eliminar → ignorar

                    // ── Preparar opciones (select) ───────────────────────────
                    $opciones = null;
                    if ($fila['tipo'] === 'select' && trim($fila['opciones_raw']) !== '') {
                        $opciones = array_values(array_filter(
                            array_map('trim', explode("\n", $fila['opciones_raw']))
                        ));
                    }

                    // ── Preparar sub-campos (group) ──────────────────────────
                    $usaFamilia = ! empty($fila['familia_id']);
                    $subCampos  = null;
                    if ($fila['tipo'] === 'group' && ! $usaFamilia) {
                        $subCampos = array_values(array_map(function ($sc) {
                            $opciones = [];
                            if ($sc['tipo'] === 'select') {
                                $opciones = array_values(array_filter(
                                    array_map('trim', explode("\n", $sc['opciones_raw'] ?? ''))
                                ));
                            }
                            return [
                                'id'        => $sc['id'],
                                'nombre'    => trim($sc['nombre']),
                                'tipo'      => $sc['tipo'],
                                'opciones'  => $opciones,
                                'requerido' => (bool) $sc['requerido'],
                            ];
                        }, $fila['sub_campos_data'] ?? []));
                    }

                    $data = [
                        'categoria_id'     => $this->categoriaId,
                        'familia_id'       => $usaFamilia ? (int) $fila['familia_id'] : null,
                        'nombre'           => trim($fila['nombre']),
                        'slug'             => Str::slug(trim($fila['nombre'])),
                        'tipo'             => $usaFamilia ? 'group' : $fila['tipo'],
                        'requerido'        => $fila['requerido'],
                        'filtrable'        => $fila['filtrable'],
                        'visible_en_tabla' => $fila['visible_en_tabla'],
                        'ver_en_reporte'   => $fila['ver_en_reporte'],
                        // Una pieza de familia siempre se puede rescatar.
                        'reutilizable'     => $usaFamilia ? true : ($fila['reutilizable'] ?? false),
                        'orden'            => (int) $fila['orden'],
                        'opciones'         => $opciones,
                    ];

                    // Con familia, los sub-campos propios no se tocan: quedan de
                    // respaldo en la columna y la ficha sale de la familia.
                    if (! $usaFamilia) {
                        $data['sub_campos'] = $subCampos;
                    }

                    // ── Actualizar o crear ───────────────────────────────────
                    if ($fila['id']) {
                        $atributo = AtributoEquipo::findOrFail($fila['id']);

                        // Deja de usar la familia: se copian sus campos a la
                        // característica para que los datos sigan siendo válidos.
                        if ($fila['familia_original'] && ! $usaFamilia) {
                            app(VinculacionFamiliaService::class)->desvincular($atributo);
                            unset($data['sub_campos']);
                        }

                        $atributo->update($data);
                        $actualizados++;
                    } else {
                        AtributoEquipo::create($data);
                        $creados++;
                    }
                }
            });

            // Mensaje de resumen
            $partes = [];
            if ($creados)      $partes[] = "{$creados} creado(s)";
            if ($actualizados) $partes[] = "{$actualizados} actualizado(s)";
            if ($eliminados)   $partes[] = "{$eliminados} eliminado(s)";
            if ($omitidos)     $partes[] = "{$omitidos} omitido(s) por tener valores en equipos";

            $resumen = implode(', ', $partes) ?: 'Sin cambios';

            $this->close();
            $this->dispatch('atributoGuardado');
            $this->dispatch(
                'toast',
                type: 'success',
                message: "Atributos de «{$this->categoriaNombre}» guardados. {$resumen}."
            );

            if ($omitidos > 0) {
                $this->dispatch(
                    'toast',
                    type: 'warning',
                    message: "{$omitidos} atributo(s) no se eliminaron porque ya tienen valores registrados en equipos."
                );
            }
        } catch (\Exception $e) {
            Log::error('AtributosEditorModal@guardar: ' . $e->getMessage());
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Ocurrió un error al guardar los atributos.'
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ajustar propiedades según el tipo seleccionado
    // ─────────────────────────────────────────────────────────────────────────
    public function ajustarPorTipo(int $index): void
    {
        $tipo = $this->filas[$index]['tipo'];

        if ($tipo === 'file') {
            $this->filas[$index]['filtrable']        = false;
            $this->filas[$index]['visible_en_tabla'] = false;
            $this->filas[$index]['ver_en_reporte']   = false;
            $this->filas[$index]['reutilizable']     = false;
        }

        // Limpiar sub_campos_data si cambian a otro tipo
        if ($tipo !== 'group') {
            $this->filas[$index]['sub_campos_data'] = [];
        }
    }

    public function close(): void
    {
        $this->open            = false;
        $this->categoriaId     = null;
        $this->categoriaNombre = '';
        $this->filas           = [];
        $this->resetValidation();
    }
    
    public function render()
    {
        return view('livewire.configuracion.atributos.atributos-editor-modal', [
            'tiposSub' => self::TIPOS_SUB_CAMPO,
        ]);
    }
}
