<?php

namespace App\Livewire\Mantenimientos;

use App\Models\AsignacionItem;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\TareaMantenimientoCatalogo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulario de creación de un mantenimiento/reparación.
 *
 * Al guardar: vincula automáticamente la asignación activa del equipo (si
 * tiene una), arranca en el primer estado de su flujo según el tipo, y
 * bloquea el equipo — Correctivo se bloquea ya mismo (el equipo ya falló);
 * Preventivo NO se bloquea hasta pasar a "En proceso" (ver
 * Mantenimiento::avanzarEstado()), para no inutilizar un equipo por un caso
 * "Programado" con días de antelación. Exige una foto "Antes" como
 * evidencia inicial del estado del equipo.
 */
class CrearMantenimiento extends Component
{
    use WithFileUploads;

    public string $empresa_id  = '';
    public string $equipo_id   = '';
    public string $tipo        = 'Correctivo';

    // Búsqueda alternativa: por la persona que tiene el equipo, para cuando
    // el analista no recuerda el código del equipo.
    public bool   $buscarPorUsuario = false;
    public string $usuario_id       = '';

    public $fotoAntes = null;

    public string $descripcion        = '';
    public string $fecha_inicio       = '';
    public string $fecha_fin_estimada = '';
    public string $responsable_id     = '';
    public string $proveedor_externo  = '';

    // Garantía: se autodetecta al elegir el equipo (fecha_garantia_fin),
    // solo informativo — el analista puede destildarlo si no corresponde.
    public bool   $en_garantia     = false;

    // Correctivo
    public string $falla_reportada = '';

    // Preventivo
    public ?int   $frecuencia_meses          = null;
    public string $proxima_fecha_programada  = '';
    public array  $checklist                 = [];
    public string $nuevaTareaChecklist        = '';

    public function mount(): void
    {
        $this->authorize('create', Mantenimiento::class);

        $actor = Auth::user();
        if (! $actor->hasRole('Administrador')) {
            $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
        }

        $this->fecha_inicio = now()->format('Y-m-d');
        $this->cargarChecklistDefault();
    }

    public function updatedEmpresaId(): void
    {
        $this->equipo_id = '';
        $this->usuario_id = '';
    }

    public function updatedUsuarioId(): void
    {
        $this->equipo_id = '';
    }

    public function updatedBuscarPorUsuario(): void
    {
        $this->equipo_id = '';
        $this->usuario_id = '';
    }

    public function updatedTipo(): void
    {
        if ($this->tipo === 'Preventivo' && empty($this->checklist)) {
            $this->cargarChecklistDefault();
        }
    }

    public function updatedEquipoId(): void
    {
        $equipo = $this->equipo_id ? Equipo::find($this->equipo_id) : null;
        $this->en_garantia = $equipo && $equipo->fecha_garantia_fin && $equipo->fecha_garantia_fin->isFuture();
    }

    private function cargarChecklistDefault(): void
    {
        $this->checklist = TareaMantenimientoCatalogo::activas()->ordenadas()->pluck('nombre')
            ->map(fn ($tarea) => ['tarea' => $tarea, 'incluir' => true])
            ->toArray();
    }

    public function agregarTareaChecklist(): void
    {
        $tarea = trim($this->nuevaTareaChecklist);
        if ($tarea === '') return;

        $this->checklist[] = ['tarea' => $tarea, 'incluir' => true];
        $this->nuevaTareaChecklist = '';
    }

    public function quitarTareaChecklist(int $index): void
    {
        unset($this->checklist[$index]);
        $this->checklist = array_values($this->checklist);
    }

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        if ($actor->hasRole('Administrador')) {
            return Empresa::activas()->orderBy('nombre')->pluck('nombre', 'id');
        }

        return Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    #[Computed]
    public function responsablesOpciones()
    {
        return User::whereIn('id', function ($q) {
            $q->select('model_id')
              ->from('model_has_roles')
              ->whereIn('role_id', function ($q2) {
                  $q2->select('id')->from('roles')->whereIn('name', ['Administrador', 'Analista']);
              });
        })->orderBy('name')->pluck('name', 'id');
    }

