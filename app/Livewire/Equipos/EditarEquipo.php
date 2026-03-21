<?php

namespace App\Livewire\Equipos;

use App\Models\AtributoEquipo;
use App\Models\Equipo;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EditarEquipo extends Component
{
    // ── ID del equipo (int = serializable por Livewire entre requests) ────────
    public int $equipoId;

    // ── Datos base editables ──────────────────────────────────────────────────
    public string $estado_id          = '';
    public string $ubicacion_id       = '';
    public string $serial             = '';
    public string $nombre_maquina     = '';
    public string $fecha_adquisicion  = '';
    public string $fecha_garantia_fin = '';
    public string $observaciones      = '';

    // ── Atributos EAV como array plano (serializable por Livewire) ────────────
    public array $atributos = [];
    public array $valores   = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Mount: recibe el modelo por route-model binding del controller
    // ─────────────────────────────────────────────────────────────────────────
    public function mount(Equipo $equipo): void
    {
        $this->authorize('update', $equipo);

        $this->equipoId = $equipo->id;

        // Cargar relaciones necesarias
        $equipo->load(['categoria.atributos', 'atributosActuales.atributo']);

        // Hidratar campos base
        $this->estado_id          = (string) ($equipo->estado_id ?? '');
        $this->ubicacion_id       = (string) ($equipo->ubicacion_id ?? '');
        $this->serial             = $equipo->serial          ?? '';
        $this->nombre_maquina     = $equipo->nombre_maquina  ?? '';
        $this->fecha_adquisicion  = $equipo->fecha_adquisicion  ?? '';
        $this->fecha_garantia_fin = $equipo->fecha_garantia_fin ?? '';
        $this->observaciones      = $equipo->observaciones    ?? '';

        // Atributos de la categoría como array plano
        $this->atributos = $equipo->categoria->atributos()
            ->orderBy('orden')
            ->get()
            ->map(fn($a) => [
                'id'        => $a->id,
                'nombre'    => $a->nombre,
                'tipo'      => $a->tipo,
                'requerido' => (bool) $a->requerido,
                'opciones'  => $a->opciones ?? [],
            ])
            ->toArray();

        // Valores actuales indexados por atributo_id
        $valoresActuales = $equipo->atributosActuales->keyBy('atributo_id');

        foreach ($this->atributos as $atributo) {
            $this->valores[$atributo['id']] = $valoresActuales[$atributo['id']]?->valor ?? null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed Properties — disponibles como variables en el blade
    // ─────────────────────────────────────────────────────────────────────────

    /** El equipo fresco desde BD — disponible como $equipo en el blade */
    #[Computed]
    public function equipo(): Equipo
    {
        return Equipo::with(['categoria', 'estado', 'ubicacion'])->findOrFail($this->equipoId);
    }

    #[Computed]
    public function estados()
    {
        return EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function ubicaciones()
    {
        // Las ubicaciones disponibles son las de la empresa del equipo
        return Ubicacion::where('empresa_id', $this->equipo->empresa_id)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación dinámica
    // ─────────────────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $rules = [
            'estado_id'          => 'required|exists:estados_equipos,id',
            'ubicacion_id'       => 'nullable|exists:ubicaciones,id',
            'serial'             => 'nullable|string|max:255',
            'nombre_maquina'     => 'nullable|string|max:255',
            'fecha_adquisicion'  => 'nullable|date',
            'fecha_garantia_fin' => 'nullable|date|after_or_equal:fecha_adquisicion',
            'observaciones'      => 'nullable|string|max:1000',
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

    protected function messages(): array
    {
        $messages = [
            'estado_id.required' => 'Debe seleccionar un estado.',
            'fecha_garantia_fin.after_or_equal' =>
                'La garantía no puede ser anterior a la fecha de adquisición.',
        ];

        foreach ($this->atributos as $atributo) {
            $messages["valores.{$atributo['id']}.required"] =
                "El campo «{$atributo['nombre']}» es obligatorio.";
        }

        return $messages;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Actualizar con versionado EAV
    // ─────────────────────────────────────────────────────────────────────────
    public function actualizar(): void
    {
        $equipo = $this->equipo;
        $this->authorize('update', $equipo);
        $this->validate();

        DB::transaction(function () use ($equipo) {

            // ── Datos base del equipo ─────────────────────────────────────────
            $equipo->update([
                'estado_id'          => $this->estado_id,
                'ubicacion_id'       => $this->ubicacion_id  ?: null,
                'serial'             => $this->serial         ?: null,
                'nombre_maquina'     => $this->nombre_maquina ?: null,
                'fecha_adquisicion'  => $this->fecha_adquisicion  ?: null,
                'fecha_garantia_fin' => $this->fecha_garantia_fin ?: null,
                'observaciones'      => $this->observaciones       ?: null,
            ]);

            // ── Versionado EAV: solo registra si el valor realmente cambió ────
            foreach ($this->valores as $atributoId => $nuevoValor) {

                $valorActual = EquipoAtributoValor::where([
                    'equipo_id'   => $equipo->id,
                    'atributo_id' => $atributoId,
                    'es_actual'   => true,
                ])->first();

                // Sin cambio → no hacer nada
                if ($valorActual && (string) $valorActual->valor === (string) $nuevoValor) {
                    continue;
                }

                // Marcar versión anterior como histórico
                if ($valorActual) {
                    $valorActual->update(['es_actual' => false]);
                }

                // Crear nueva versión solo si hay valor
                if ($nuevoValor !== null && $nuevoValor !== '') {
                    EquipoAtributoValor::create([
                        'equipo_id'   => $equipo->id,
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

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.equipos.editar-equipo');
    }
}