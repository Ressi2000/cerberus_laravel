<?php

namespace App\Livewire\Equipos;

use App\Models\AtributoEquipo;
use App\Models\Equipo;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarEquipo extends Component
{
    use WithFileUploads;

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

    /**
     * Archivos nuevos para atributos tipo 'file' (upload temporal de Livewire).
     * Indexado por atributo_id => TemporaryUploadedFile|null
     *
     * null = el usuario no subió nada nuevo → conservar archivo actual.
     */
    public array $archivos = [];

    /**
     * Paths actuales de archivos existentes (para mostrarlos en la vista).
     * Indexado por atributo_id => string|null
     *
     * Se carga en mount() y se actualiza tras cada guardado.
     * Solo es lectura en la vista; no se envía al servidor como upload.
     */
    public array $archivosActuales = [];

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
            $valorActual = $valoresActuales[$atributo['id']] ?? null;

            if ($atributo['tipo'] === AtributoEquipo::TIPO_FILE) {
                // Para tipo file: guardar el path actual para mostrarlo en vista
                $this->archivosActuales[$atributo['id']] = $valorActual?->valor;
                $this->archivos[$atributo['id']]         = null; // sin nuevo upload aún
                $this->valores[$atributo['id']]          = '';   // no usado para file
            } else {
                $this->valores[$atributo['id']] = $valorActual?->valor ?? '';
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed: equipo completo (evita serializar el modelo en propiedades)
    // ─────────────────────────────────────────────────────────────────────────
    #[Computed]
    public function equipo(): Equipo
    {
        return Equipo::findOrFail($this->equipoId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación dinámica
    // ─────────────────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $rules = [
            'estado_id'          => 'required|exists:estados_equipos,id',
            'ubicacion_id'       => 'nullable|exists:ubicaciones,id',
            'serial'             => 'nullable|string|max:100',
            'nombre_maquina'     => 'nullable|string|max:100',
            'fecha_adquisicion'  => 'nullable|date',
            'fecha_garantia_fin' => 'nullable|date|after_or_equal:fecha_adquisicion',
            'observaciones'      => 'nullable|string|max:2000',
        ];

        foreach ($this->atributos as $atributo) {
            $tipo = $atributo['tipo'];

            if ($tipo === AtributoEquipo::TIPO_FILE) {
                // En edición: el archivo nuevo es nullable si ya existe uno actual
                $tieneActual = ! empty($this->archivosActuales[$atributo['id']]);
                $regla = ($atributo['requerido'] && ! $tieneActual)
                    ? 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'
                    : 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx';

                $rules["archivos.{$atributo['id']}"] = $regla;
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
            'estado_id.required'  => 'Debe seleccionar un estado.',
            'fecha_garantia_fin.after_or_equal' => 'La garantía no puede ser anterior a la fecha de adquisición.',
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
    public function actualizar(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $equipo = $this->equipo;

                // ── Guardia: no permitir cambio de estado si hay asignación activa ───────
                $tieneAsignacionActiva = \App\Models\AsignacionItem::where('equipo_id', $this->equipo->id)
                    ->where('devuelto', false)
                    ->whereHas('asignacion', fn($q) => $q->where('estado', 'Activa'))
                    ->exists();

                if ($tieneAsignacionActiva && $this->estado_id != $this->equipo->estado_id) {
                    $this->addError(
                        'estado_id',
                        'No puedes cambiar el estado de un equipo con asignación activa. Realiza la devolución primero.'
                    );
                    return;
                }

                $equipo->update([
                    'estado_id'          => $this->estado_id,
                    'ubicacion_id'       => $this->ubicacion_id       ?: null,
                    'serial'             => $this->serial              ?: null,
                    'nombre_maquina'     => $this->nombre_maquina      ?: null,
                    'fecha_adquisicion'  => $this->fecha_adquisicion   ?: null,
                    'fecha_garantia_fin' => $this->fecha_garantia_fin  ?: null,
                    'observaciones'      => $this->observaciones        ?: null,
                ]);

                foreach ($this->atributos as $atributo) {
                    $atributoId = $atributo['id'];

                    if ($atributo['tipo'] === AtributoEquipo::TIPO_FILE) {
                        // ── Atributo tipo archivo ────────────────────────────
                        $nuevoArchivo = $this->archivos[$atributoId] ?? null;
                        if (! $nuevoArchivo) continue; // sin cambio → conservar actual

                        // Marcar versión anterior como histórico
                        $valorActual = EquipoAtributoValor::where([
                            'equipo_id'   => $equipo->id,
                            'atributo_id' => $atributoId,
                            'es_actual'   => true,
                        ])->first();

                        if ($valorActual) {
                            // Eliminar el archivo físico anterior del storage
                            if ($valorActual->valor && Storage::disk('public')->exists($valorActual->valor)) {
                                Storage::disk('public')->delete($valorActual->valor);
                            }
                            $valorActual->update(['es_actual' => false]);
                        }

                        // Guardar nuevo archivo
                        $path = $nuevoArchivo->storeAs(
                            "equipos/archivos/{$equipo->id}",
                            Str::slug($atributo['nombre']) . '_' . time() . '.' . $nuevoArchivo->getClientOriginalExtension(),
                            'public'
                        );

                        EquipoAtributoValor::create([
                            'equipo_id'   => $equipo->id,
                            'atributo_id' => $atributoId,
                            'valor'       => $path,
                            'es_actual'   => true,
                            'creado_por'  => Auth::id(),
                        ]);

                        // Actualizar referencia local para la vista
                        $this->archivosActuales[$atributoId] = $path;
                        $this->archivos[$atributoId]         = null;
                    } else {
                        // ── Atributo tipo texto/número/fecha/etc ─────────────
                        $nuevoValor = $this->valores[$atributoId] ?? null;

                        $valorActual = EquipoAtributoValor::where([
                            'equipo_id'   => $equipo->id,
                            'atributo_id' => $atributoId,
                            'es_actual'   => true,
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
                                'equipo_id'   => $equipo->id,
                                'atributo_id' => $atributoId,
                                'valor'       => $nuevoValor,
                                'es_actual'   => true,
                                'creado_por'  => Auth::id(),
                            ]);
                        }
                    }
                }
            });

            session()->flash('success', 'Equipo actualizado correctamente.');
            $this->redirect(route('admin.equipos.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('EditarEquipo@actualizar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al actualizar el equipo. Por favor intenta nuevamente.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.equipos.editar-equipo', [
            'estados'     => EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id'),
            'ubicaciones' => Ubicacion::where('empresa_id', $this->equipo->empresa_id)
                ->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
