<?php

namespace App\Livewire\Equipos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\Equipo;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Services\CodigoInternoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class CrearEquipo extends Component
{
    use WithFileUploads;

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

    /**
     * Archivos temporales para atributos tipo 'file'.
     * Indexado por atributo_id => TemporaryUploadedFile|null
     *
     * Se mantiene SEPARADO de $valores porque Livewire serializa $valores
     * como strings, y los objetos TemporaryUploadedFile no son strings.
     * Al guardar, subimos el archivo, obtenemos el path y lo metemos en $valores.
     */
    public array $archivos = [];

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
            $this->archivos  = [];
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

        // Inicializar valores y archivos vacíos
        $this->valores  = [];
        $this->archivos = [];
        foreach ($this->atributos as $atributo) {
            $this->valores[$atributo['id']]  = '';
            $this->archivos[$atributo['id']] = null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación dinámica
    // ─────────────────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $rules = [
            'categoria_id'       => 'required|exists:categorias_equipos,id',
            // 'codigo_interno'     => 'required|string|max:100|unique:equipos,codigo_interno',
            'estado_id'          => 'required|exists:estados_equipos,id',
            'ubicacion_id'       => 'nullable|exists:ubicaciones,id',
            'serial'             => 'nullable|string|max:100',
            'nombre_maquina'     => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('equipos', 'nombre_maquina'),
            ],
            'fecha_adquisicion'  => 'nullable|date',
            'fecha_garantia_fin' => 'nullable|date|after_or_equal:fecha_adquisicion',
            'observaciones'      => 'nullable|string|max:2000',
        ];

        foreach ($this->atributos as $atributo) {
            $tipo = $atributo['tipo'];

            if ($tipo === AtributoEquipo::TIPO_FILE) {
                // Validar el objeto de archivo temporal, no el valor de texto
                $rules["archivos.{$atributo['id']}"] = $atributo['requerido']
                    ? 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'
                    : 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx';
            } else {
                $tipoRegla = match ($tipo) {
                    AtributoEquipo::TIPO_INTEGER => 'integer',
                    AtributoEquipo::TIPO_DECIMAL => 'numeric',
                    AtributoEquipo::TIPO_BOOLEAN => 'boolean',
                    AtributoEquipo::TIPO_DATE    => 'date',
                    AtributoEquipo::TIPO_TEXT    => 'string',
                    default                      => 'string|max:500',
                };

                $rules["valores.{$atributo['id']}"] = $atributo['requerido']
                    ? "required|{$tipoRegla}"
                    : "nullable|{$tipoRegla}";
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        $messages = [
            'categoria_id.required'              => 'Debe seleccionar una categoría.',
            'estado_id.required'                 => 'Debe seleccionar un estado.',
            'fecha_garantia_fin.after_or_equal'  => 'La garantía no puede ser anterior a la fecha de adquisición.',
            'serial.unique'                      => 'Ese serial ya está registrado en otro equipo.',
            'nombre_maquina.unique'              => 'Ese hostname ya existe en el sistema. Verifique en el Active Directory.',
        ];

        foreach ($this->atributos as $atributo) {
            if ($atributo['tipo'] === AtributoEquipo::TIPO_FILE) {
                $messages["archivos.{$atributo['id']}.required"] =
                    "El archivo «{$atributo['nombre']}» es obligatorio.";
                $messages["archivos.{$atributo['id']}.mimes"] =
                    "El archivo «{$atributo['nombre']}» debe ser PDF, imagen o documento Office.";
                $messages["archivos.{$atributo['id']}.max"] =
                    "El archivo «{$atributo['nombre']}» no puede superar 10 MB.";
            } else {
                $messages["valores.{$atributo['id']}.required"] =
                    "El campo «{$atributo['nombre']}» es obligatorio.";
            }
        }

        return $messages;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar
    // ─────────────────────────────────────────────────────────────────────────
    public function guardar(CodigoInternoService $codigos): void
    {
        $this->validate();

        try {
            DB::transaction(function () use ($codigos) {

                // Paso 1: crear el equipo SIN codigo_interno para obtener el id
                $equipo = Equipo::create([
                    'empresa_id'         => $this->empresa_id,
                    'categoria_id'       => $this->categoria_id,
                    'estado_id'          => $this->estado_id,
                    'ubicacion_id'       => $this->ubicacion_id ?: null,
                    'serial'             => $this->serial             ?: null,
                    'nombre_maquina'     => $this->nombre_maquina     ?: null,
                    'fecha_adquisicion'  => $this->fecha_adquisicion  ?: null,
                    'fecha_garantia_fin' => $this->fecha_garantia_fin ?: null,
                    'observaciones'      => $this->observaciones       ?: null,
                    'activo'             => true,
                ]);

                // Paso 2: ahora que tenemos el id, generamos y asignamos el código
                $equipo->update([
                    'codigo_interno' => $codigos->generar($equipo->id),
                ]);

                foreach ($this->atributos as $atributo) {
                    $atributoId = $atributo['id'];

                    if ($atributo['tipo'] === AtributoEquipo::TIPO_FILE) {
                        // ── Atributo tipo archivo ────────────────────────────
                        $archivo = $this->archivos[$atributoId] ?? null;
                        if (! $archivo) continue;

                        // Guardar en storage/app/public/equipos/archivos/{equipo_id}/
                        $path = $archivo->storeAs(
                            "equipos/archivos/{$equipo->id}",
                            Str::slug($atributo['nombre']) . '_' . time() . '.' . $archivo->getClientOriginalExtension(),
                            'public'
                        );

                        EquipoAtributoValor::create([
                            'equipo_id'   => $equipo->id,
                            'atributo_id' => $atributoId,
                            'valor'       => $path,  // almacenamos el path relativo
                            'es_actual'   => true,
                            'creado_por'  => Auth::id(),
                        ]);
                    } else {
                        // ── Atributo tipo texto/número/fecha/etc ─────────────
                        $valor = $this->valores[$atributoId] ?? null;
                        if ($valor === null || $valor === '') continue;

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
        } catch (\Exception $e) {
            Log::error('CrearEquipo@guardar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al crear el equipo. Por favor intenta nuevamente.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        $user = Auth::user();

        $ubicaciones = $user->hasRole('Administrador')
            ? Ubicacion::orderBy('nombre')->pluck('nombre', 'id')
            : Ubicacion::where(function ($q) use ($user) {
                $q->where('empresa_id', $user->empresa_activa_id)
                    ->orWhere('es_estado', true);
            })
            ->whereNot('activo', false)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        return view('livewire.equipos.crear-equipo', [
            'categorias'  => CategoriaEquipo::activos()->orderBy('nombre')->pluck('nombre', 'id'),
            'estados'     => EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id'),
            'ubicaciones' => $ubicaciones,
        ]);
    }
}
