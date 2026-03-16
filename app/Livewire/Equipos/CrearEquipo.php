<?php

namespace App\Livewire\Equipos;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\CategoriaEquipo;
use App\Models\EstadoEquipo;
use App\Models\AtributoEquipo;
use App\Models\EquipoAtributoValor;
use App\Models\Equipos;
use Illuminate\Support\Facades\Auth;

class CrearEquipo extends Component
{
    public $empresa_id;
    public $categoria_id;
    public $estado_id;
    public $ubicacion_id;

    public $codigo_interno;
    public $serial;
    public $nombre_maquina;
    public $fecha_adquisicion;
    public $fecha_garantia_fin;
    public $observaciones;

    public $atributos = [];
    public $valores = [];

    public function mount()
    {
        $this->empresa_id = session('empresa_activa_id');
        $this->estado_id = EstadoEquipo::where('nombre', 'Disponible')->first()?->id;
    }

    public function updatedCategoriaId($value)
    {
        $this->cargarAtributos($value);
    }

    private function cargarAtributos($categoriaId)
    {
        $this->atributos = AtributoEquipo::where('categoria_id', $categoriaId)
            ->orderBy('orden')
            ->get();

        $this->valores = [];

        foreach ($this->atributos as $atributo) {
            $this->valores[$atributo->id] = null;
        }
    }

    private function reglasDinamicas()
    {
        $rules = [
            'categoria_id' => 'required|exists:categorias_equipos,id',
            'codigo_interno' => 'required|unique:equipos,codigo_interno',
            'estado_id' => 'required|exists:estados_equipos,id',
        ];

        foreach ($this->atributos as $atributo) {

            $rule = match ($atributo->tipo) {
                'integer' => 'integer',
                'decimal' => 'numeric',
                'boolean' => 'boolean',
                'date' => 'date',
                default => 'string',
            };

            if ($atributo->requerido) {
                $rule = 'required|' . $rule;
            } else {
                $rule = 'nullable|' . $rule;
            }

            $rules["valores.{$atributo->id}"] = $rule;
        }

        return $rules;
    }

    public function guardar()
    {
        $this->validate($this->reglasDinamicas());

        DB::transaction(function () {

            $equipo = Equipos::create([
                'empresa_id' => $this->empresa_id,
                'categoria_id' => $this->categoria_id,
                'estado_id' => $this->estado_id,
                'ubicacion_id' => $this->ubicacion_id,
                'codigo_interno' => $this->codigo_interno ?? Str::uuid(),
                'serial' => $this->serial,
                'nombre_maquina' => $this->nombre_maquina,
                'fecha_adquisicion' => $this->fecha_adquisicion,
                'fecha_garantia_fin' => $this->fecha_garantia_fin,
                'observaciones' => $this->observaciones,
            ]);

            foreach ($this->valores as $atributoId => $valor) {

                if ($valor !== null && $valor !== '') {

                    EquipoAtributoValor::create([
                        'equipo_id' => $equipo->id,
                        'atributo_id' => $atributoId,
                        'valor' => $valor,
                        'es_actual' => true,
                        'creado_por' => Auth::id(),
                    ]);
                }
            }
        });

        session()->flash('success', 'Equipo registrado correctamente.');

        return redirect()->route('equipos.index');
    }

    public function render()
    {
        return view('livewire.equipos.crear-equipo', [
            'categorias' => CategoriaEquipo::orderBy('nombre')->get(),
            'estados' => EstadoEquipo::orderBy('nombre')->get(),
        ]);
    }
}
