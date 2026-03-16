<?php

namespace App\Livewire\Equipos;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\EquipoAtributoValor;
use App\Models\Equipos;
use Illuminate\Support\Facades\Auth;

class EditarEquipo extends Component
{
    public Equipos $equipo;

    public $estado_id;
    public $ubicacion_id;
    public $serial;
    public $nombre_maquina;
    public $fecha_adquisicion;
    public $fecha_garantia_fin;
    public $observaciones;

    public $atributos = [];
    public $valores = [];

    public function mount(Equipos $equipo)
    {
        $this->authorize('update', $equipo);

        $this->equipo = $equipo->load([
            'categoria.atributos',
            'atributosActuales'
        ]);

        $this->estado_id = $equipo->estado_id;
        $this->ubicacion_id = $equipo->ubicacion_id;
        $this->serial = $equipo->serial;
        $this->nombre_maquina = $equipo->nombre_maquina;
        $this->fecha_adquisicion = $equipo->fecha_adquisicion;
        $this->fecha_garantia_fin = $equipo->fecha_garantia_fin;
        $this->observaciones = $equipo->observaciones;

        $this->atributos = $equipo->categoria->atributos()->orderBy('orden')->get();

        foreach ($this->atributos as $atributo) {
            $valorActual = $equipo->atributosActuales
                ->where('atributo_id', $atributo->id)
                ->first();

            $this->valores[$atributo->id] = $valorActual?->valor;
        }
    }

    private function reglasDinamicas()
    {
        $rules = [
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

    public function actualizar()
    {
        $this->validate($this->reglasDinamicas());

        DB::transaction(function () {

            // Actualizar datos base
            $this->equipo->update([
                'estado_id' => $this->estado_id,
                'ubicacion_id' => $this->ubicacion_id,
                'serial' => $this->serial,
                'nombre_maquina' => $this->nombre_maquina,
                'fecha_adquisicion' => $this->fecha_adquisicion,
                'fecha_garantia_fin' => $this->fecha_garantia_fin,
                'observaciones' => $this->observaciones,
            ]);

            // Versionado de atributos
            foreach ($this->valores as $atributoId => $nuevoValor) {

                $valorActual = EquipoAtributoValor::where([
                    'equipo_id' => $this->equipo->id,
                    'atributo_id' => $atributoId,
                    'es_actual' => true,
                ])->first();

                $valorAnterior = $valorActual?->valor;

                if ($valorAnterior != $nuevoValor) {

                    if ($valorActual) {
                        $valorActual->update(['es_actual' => false]);
                    }

                    if ($nuevoValor !== null && $nuevoValor !== '') {
                        EquipoAtributoValor::create([
                            'equipo_id' => $this->equipo->id,
                            'atributo_id' => $atributoId,
                            'valor' => $nuevoValor,
                            'es_actual' => true,
                            'creado_por' => Auth::id(),
                        ]);
                    }
                }
            }
        });

        session()->flash('success', 'Equipo actualizado correctamente.');

        return redirect()->route('equipos.index');
    }

    public function render()
    {
        return view('livewire.equipos.editar-equipo');
    }
}

