<?php

namespace App\Livewire\Configuracion\Atributos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class AtributoModal extends Component
{
    public bool   $open             = false;
    public ?int   $atributoId       = null;
    public string $categoria_id     = '';
    public string $nombre           = '';
    public string $tipo             = 'string';
    public bool   $requerido        = false;
    public bool   $filtrable        = false;
    public bool   $visible_en_tabla = false;
    public int    $orden            = 0;
    public array  $opciones         = [];   // [['id'=>uuid, 'valor'=>'...'], ...] — solo tipo 'select'
    public array  $subCampos        = [];   // solo tipo 'group'

    // Tipos disponibles para el sub-campo (sin 'group' ni 'file' — no se anidan ni se suben archivos)
    public const TIPOS_SUB_CAMPO = [
        'string'  => 'Texto corto',
        'integer' => 'Número entero',
        'decimal' => 'Número decimal',
        'boolean' => 'Sí / No',
        'date'    => 'Fecha',
        'select'  => 'Lista desplegable',
    ];

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

    #[On('openAtributoCrear')]
    public function abrirCrear(?int $categoriaId = null): void
    {
        $this->reset([
            'atributoId', 'nombre', 'requerido', 'filtrable',
            'visible_en_tabla', 'opciones', 'subCampos',
        ]);
        $this->tipo         = 'string';
        $this->orden        = 0;
        $this->categoria_id = $categoriaId ? (string) $categoriaId : '';
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openAtributoEditar')]
    public function abrirEditar(int $id): void
    {
        $a = AtributoEquipo::findOrFail($id);
        $this->atributoId       = $a->id;
        $this->categoria_id     = (string) $a->categoria_id;
        $this->nombre           = $a->nombre;
        $this->tipo             = $a->tipo;
        $this->requerido        = (bool) $a->requerido;
        $this->filtrable        = (bool) $a->filtrable;
        $this->visible_en_tabla = (bool) $a->visible_en_tabla;
        $this->orden            = (int) $a->orden;

        // Opciones del tipo 'select'
        $this->opciones = collect($a->opciones ?? [])
            ->map(fn($v) => ['id' => Str::uuid()->toString(), 'valor' => $v])
            ->values()->toArray();

        // Sub-campos del tipo 'group'
        $this->subCampos = collect($a->sub_campos ?? [])
            ->map(fn($sc) => [
                'id'           => $sc['id'] ?? Str::uuid()->toString(),
                'nombre'       => $sc['nombre'] ?? '',
                'tipo'         => $sc['tipo'] ?? 'string',
                // opciones de sub-campo: ['HDD','SSD'] → textarea con saltos de línea
                'opciones_raw' => implode("\n", $sc['opciones'] ?? []),
                'requerido'    => (bool) ($sc['requerido'] ?? false),
            ])
            ->values()->toArray();

        $this->resetValidation();
        $this->open = true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Opciones (tipo select)
    // ─────────────────────────────────────────────────────────────────────────

    public function updatedTipo(): void
    {
        if ($this->tipo !== 'select') $this->opciones  = [];
        if ($this->tipo !== 'group')  $this->subCampos = [];
    }

    public function agregarOpcion(): void
    {
        $this->opciones[] = ['id' => Str::uuid()->toString(), 'valor' => ''];
    }

    public function eliminarOpcion(string $id): void
    {
        $this->opciones = array_values(
            array_filter($this->opciones, fn($o) => $o['id'] !== $id)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sub-campos (tipo group)
    // ─────────────────────────────────────────────────────────────────────────

    public function agregarSubCampo(): void
    {
        $this->subCampos[] = [
            'id'           => Str::uuid()->toString(),
            'nombre'       => '',
            'tipo'         => 'string',
            'opciones_raw' => '',
            'requerido'    => false,
        ];
    }

    public function eliminarSubCampo(string $id): void
    {
        $this->subCampos = array_values(
            array_filter($this->subCampos, fn($sc) => $sc['id'] !== $id)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación
    // ─────────────────────────────────────────────────────────────────────────

    protected function rules(): array
    {
        $tipos = implode(',', array_keys(AtributoEquipo::TIPOS));
        $rules = [
            'categoria_id'     => 'required|exists:categorias_equipos,id',
            'nombre'           => 'required|string|max:100',
            'tipo'             => "required|in:{$tipos}",
            'requerido'        => 'boolean',
            'filtrable'        => 'boolean',
            'visible_en_tabla' => 'boolean',
            'orden'            => 'integer|min:0',
        ];

        if ($this->tipo === 'select') {
            $rules['opciones']         = 'required|array|min:1';
            $rules['opciones.*.valor'] = 'required|string|max:100';
        }

        if ($this->tipo === 'group') {
            $tiposSub = implode(',', array_keys(self::TIPOS_SUB_CAMPO));
            $rules['subCampos']               = 'required|array|min:1';
            $rules['subCampos.*.nombre']      = 'required|string|max:100';
            $rules['subCampos.*.tipo']        = "required|in:{$tiposSub}";
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'categoria_id.required'          => 'Debe seleccionar una categoría.',
            'nombre.required'                => 'El nombre es obligatorio.',
            'tipo.required'                  => 'Seleccione un tipo de dato.',
            'opciones.required'              => 'Agregue al menos una opción.',
            'opciones.*.valor.required'      => 'Cada opción debe tener un valor.',
            'subCampos.required'             => 'Agregue al menos un sub-campo.',
            'subCampos.min'                  => 'El grupo debe tener al menos un sub-campo.',
            'subCampos.*.nombre.required'    => 'Cada sub-campo debe tener un nombre.',
            'subCampos.*.tipo.required'      => 'Seleccione el tipo para cada sub-campo.',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar
    // ─────────────────────────────────────────────────────────────────────────

    public function guardar(): void
    {
        $this->validate();

        $opcionesJson = null;
        $subCamposJson = null;

        if ($this->tipo === 'select') {
            $opcionesJson = array_values(array_map(fn($o) => trim($o['valor']), $this->opciones));
        }

        if ($this->tipo === 'group') {
            $subCamposJson = array_values(array_map(function ($sc) {
                $opciones = [];
                if ($sc['tipo'] === 'select') {
                    $opciones = array_values(array_filter(
                        array_map('trim', explode("\n", $sc['opciones_raw'] ?? ''))
                    ));
                }
                return [
                    'id'       => $sc['id'],
                    'nombre'   => trim($sc['nombre']),
                    'tipo'     => $sc['tipo'],
                    'opciones' => $opciones,
                    'requerido'=> (bool) $sc['requerido'],
                ];
            }, $this->subCampos));
        }

        try {
            $data = [
                'categoria_id'     => $this->categoria_id,
                'nombre'           => $this->nombre,
                'slug'             => Str::slug($this->nombre),
                'tipo'             => $this->tipo,
                'requerido'        => $this->requerido,
                'filtrable'        => $this->filtrable,
                'visible_en_tabla' => $this->visible_en_tabla,
                'orden'            => $this->orden,
                'opciones'         => $opcionesJson,
                'sub_campos'       => $subCamposJson,
            ];

            if ($this->atributoId) {
                $atributo = AtributoEquipo::findOrFail($this->atributoId);
                if (
                    (int)$atributo->categoria_id !== (int)$this->categoria_id
                    && $atributo->valores()->exists()
                ) {
                    $this->addError(
                        'categoria_id',
                        'No se puede cambiar la categoría: el atributo ya tiene valores en equipos.'
                    );
                    return;
                }
                $atributo->update($data);
                $msg = "Atributo «{$this->nombre}» actualizado.";
            } else {
                AtributoEquipo::create($data);
                $msg = "Atributo «{$this->nombre}» creado.";
            }

            $this->close();
            $this->dispatch('atributoGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('AtributoModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el atributo.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset([
            'atributoId', 'categoria_id', 'nombre', 'tipo',
            'requerido', 'filtrable', 'visible_en_tabla', 'orden',
            'opciones', 'subCampos',
        ]);
        $this->tipo = 'string';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.atributos.atributo-modal', [
            'categorias'    => CategoriaEquipo::activos()->orderBy('nombre')->pluck('nombre', 'id'),
            'tiposSub'      => self::TIPOS_SUB_CAMPO,
        ]);
    }
}
