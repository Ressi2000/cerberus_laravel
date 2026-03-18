<?php

namespace App\Livewire\Equipos;

use App\Models\Equipo;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use Illuminate\Support\Facades\Auth;

class EditarEquipo extends Component
{
    public Equipo $equipo;

    public $estado_id        = '';
    public $ubicacion_id     = '';
    public $serial           = '';
    public $nombre_maquina   = '';
    public $fecha_adquisicion   = '';
    public $fecha_garantia_fin  = '';
    public $observaciones    = '';

    // Array plano serializable por Livewire (NO Collection de Eloquent)
    public array $atributos = [];
    public array $valores   = [];

    public function mount(Equipo $equipo): void
    {
        $this->authorize('update', $equipo);

        $this->equipo = $equipo->load([
            'categoria.atributos',
            'atributosActuales.atributo',
        ]);

        // Datos base
        $this->estado_id          = $equipo->estado_id;
        $this->ubicacion_id       = $equipo->ubicacion_id;
        $this->serial             = $equipo->serial ?? '';
        $this->nombre_maquina     = $equipo->nombre_maquina ?? '';
        $this->fecha_adquisicion  = $equipo->fecha_adquisicion ?? '';
        $this->fecha_garantia_fin = $equipo->fecha_garantia_fin ?? '';
        $this->observaciones      = $equipo->observaciones ?? '';

        // Atributos como array plano (serializable)
        $this->atributos = $equipo->categoria->atributos()
            ->orderBy('orden')
            ->get()
            ->map(fn($a) => [
                'id'        => $a->id,
                'nombre'    => $a->nombre,
                'tipo'      => $a->tipo,
                'requerido' => $a->requerido,
                'opciones'  => $a->opciones ?? [],
            ])
            ->toArray();

        // Cargar valores actuales
        $valoresActuales = $equipo->atributosActuales->keyBy('atributo_id');

        foreach ($this->atributos as $atributo) {
            $this->valores[$atributo['id']] = $valoresActuales[$atributo['id']]?->valor ?? null;
        }
    }

    private function reglasDinamicas(): array
    {
        $rules = [
            'estado_id'          => 'required|exists:estados_equipos,id',
            'ubicacion_id'       => 'nullable|exists:ubicaciones,id',
            'serial'             => 'nullable|string|max:255',
            'nombre_maquina'     => 'nullable|string|max:255',
            'fecha_adquisicion'  => 'nullable|date',
            'fecha_garantia_fin' => 'nullable|date|after_or_equal:fecha_adquisicion',
            'observaciones'      => 'nullable|string',
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

    public function actualizar(): void
    {
        $this->validate($this->reglasDinamicas(), $this->mensajesDinamicos());

        DB::transaction(function () {

            // Actualizar datos base del equipo
            $this->equipo->update([
                'estado_id'          => $this->estado_id,
                'ubicacion_id'       => $this->ubicacion_id ?: null,
                'serial'             => $this->serial ?: null,
                'nombre_maquina'     => $this->nombre_maquina ?: null,
                'fecha_adquisicion'  => $this->fecha_adquisicion ?: null,
                'fecha_garantia_fin' => $this->fecha_garantia_fin ?: null,
                'observaciones'      => $this->observaciones ?: null,
            ]);

            // Versionado EAV: solo registra si el valor cambió
            foreach ($this->valores as $atributoId => $nuevoValor) {

                $valorActual = EquipoAtributoValor::where([
                    'equipo_id'  => $this->equipo->id,
                    'atributo_id'=> $atributoId,
                    'es_actual'  => true,
                ])->first();

                // Sin cambio → no hacer nada
                if ($valorActual && (string) $valorActual->valor === (string) $nuevoValor) {
                    continue;
                }

                // Marcar anterior como histórico
                if ($valorActual) {
                    $valorActual->update(['es_actual' => false]);
                }

                // Crear nueva versión solo si hay valor
                if ($nuevoValor !== null && $nuevoValor !== '') {
                    EquipoAtributoValor::create([
                        'equipo_id'   => $this->equipo->id,
                        'atributo_id' => $atributoId,
                        'valor'       => $nuevoValor,
                        'es_actual'   => true,
                        'creado_por'  => Auth::id(),
                    ]);
                }
            }
        });

        session()->flash('success', 'Equipo actualizado correctamente.');

        $this->redirect(route('admin.equipos.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.equipos.editar-equipo', [
            'estados'    => EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id'),
            'ubicaciones'=> \App\Models\Ubicacion::where('empresa_id', $this->equipo->empresa_id)
                                ->orderBy('nombre')
                                ->pluck('nombre', 'id'),
        ]);
    }
}