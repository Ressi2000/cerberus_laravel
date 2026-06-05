<?php

namespace App\Livewire\Equipos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Services\CodigoInternoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    public string $fecha_adquisicion  = '';
    public string $fecha_garantia_fin = '';
    public string $observaciones      = '';

    // ── Atributos EAV simples ─────────────────────────────────────────────────
    public array $atributos = [];
    public array $valores   = [];

    /**
     * Archivos temporales para atributos tipo 'file'.
     * Indexado por atributo_id => TemporaryUploadedFile|null
     */
    public array $archivos = [];

    /**
     * Instancias de atributos tipo 'group'.
     * Estructura: [$atributoId => [ [sub_campo_id => valor, ...], ... ]]
     */
    public array $grupoInstancias = [];

    // ── Empresa del equipo ────────────────────────────────────────────────────
    public int $empresa_id;

    // ─────────────────────────────────────────────────────────────────────────
    // Mount
    // ─────────────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = Auth::user();
        $this->empresa_id = (int) ($user->empresa_activa_id ?? $user->empresa_id);
        $this->estado_id  = (string) (EstadoEquipo::where('nombre', 'Disponible')->value('id') ?? '');
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
            $this->atributos      = [];
            $this->valores        = [];
            $this->archivos       = [];
            $this->grupoInstancias = [];
            return;
        }

        $this->atributos = AtributoEquipo::where('categoria_id', $categoriaId)
            ->orderBy('orden')
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'nombre'     => $a->nombre,
                'tipo'       => $a->tipo,
                'requerido'  => (bool) $a->requerido,
                'opciones'   => $a->opciones ?? [],
                'sub_campos' => $a->sub_campos ?? [],
            ])
            ->toArray();

        $this->valores        = [];
        $this->archivos       = [];
        $this->grupoInstancias = [];

        foreach ($this->atributos as $atributo) {
            if ($atributo['tipo'] === AtributoEquipo::TIPO_GROUP) {
                $this->grupoInstancias[$atributo['id']] = [];
            } else {
                $this->valores[$atributo['id']]  = '';
                $this->archivos[$atributo['id']] = null;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Gestión de instancias de grupo
    // ─────────────────────────────────────────────────────────────────────────
    public function agregarInstanciaGrupo(int $atributoId): void
    {
        $atributo = collect($this->atributos)->firstWhere('id', $atributoId);
        if (! $atributo || $atributo['tipo'] !== AtributoEquipo::TIPO_GROUP) return;

        $instancia = [];
        foreach ($atributo['sub_campos'] as $sc) {
            $instancia[$sc['id']] = '';
        }
        $this->grupoInstancias[$atributoId][] = $instancia;
    }

    public function eliminarInstanciaGrupo(int $atributoId, int $index): void
    {
        if (isset($this->grupoInstancias[$atributoId][$index])) {
            array_splice($this->grupoInstancias[$atributoId], $index, 1);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación dinámica
    // ─────────────────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $rules = [
            'categoria_id'       => 'required|exists:categorias_equipos,id',
            'estado_id'          => 'required|exists:estados_equipos,id',
            'ubicacion_id'       => 'nullable|exists:ubicaciones,id',
            'fecha_adquisicion'  => 'nullable|date',
            'fecha_garantia_fin' => 'nullable|date|after_or_equal:fecha_adquisicion',
            'observaciones'      => 'nullable|string|max:2000',
        ];

        foreach ($this->atributos as $atributo) {
            $tipo = $atributo['tipo'];

            if ($tipo === AtributoEquipo::TIPO_GROUP) {
                if ($atributo['requerido']) {
                    $rules["grupoInstancias.{$atributo['id']}"] = 'required|array|min:1';
                }
            } elseif ($tipo === AtributoEquipo::TIPO_FILE) {
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
            'categoria_id.required'             => 'Debe seleccionar una categoría.',
            'estado_id.required'                => 'Debe seleccionar un estado.',
            'fecha_garantia_fin.after_or_equal' => 'La garantía no puede ser anterior a la fecha de adquisición.',
        ];

        foreach ($this->atributos as $atributo) {
            if ($atributo['tipo'] === AtributoEquipo::TIPO_GROUP) {
                $messages["grupoInstancias.{$atributo['id']}.required"] =
                    "Debe agregar al menos un «{$atributo['nombre']}».";
                $messages["grupoInstancias.{$atributo['id']}.min"] =
                    "Debe agregar al menos un «{$atributo['nombre']}».";
            } elseif ($atributo['tipo'] === AtributoEquipo::TIPO_FILE) {
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

                $equipo = Equipo::create([
                    'empresa_id'         => $this->empresa_id,
                    'categoria_id'       => $this->categoria_id,
                    'estado_id'          => $this->estado_id,
                    'ubicacion_id'       => $this->ubicacion_id ?: null,
                    'fecha_adquisicion'  => $this->fecha_adquisicion  ?: null,
                    'fecha_garantia_fin' => $this->fecha_garantia_fin ?: null,
                    'observaciones'      => $this->observaciones       ?: null,
                    'activo'             => true,
                    'creado_por'         => Auth::id(),
                ]);

                $equipo->update([
                    'codigo_interno' => $codigos->generar($equipo->id),
                ]);

                foreach ($this->atributos as $atributo) {
                    $atributoId = $atributo['id'];

                    if ($atributo['tipo'] === AtributoEquipo::TIPO_GROUP) {
                        // ── Atributo tipo grupo ──────────────────────────────
                        $instancias = $this->grupoInstancias[$atributoId] ?? [];
                        $orden = 0;
                        foreach ($instancias as $instancia) {
                            $hasValue = collect($instancia)->filter(fn($v) => $v !== '' && $v !== null)->isNotEmpty();
                            if (! $hasValue) continue;

                            EquipoAtributoGrupoInstancia::create([
                                'equipo_id'   => $equipo->id,
                                'atributo_id' => $atributoId,
                                'valores'     => $instancia,
                                'orden'       => $orden++,
                            ]);
                        }
                    } elseif ($atributo['tipo'] === AtributoEquipo::TIPO_FILE) {
                        // ── Atributo tipo archivo ────────────────────────────
                        $archivo = $this->archivos[$atributoId] ?? null;
                        if (! $archivo) continue;

                        $path = $archivo->storeAs(
                            "equipos/archivos/{$equipo->id}",
                            Str::slug($atributo['nombre']) . '_' . time() . '.' . $archivo->getClientOriginalExtension(),
                            'public'
                        );

                        EquipoAtributoValor::create([
                            'equipo_id'   => $equipo->id,
                            'atributo_id' => $atributoId,
                            'valor'       => $path,
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
            $this->dispatch('toast', type: 'error', message: 'Ocurrió un error al crear el equipo. Por favor intenta nuevamente.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        $user = Auth::user();

        $ubicaciones = $user->hasRole('Administrador')
            ? Ubicacion::orderBy('es_estado')->orderBy('nombre')->pluck('nombre', 'id')
            : Ubicacion::where(function ($q) use ($user) {
                $q->where('empresa_id', $user->empresa_activa_id)
                    ->orWhere('es_estado', true);
            })
            ->whereNot('activo', false)
            ->orderBy('es_estado')->orderBy('nombre')
            ->pluck('nombre', 'id');

        return view('livewire.equipos.crear-equipo', [
            'categorias'  => CategoriaEquipo::activos()->orderBy('nombre')->pluck('nombre', 'id'),
            'estados'     => EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id'),
            'ubicaciones' => $ubicaciones,
        ]);
    }
}
