<?php

namespace App\Livewire\Equipos;

use App\Models\AtributoEquipo;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
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

    public int $equipoId;

    // ── Datos base editables ──────────────────────────────────────────────────
    public string $estado_id          = '';
    public string $ubicacion_id       = '';
    public string $fecha_adquisicion  = '';
    public string $fecha_garantia_fin = '';
    public string $observaciones      = '';

    // ── Atributos EAV simples ─────────────────────────────────────────────────
    public array $atributos = [];
    public array $valores   = [];

    /**
     * Archivos nuevos para atributos tipo 'file'.
     * null = sin cambio → conservar archivo actual.
     */
    public array $archivos = [];

    /**
     * Paths actuales de archivos existentes (solo lectura en vista).
     */
    public array $archivosActuales = [];

    /**
     * Instancias de atributos tipo 'group'.
     * [$atributoId => [ [sub_campo_id => valor, ...], ... ]]
     */
    public array $grupoInstancias = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Mount
    // ─────────────────────────────────────────────────────────────────────────
    public function mount(Equipo $equipo): void
    {
        $this->authorize('update', $equipo);

        $this->equipoId = $equipo->id;

        $equipo->load(['categoria.atributos', 'atributosActuales.atributo']);

        $this->estado_id          = (string) ($equipo->estado_id ?? '');
        $this->ubicacion_id       = (string) ($equipo->ubicacion_id ?? '');
        $this->fecha_adquisicion  = $equipo->fecha_adquisicion  ? $equipo->fecha_adquisicion->format('Y-m-d')  : '';
        $this->fecha_garantia_fin = $equipo->fecha_garantia_fin ? $equipo->fecha_garantia_fin->format('Y-m-d') : '';
        $this->observaciones      = $equipo->observaciones ?? '';

        // Atributos de la categoría como array plano (incluye sub_campos para group)
        $this->atributos = $equipo->categoria->atributos()
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

        // Valores actuales indexados por atributo_id
        $valoresActuales = $equipo->atributosActuales->keyBy('atributo_id');

        // Instancias de grupo existentes
        $instanciasGrupo = EquipoAtributoGrupoInstancia::where('equipo_id', $equipo->id)
            ->orderBy('atributo_id')
            ->orderBy('orden')
            ->get()
            ->groupBy('atributo_id');

        foreach ($this->atributos as $atributo) {
            $atributoId = $atributo['id'];

            if ($atributo['tipo'] === AtributoEquipo::TIPO_GROUP) {
                $this->grupoInstancias[$atributoId] = $instanciasGrupo->get($atributoId, collect())
                    ->map(fn($i) => $i->valores)
                    ->values()
                    ->toArray();
            } elseif ($atributo['tipo'] === AtributoEquipo::TIPO_FILE) {
                $this->archivosActuales[$atributoId] = $valoresActuales[$atributoId]?->valor;
                $this->archivos[$atributoId]         = null;
                $this->valores[$atributoId]          = '';
            } else {
                $this->valores[$atributoId] = $valoresActuales[$atributoId]?->valor ?? '';
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed
    // ─────────────────────────────────────────────────────────────────────────
    #[Computed]
    public function equipo(): Equipo
    {
        return Equipo::findOrFail($this->equipoId);
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
            'estado_id'          => 'required|exists:estados_equipos,id',
            'ubicacion_id'       => 'nullable|exists:ubicaciones,id',
            'fecha_adquisicion'  => 'nullable|date',
            'fecha_garantia_fin' => 'nullable|date|after_or_equal:fecha_adquisicion',
            'observaciones'      => 'nullable|string|max:2000',
        ];

        foreach ($this->atributos as $atributo) {
            $tipo       = $atributo['tipo'];
            $atributoId = $atributo['id'];

            if ($tipo === AtributoEquipo::TIPO_GROUP) {
                if ($atributo['requerido']) {
                    $rules["grupoInstancias.{$atributoId}"] = 'required|array|min:1';
                }
            } elseif ($tipo === AtributoEquipo::TIPO_FILE) {
                $tieneActual = ! empty($this->archivosActuales[$atributoId]);
                $rules["archivos.{$atributoId}"] = ($atributo['requerido'] && ! $tieneActual)
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
                $rules["valores.{$atributoId}"] = $atributo['requerido']
                    ? "required|{$tipoRegla}"
                    : "nullable|{$tipoRegla}";
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        $messages = [
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
    public function actualizar(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $equipo = $this->equipo;

                // Guardia: no cambiar estado si hay asignación activa
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
                    'fecha_adquisicion'  => $this->fecha_adquisicion   ?: null,
                    'fecha_garantia_fin' => $this->fecha_garantia_fin  ?: null,
                    'observaciones'      => $this->observaciones        ?: null,
                ]);

                foreach ($this->atributos as $atributo) {
                    $atributoId = $atributo['id'];

                    if ($atributo['tipo'] === AtributoEquipo::TIPO_GROUP) {
                        // ── Grupo: borrar y recrear instancias ───────────────
                        EquipoAtributoGrupoInstancia::where('equipo_id', $equipo->id)
                            ->where('atributo_id', $atributoId)
                            ->delete();

                        $orden = 0;
                        foreach ($this->grupoInstancias[$atributoId] ?? [] as $instancia) {
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
                        // ── Archivo ──────────────────────────────────────────
                        $nuevoArchivo = $this->archivos[$atributoId] ?? null;
                        if (! $nuevoArchivo) continue;

                        $valorActual = EquipoAtributoValor::where([
                            'equipo_id'   => $equipo->id,
                            'atributo_id' => $atributoId,
                            'es_actual'   => true,
                        ])->first();

                        if ($valorActual) {
                            if ($valorActual->valor && Storage::disk('public')->exists($valorActual->valor)) {
                                Storage::disk('public')->delete($valorActual->valor);
                            }
                            $valorActual->update(['es_actual' => false]);
                        }

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

                        $this->archivosActuales[$atributoId] = $path;
                        $this->archivos[$atributoId]         = null;
                    } else {
                        // ── Texto / número / fecha / etc ─────────────────────
                        $nuevoValor = $this->valores[$atributoId] ?? null;

                        $valorActual = EquipoAtributoValor::where([
                            'equipo_id'   => $equipo->id,
                            'atributo_id' => $atributoId,
                            'es_actual'   => true,
                        ])->first();

                        if ($valorActual && (string) $valorActual->valor === (string) $nuevoValor) {
                            continue;
                        }

                        if ($valorActual) {
                            $valorActual->update(['es_actual' => false]);
                        }

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
            // Redirect sin navigate: wire:navigate puede servir una versión
            // cacheada (prefetch) de la página destino, sin la sesión flash
            // recién escrita, y el toast de éxito nunca aparece.
            $this->redirect(route('admin.equipos.index'));
        } catch (\Exception $e) {
            Log::error('EditarEquipo@actualizar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Ocurrió un error al actualizar el equipo. Por favor intenta nuevamente.');
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

        return view('livewire.equipos.editar-equipo', [
            'estados'     => EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id'),
            'ubicaciones' => $ubicaciones,
        ]);
    }
}
