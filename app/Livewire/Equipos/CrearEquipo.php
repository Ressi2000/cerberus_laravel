<?php

namespace App\Livewire\Equipos;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\CategoriaEquipo;
use App\Models\EstadoEquipo;
use App\Models\AtributoEquipo;
use App\Models\Equipo;
use App\Models\EquipoAtributoValor;
use Illuminate\Support\Facades\Auth;

class CrearEquipo extends Component
{
    public $empresa_id;
    public $categoria_id    = '';
    public $estado_id       = '';
    public $ubicacion_id    = '';

    public $codigo_interno   = '';
    public $serial           = '';
    public $nombre_maquina   = '';
    public $fecha_adquisicion   = '';
    public $fecha_garantia_fin  = '';
    public $observaciones    = '';

    // IDs + metadatos de atributos (array plano, serializable por Livewire)
    public array $atributos = [];

    // Valores indexados por atributo_id
    public array $valores = [];

    public function mount(): void
    {
        $this->empresa_id = Auth::user()->empresa_id;
        $this->estado_id  = EstadoEquipo::where('nombre', 'Disponible')->value('id') ?? '';
    }

    // Cuando cambia la categoría, recarga atributos
    public function updatedCategoriaId($value): void
    {
        $this->cargarAtributos($value);
        $this->resetValidation();
    }

    private function cargarAtributos($categoriaId): void
    {
        if (!$categoriaId) {
            $this->atributos = [];
            $this->valores   = [];
            return;
        }

        // Convertimos a array plano para que Livewire pueda serializar sin problemas
        $this->atributos = AtributoEquipo::where('categoria_id', $categoriaId)
            ->orderBy('orden')
            ->get()
            ->map(fn($a) => [
                'id'        => $a->id,
                'nombre'    => $a->nombre,
                'tipo'      => $a->tipo,
                'requerido' => $a->requerido,
                'filtrable' => $a->filtrable,
                'opciones'  => $a->opciones ?? [],
            ])
            ->toArray();

        // Inicializar valores vacíos
        $this->valores = [];
        foreach ($this->atributos as $atributo) {
            $this->valores[$atributo['id']] = null;
        }
    }

    private function reglasDinamicas(): array
    {
        $rules = [
            'categoria_id'     => 'required|exists:categorias_equipos,id',
            'codigo_interno'   => 'required|unique:equipos,codigo_interno',
            'estado_id'        => 'required|exists:estados_equipos,id',
            'ubicacion_id'     => 'nullable|exists:ubicaciones,id',
            'serial'           => 'nullable|string|max:255',
            'nombre_maquina'   => 'nullable|string|max:255',
            'fecha_adquisicion'  => 'nullable|date',
            'fecha_garantia_fin' => 'nullable|date|after_or_equal:fecha_adquisicion',
            'observaciones'    => 'nullable|string',
        ];

        foreach ($this->atributos as $atributo) {
            $tipo = match ($atributo['tipo']) {
                'integer' => 'integer',
                'decimal' => 'numeric',
                'boolean' => 'boolean',
                'date'    => 'date',
                'text'    => 'string',
                default   => 'string|max:500',
            };

            $rules["valores.{$atributo['id']}"] = $atributo['requerido']
                ? "required|{$tipo}"
                : "nullable|{$tipo}";
        }

        return $rules;
    }

    private function mensajesDinamicos(): array
    {
        $messages = [];

        foreach ($this->atributos as $atributo) {
            $messages["valores.{$atributo['id']}.required"] =
                "El campo \"{$atributo['nombre']}\" es obligatorio.";
        }

        return $messages;
    }

    public function guardar(): void
    {
        $this->validate($this->reglasDinamicas(), $this->mensajesDinamicos());

        DB::transaction(function () {

            $equipo = Equipo::create([
                'empresa_id'        => $this->empresa_id,
                'categoria_id'      => $this->categoria_id,
                'estado_id'         => $this->estado_id,
                'ubicacion_id'      => $this->ubicacion_id ?: null,
                'codigo_interno'    => $this->codigo_interno ?: Str::uuid(),
                'serial'            => $this->serial ?: null,
                'nombre_maquina'    => $this->nombre_maquina ?: null,
                'fecha_adquisicion' => $this->fecha_adquisicion ?: null,
                'fecha_garantia_fin'=> $this->fecha_garantia_fin ?: null,
                'observaciones'     => $this->observaciones ?: null,
            ]);

            foreach ($this->valores as $atributoId => $valor) {
                if ($valor !== null && $valor !== '') {
                    EquipoAtributoValor::create([
                        'equipo_id'   => $equipo->id,
                        'atributo_id' => $atributoId,
                        'valor'       => $valor,
                        'es_actual'   => true,
                        'creado_por'  => Auth::id(),
                    ]);
                }
            }
        });

        session()->flash('success', 'Equipo registrado correctamente.');

        $this->redirect(route('admin.equipos.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.equipos.crear-equipo', [
            'categorias' => CategoriaEquipo::orderBy('nombre')->pluck('nombre', 'id'),
            'estados'    => EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id'),
            'ubicaciones'=> \App\Models\Ubicacion::where('empresa_id', $this->empresa_id)
                                ->orderBy('nombre')
                                ->pluck('nombre', 'id'),
        ]);
    }
}