<?php

namespace App\Livewire\Configuracion\Atributos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Abrir editor — acepta categoria_id desde cualquier evento
    // ─────────────────────────────────────────────────────────────────────────
    #[On('openAtributosEditor')]
    public function abrir(int $categoriaId): void
    {
        $categoria = CategoriaEquipo::findOrFail($categoriaId);

        $this->categoriaId     = $categoria->id;
        $this->categoriaNombre = $categoria->nombre;

        // Cargar atributos existentes como filas editables
        $atributos = AtributoEquipo::where('categoria_id', $categoriaId)
            ->withCount('valores')
            ->orderBy('orden')
            ->get();

        $this->filas = $atributos->map(fn($a) => [
            'uid'              => Str::uuid()->toString(),
            'id'               => $a->id,
            'nombre'           => $a->nombre,
            'tipo'             => $a->tipo,
            'requerido'        => (bool) $a->requerido,
            'filtrable'        => (bool) $a->filtrable,
            'visible_en_tabla' => (bool) $a->visible_en_tabla,
            'orden'            => (int) $a->orden,
            'opciones_raw'     => implode("\n", $a->opciones ?? []),
            'tiene_valores'    => $a->valores_count > 0,
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
            'orden'            => $siguienteOrden,
            'opciones_raw'     => '',
            'tiene_valores'    => false,
            'eliminar'         => false,
        ];
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
    // Guardar en bloque — upsert + eliminaciones controladas
    // ─────────────────────────────────────────────────────────────────────────
    public function guardar(): void
    {
        // Validar filas activas (no marcadas para eliminar)
        $rules    = [];
        $messages = [];

        foreach ($this->filas as $i => $fila) {
            if ($fila['eliminar']) continue;

            $rules["filas.{$i}.nombre"] = 'required|string|max:100';
            $rules["filas.{$i}.tipo"]   = 'required|in:string,text,integer,decimal,boolean,date,select';
            $rules["filas.{$i}.orden"]  = 'integer|min:0';

            if ($fila['tipo'] === 'select') {
                $rules["filas.{$i}.opciones_raw"] = 'required|string';
            }

            $messages["filas.{$i}.nombre.required"]      = "La fila " . ($i + 1) . " necesita un nombre.";
            $messages["filas.{$i}.opciones_raw.required"] = "La fila " . ($i + 1) . " (Lista) necesita al menos una opción.";
        }

        $this->validate($rules, $messages);

        $omitidos     = 0;
        $eliminados   = 0;
        $creados      = 0;
        $actualizados = 0;

        try {
            DB::transaction(function () use (&$omitidos, &$eliminados, &$creados, &$actualizados) {

                foreach ($this->filas as $fila) {

                    // ── Eliminar ─────────────────────────────────────────────
                    if ($fila['eliminar'] && $fila['id']) {
                        $atributo = AtributoEquipo::withCount('valores')->find($fila['id']);
                        if (! $atributo) continue;

                        if ($atributo->valores_count > 0) {
                            $omitidos++;
                            continue; // no se puede eliminar, tiene historial
                        }

                        $atributo->delete();
                        $eliminados++;
                        continue;
                    }

                    if ($fila['eliminar']) continue; // nueva marcada eliminar → ignorar

                    // ── Preparar opciones ────────────────────────────────────
                    $opciones = null;
                    if ($fila['tipo'] === 'select' && trim($fila['opciones_raw']) !== '') {
                        $opciones = array_values(array_filter(
                            array_map('trim', explode("\n", $fila['opciones_raw']))
                        ));
                    }

                    $data = [
                        'categoria_id'     => $this->categoriaId,
                        'nombre'           => trim($fila['nombre']),
                        'slug'             => Str::slug(trim($fila['nombre'])),
                        'tipo'             => $fila['tipo'],
                        'requerido'        => $fila['requerido'],
                        'filtrable'        => $fila['filtrable'],
                        'visible_en_tabla' => $fila['visible_en_tabla'],
                        'orden'            => (int) $fila['orden'],
                        'opciones'         => $opciones,
                    ];

                    // ── Actualizar o crear ───────────────────────────────────
                    if ($fila['id']) {
                        AtributoEquipo::where('id', $fila['id'])->update($data);
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
        return view('livewire.configuracion.atributos.atributos-editor-modal');
    }
}
