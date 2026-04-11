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
    public array  $opciones         = [];   // [['id'=>uuid, 'valor'=>'...'], ...]

    public array $tiposDisponibles = [
        'string'  => 'Texto corto',
        'text'    => 'Texto largo',
        'integer' => 'Número entero',
        'decimal' => 'Número decimal',
        'boolean' => 'Sí / No',
        'date'    => 'Fecha',
        'select'  => 'Lista desplegable',
    ];

    #[On('openAtributoCrear')]
    public function abrirCrear(?int $categoriaId = null): void
    {
        $this->reset([
            'atributoId',
            'nombre',
            'requerido',
            'filtrable',
            'visible_en_tabla',
            'opciones'
        ]);
        $this->tipo        = 'string';
        $this->orden       = 0;
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
        $this->opciones = collect($a->opciones ?? [])
            ->map(fn($v) => ['id' => Str::uuid()->toString(), 'valor' => $v])
            ->values()->toArray();
        $this->resetValidation();
        $this->open = true;
    }

    public function updatedTipo(): void
    {
        if ($this->tipo !== 'select') $this->opciones = [];
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

    protected function rules(): array
    {
        $rules = [
            'categoria_id'     => 'required|exists:categorias_equipos,id',
            'nombre'           => 'required|string|max:100',
            'tipo'             => 'required|in:string,text,integer,decimal,boolean,date,select',
            'requerido'        => 'boolean',
            'filtrable'        => 'boolean',
            'visible_en_tabla' => 'boolean',
            'orden'            => 'integer|min:0',
        ];
        if ($this->tipo === 'select') {
            $rules['opciones']          = 'required|array|min:1';
            $rules['opciones.*.valor']  = 'required|string|max:100';
        }
        return $rules;
    }

    protected function messages(): array
    {
        return [
            'categoria_id.required'     => 'Debe seleccionar una categoría.',
            'nombre.required'           => 'El nombre es obligatorio.',
            'tipo.required'             => 'Seleccione un tipo de dato.',
            'opciones.required'         => 'Agregue al menos una opción.',
            'opciones.*.valor.required' => 'Cada opción debe tener un valor.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        $opcionesJson = $this->tipo === 'select'
            ? array_values(array_map(fn($o) => trim($o['valor']), $this->opciones))
            : null;

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
            ];

            if ($this->atributoId) {
                $atributo = AtributoEquipo::findOrFail($this->atributoId);
                // Proteger cambio de categoría si ya tiene valores
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
            'atributoId',
            'categoria_id',
            'nombre',
            'tipo',
            'requerido',
            'filtrable',
            'visible_en_tabla',
            'orden',
            'opciones'
        ]);
        $this->tipo = 'string';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.atributos.atributo-modal', [
            'categorias' => CategoriaEquipo::activos()->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
