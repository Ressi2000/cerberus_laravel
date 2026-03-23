<?php

namespace App\Livewire\Equipos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\Equipo;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CrearEquipo extends Component
{
    // ── Datos base ────────────────────────────────────────────────────────────
    public string $categoria_id       = '';
    public string $estado_id          = '';
    public string $ubicacion_id       = '';
    public string $codigo_interno     = '';
    public string $serial             = '';
    public string $nombre_maquina     = '';
    public string $fecha_adquisicion  = '';
    public string $fecha_garantia_fin = '';
    public string $observaciones      = '';

    // ── Atributos EAV (array plano serializable) ──────────────────────────────
    public array $atributos = [];
    public array $valores   = [];

    // ── Empresa del equipo ────────────────────────────────────────────────────
    public int $empresa_id;

    // ─────────────────────────────────────────────────────────────────────────
    // Mount
    // ─────────────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = Auth::user();

        // Prioridad: empresa activa (analista) → empresa de nómina
        $this->empresa_id = (int) ($user->empresa_activa_id ?? $user->empresa_id);

        // Estado inicial: Disponible
        $this->estado_id = (string) (EstadoEquipo::where('nombre', 'Disponible')->value('id') ?? '');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cascada de categoría → atributos EAV
    // ─────────────────────────────────────────────────────────────────────────
    public function updatedCategoriaId(string $value): void
    {
        $this->cargarAtributos($value);
        $this->resetValidation();
    }

    private function cargarAtributos(string $categoriaId): void
    {
        if (! $categoriaId) {
            $this->atributos = [];
            $this->valores   = [];
            return;
        }

        $this->atributos = AtributoEquipo::where('categoria_id', $categoriaId)
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

        // Inicializar valores vacíos
        $this->valores = [];
        foreach ($this->atributos as $a) {
            $this->valores[$a['id']] = null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed Properties
    // ─────────────────────────────────────────────────────────────────────────
    #[Computed]
    public function categorias()
    {
        return CategoriaEquipo::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function estados()
    {
        return EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function ubicaciones()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('Administrador')) {
            return Ubicacion::orderBy('nombre')->pluck('nombre', 'id');
        }

        // Analista: solo la ubicación de su empresa activa + foráneos
        return Ubicacion::where(function ($q) use ($user) {
            $q->where('empresa_id', $user->empresa_activa_id)
                ->orWhere('es_estado', true);
        })
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación dinámica
    // ─────────────────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $rules = [
            'categoria_id'       => 'required|exists:categorias_equipos,id',
            'codigo_interno'     => 'required|string|max:100|unique:equipos,codigo_interno',
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
            'categoria_id.required'   => 'Debe seleccionar una categoría.',
            'codigo_interno.required' => 'El código interno es obligatorio.',
            'codigo_interno.unique'   => 'Ese código interno ya está en uso.',
            'estado_id.required'      => 'Debe seleccionar un estado.',
            'fecha_garantia_fin.after_or_equal' => 'La garantía no puede ser anterior a la fecha de adquisición.',
        ];

        foreach ($this->atributos as $atributo) {
            $messages["valores.{$atributo['id']}.required"] =
                "El campo «{$atributo['nombre']}» es obligatorio.";
        }

        return $messages;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar
    // ─────────────────────────────────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        DB::transaction(function () {

            $equipo = Equipo::create([
                'empresa_id'        => $this->empresa_id,
                'categoria_id'      => $this->categoria_id,
                'estado_id'         => $this->estado_id,
                'ubicacion_id'      => $this->ubicacion_id ?: null,
                'codigo_interno'    => $this->codigo_interno,
                'serial'            => $this->serial        ?: null,
                'nombre_maquina'    => $this->nombre_maquina ?: null,
                'fecha_adquisicion' => $this->fecha_adquisicion  ?: null,
                'fecha_garantia_fin' => $this->fecha_garantia_fin ?: null,
                'observaciones'     => $this->observaciones      ?: null,
                'activo'            => true,
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

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.equipos.crear-equipo');
    }
}