    /** Usuarios con equipos asignados en la empresa elegida — para buscar el equipo por la persona. */
    #[Computed]
    public function usuariosOpciones()
    {
        if (! $this->empresa_id) {
            return collect();
        }

        return User::whereHas('asignaciones', fn ($q) => $q
                ->where('estado', 'Activa')
                ->where('empresa_id', $this->empresa_id)
            )
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Equipos disponibles para elegir: si se está buscando por usuario, solo
     * los que esa persona tiene asignados actualmente; si no, todos los de
     * la empresa. En ambos casos, excluye equipos con un caso ya abierto.
     */
    #[Computed]
    public function equiposOpciones()
    {
        if (! $this->empresa_id) {
            return collect();
        }

        if ($this->buscarPorUsuario) {
            if (! $this->usuario_id) {
                return collect();
            }

            $usuario = User::find($this->usuario_id);
            $equipoIds = $usuario?->asignacionItemsActivos()->pluck('equipo_id') ?? collect();

            return Equipo::with('categoria')
                ->whereIn('id', $equipoIds)
                ->where('activo', true)
                ->whereDoesntHave('mantenimientos', fn ($q) => $q->whereNotIn('estado', Mantenimiento::ESTADOS_TERMINALES))
                ->orderBy('codigo_interno')
                ->get()
                ->mapWithKeys(fn ($e) => [
                    $e->id => trim(($e->codigo_interno ?? "Equipo #{$e->id}") . ' — ' . ($e->categoria->nombre ?? '')),
                ]);
        }

        return Equipo::with('categoria')
            ->where('empresa_id', $this->empresa_id)
            ->where('activo', true)
            ->whereDoesntHave('mantenimientos', fn ($q) => $q->whereNotIn('estado', Mantenimiento::ESTADOS_TERMINALES))
            ->orderBy('codigo_interno')
            ->get()
            ->mapWithKeys(fn ($e) => [
                $e->id => trim(($e->codigo_interno ?? "Equipo #{$e->id}") . ' — ' . ($e->categoria->nombre ?? '')),
            ]);
    }

    /** Equipo elegido, para mostrar el aviso de garantía. */
    #[Computed]
    public function equipoSeleccionado(): ?Equipo
    {
        return $this->equipo_id ? Equipo::find($this->equipo_id) : null;
    }

    protected function rules(): array
    {
        $reglas = [
            'empresa_id'         => 'required|exists:empresas,id',
            'equipo_id'          => 'required|exists:equipos,id',
            'tipo'               => 'required|in:Preventivo,Correctivo',
            'fotoAntes'          => 'required|image|max:5120',
            'fecha_inicio'       => 'required|date',
            'fecha_fin_estimada' => 'nullable|date|after_or_equal:fecha_inicio',
            'responsable_id'     => 'nullable|exists:users,id',
            'proveedor_externo'  => 'nullable|string|max:150',
            'descripcion'        => 'nullable|string|max:2000',
            'en_garantia'        => 'boolean',
        ];

        if ($this->tipo === 'Correctivo') {
            $reglas['falla_reportada'] = 'required|string|max:1000';
        } else {
            $reglas['frecuencia_meses']         = 'nullable|integer|min:1|max:60';
            $reglas['proxima_fecha_programada'] = 'nullable|date';
        }

        return $reglas;
    }

    protected function messages(): array
    {
        return [
            'equipo_id.required'        => 'Selecciona un equipo.',
            'falla_reportada.required'  => 'Describe la falla reportada.',
            'fotoAntes.required'        => 'La foto "Antes" es obligatoria para dejar evidencia del estado inicial del equipo.',
            'fotoAntes.image'           => 'El archivo debe ser una imagen.',
            'fecha_fin_estimada.after_or_equal' => 'La fecha estimada no puede ser anterior al inicio.',
        ];
    }

    public function guardar(): void
    {
        $this->authorize('create', Mantenimiento::class);
        $this->validate();

        try {
            // El archivo se guarda antes de la transacción: Storage no es
            // transaccional y no tiene sentido bloquear la fila mientras se
            // escribe a disco.
            $rutaFotoAntes = $this->fotoAntes->store('mantenimientos', 'public');

            $mantenimiento = DB::transaction(function () use ($rutaFotoAntes) {
                $equipo = Equipo::findOrFail($this->equipo_id);

                // Defensa adicional: si en el instante de guardar ya hay un caso
                // abierto para este equipo (carrera entre dos analistas), abortar.
                $yaAbierto = Mantenimiento::where('equipo_id', $equipo->id)->abiertos()->exists();
                if ($yaAbierto) {
                    throw new \App\Exceptions\ModuloBloqueadoException(
                        'Este equipo ya tiene un mantenimiento/reparación abierto. Actualiza la página e intenta de nuevo.'
                    );
                }

                $asignacionActiva = AsignacionItem::where('equipo_id', $equipo->id)
                    ->where('devuelto', false)
                    ->value('asignacion_id');

                $data = [
                    'empresa_id'         => $this->empresa_id,
                    'equipo_id'          => $equipo->id,
                    'asignacion_id'      => $asignacionActiva,
                    'tipo'               => $this->tipo,
                    'estado'             => $this->tipo === 'Preventivo' ? 'Programado' : 'Reportado',
                    'reportado_por_id'   => Auth::id(),
                    'responsable_id'     => $this->responsable_id ?: null,
                    'proveedor_externo'  => $this->proveedor_externo ?: null,
                    'fecha_inicio'       => $this->fecha_inicio,
                    'fecha_fin_estimada' => $this->fecha_fin_estimada ?: null,
                    'descripcion'        => $this->descripcion ?: null,
                    'en_garantia'        => $this->en_garantia,
                ];

                if ($this->tipo === 'Correctivo') {
                    $data['falla_reportada'] = $this->falla_reportada;
                } else {
                    $data['frecuencia_meses']         = $this->frecuencia_meses;
                    $data['proxima_fecha_programada'] = $this->proxima_fecha_programada ?: null;
                    $data['checklist'] = collect($this->checklist)
                        ->filter(fn ($item) => $item['incluir'] ?? false)
                        ->map(fn ($item) => ['tarea' => $item['tarea'], 'hecho' => false])
                        ->values()
                        ->toArray();
                }

                $mantenimiento = Mantenimiento::create($data);

                // Correctivo: bloquea ya (el equipo ya falló). Preventivo: NO
                // se bloquea acá, recién al avanzar a "En proceso".
                if ($this->tipo === 'Correctivo') {
                    $mantenimiento->bloquearEquipo();
                }

                $mantenimiento->evidencias()->create([
                    'ruta_archivo'  => $rutaFotoAntes,
                    'tipo'          => 'Antes',
                    'subido_por_id' => Auth::id(),
                ]);

                return $mantenimiento;
            });

            session()->flash('success', 'Mantenimiento registrado correctamente.');
            $this->redirect(route('admin.mantenimientos.show', $mantenimiento), navigate: true);

        } catch (\App\Exceptions\ModuloBloqueadoException $e) {
            $this->addError('general', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('CrearMantenimiento@guardar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar el mantenimiento. Por favor, inténtalo de nuevo.');
        }
    }

    public function render()
    {
        return view('livewire.mantenimientos.crear-mantenimiento');
    }
}
