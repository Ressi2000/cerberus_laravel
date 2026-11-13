<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\FamiliaComponente;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ComponenteModal extends Component
{
    public bool   $open         = false;
    public ?int   $componenteId = null;
    public string $empresa_id   = '';
    public string $nombre       = '';
    public string $descripcion  = '';
    public string $unidad       = 'unidad';
    /** Familia a la que pertenece (obligatoria): RAM, Discos, Bisagras… */
    public string $familia_id   = '';
    /** Ficha técnica (familias con ficha): el nombre de la variante sale de aquí. */
    public array  $valores      = [];

    #[On('openComponenteCrear')]
    public function abrirCrear(?int $familiaId = null): void
    {
        $this->reset(['componenteId', 'nombre', 'descripcion', 'valores']);
        $this->familia_id = $familiaId ? (string) $familiaId : '';
        $this->unidad = 'unidad';
        $actor = Auth::user();
        $this->empresa_id = $actor->hasRole('Analista') ? (string) ($actor->empresa_activa_id ?? '') : '';
        $this->resetValidation();
        $this->authorize('create', ComponenteAlmacen::class);
        $this->open = true;
    }

    #[On('openComponenteEditar')]
    public function abrirEditar(int $id): void
    {
        $c = ComponenteAlmacen::findOrFail($id);
        $this->authorize('update', $c);

        $this->componenteId = $c->id;
        $this->empresa_id   = (string) $c->empresa_id;
        $this->nombre       = $c->nombre;
        $this->descripcion  = $c->descripcion ?? '';
        $this->unidad       = $c->unidad;
        $this->familia_id   = (string) ($c->familia_id ?? '');
        $this->valores      = $c->valoresFicha();
        $this->resetValidation();
        $this->open = true;
    }

    #[Computed]
    public function familia(): ?FamiliaComponente
    {
        return $this->familia_id ? FamiliaComponente::find($this->familia_id) : null;
    }

    /**
     * La familia tiene ficha y alguna categoría la usa: la variante se
     * define llenando la ficha (el nombre sale solo), igual que al
     * registrar una entrada. Así no quedan variantes con nombre libre
     * ("RAM PRUEBA") que después no coinciden con la ficha.
     */
    #[Computed]
    public function usaFicha(): bool
    {
        return (bool) ($this->familia?->tieneFicha() && $this->familia->atributoRepresentativo());
    }

    /** Nombre que tendrá la variante según su ficha. */
    #[Computed]
    public function nombreFicha(): ?string
    {
        if (! $this->usaFicha || collect($this->valores)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
            return null;
        }

        return $this->familia->atributoRepresentativo()->describirValor($this->valoresLimpios());
    }

    private function valoresLimpios(): array
    {
        return collect($this->familia?->campos ?? [])
            ->mapWithKeys(fn ($c) => [$c['id'] => trim((string) ($this->valores[$c['id']] ?? '')) ?: null])
            ->all();
    }

    public function updatedFamiliaId(): void
    {
        unset($this->familia, $this->usaFicha, $this->nombreFicha);
        $this->valores = [];
    }

    /**
     * Componentes de esta empresa cuyo nombre se parece a lo que se está
     * escribiendo — para que, al teclear "RAM", aparezca de inmediato "ya
     * existe RAM (8GB)" y la persona pueda usarlo en vez de crear un
     * duplicado por un typo ("RAMM", "Ram", etc.). No bloquea nombres
     * nuevos, solo los sugiere — ver usarExistente().
     */
    #[Computed]
    public function coincidencias()
    {
        if ($this->usaFicha || ! $this->empresa_id || mb_strlen(trim($this->nombre)) < 2) {
            return collect();
        }

        return ComponenteAlmacen::where('empresa_id', $this->empresa_id)
            ->where('nombre', 'like', '%' . trim($this->nombre) . '%')
            ->when($this->componenteId, fn ($q) => $q->where('id', '!=', $this->componenteId))
            ->orderBy('nombre')
            ->limit(5)
            ->get();
    }

    /**
     * En vez de crear un componente nuevo, usa uno que ya existe: cierra
     * este formulario y abre directo "Registrar movimiento de stock" sobre
     * ese componente — fusiona los dos pasos (crear el bucket / agregar
     * unidades) en un solo flujo guiado, en vez de dejar que la persona
     * tenga que descubrir por su cuenta que ambos pasos existen.
     */
    public function usarExistente(int $id): void
    {
        $this->close();
        $this->dispatch('openRegistrarEntrada', componenteId: $id);
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

    /** Familias activas para elegir; incluye la actual aunque esté desactivada. */
    #[Computed]
    public function familiasOpciones()
    {
        return FamiliaComponente::where('activo', true)
            ->when($this->familia_id, fn ($q) => $q->orWhere('id', $this->familia_id))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'control']);
    }

    /** Aviso: al pasar a una familia a granel, las piezas en almacén pasan a contarse por cantidad. */
    #[Computed]
    public function piezasQueSeAgrupan(): int
    {
        if (! $this->componenteId || ! $this->familia_id) {
            return 0;
        }

        $familia = $this->familiasOpciones->firstWhere('id', (int) $this->familia_id);

        return $familia && $familia->control === FamiliaComponente::CONTROL_GRANEL
            ? (ComponenteAlmacen::find($this->componenteId)?->piezasEnAlmacen()->count() ?? 0)
            : 0;
    }

    protected function rules(): array
    {
        $rules = [
            'familia_id'  => 'required|exists:familias_componentes,id',
            'empresa_id'  => 'required|exists:empresas,id',
            'nombre'      => $this->usaFicha ? 'nullable' : 'required|string|max:150',
            'descripcion' => 'nullable|string|max:500',
            'unidad'      => 'required|string|max:30',
        ];

        if ($this->usaFicha) {
            foreach ($this->familia->campos as $campo) {
                $rules['valores.' . $campo['id']] = 'required|string|max:100';
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'empresa_id.required' => 'Debes seleccionar una empresa.',
            'familia_id.required' => 'Elige la familia (ej. RAM, Bisagras). Si no encaja en ninguna, usa «Otros».',
            'nombre.required'     => 'El nombre es obligatorio.',
            'valores.*.required'  => 'Completa este campo de la ficha.',
        ];
    }

    public function guardar(): void
    {
        $componente = null;

        if ($this->componenteId) {
            $componente = ComponenteAlmacen::findOrFail($this->componenteId);
            $this->authorize('update', $componente);

            // La empresa de un componente no cambia al editarlo: moverlo
            // de empresa se llevaría su stock y sus piezas trazadas.
            $this->empresa_id = (string) $componente->empresa_id;
        } else {
            $this->authorize('create', ComponenteAlmacen::class);

            // El Analista solo crea componentes en su empresa activa, sin
            // importar lo que llegue en empresa_id desde el navegador.
            $actor = Auth::user();
            if (! $actor->hasRole('Administrador')) {
                $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
            }
        }

        $this->validate();

        if ($this->usaFicha) {
            $this->nombre = $this->nombreFicha;

            // La ficha define la variante: no puede haber dos con la misma.
            $otra = ComponenteAlmacen::withTrashed()
                ->where('empresa_id', $this->empresa_id)
                ->where('nombre', $this->nombre)
                ->when($this->componenteId, fn ($q) => $q->whereKeyNot($this->componenteId))
                ->first();
            if ($otra) {
                $this->addError('valores', "Ya existe la variante «{$this->nombre}» en esta empresa: usa esa.");
                return;
            }
        }

        try {
            $data = [
                'nombre'      => trim($this->nombre),
                'descripcion' => trim($this->descripcion) ?: null,
                'unidad'      => trim($this->unidad) ?: 'unidad',
            ];

            $familia = FamiliaComponente::findOrFail($this->familia_id);

            if ($componente) {
                $componente->update($data);

                // Con ficha: las piezas que están en el almacén pasan a tener
                // esa ficha (y quedan ligadas a la familia para poder instalarse).
                if ($this->usaFicha) {
                    $componente->piezasEnAlmacen()->update([
                        'atributo_id'    => $familia->atributoRepresentativo()->id,
                        'valor_extraido' => json_encode($this->valoresLimpios()),
                    ]);
                }
                $msg = "Componente «{$this->nombre}» actualizado.";

                // Cambio de familia: si es a granel, las piezas en almacén
                // pasan a contarse solo por cantidad.
                if ((int) $componente->familia_id !== (int) $familia->id) {
                    $agrupadas = $componente->asignarFamilia($familia, Auth::user());
                    if ($agrupadas > 0) {
                        $msg .= " {$agrupadas} pieza(s) en almacén pasaron a contarse solo por cantidad.";
                    }
                }
            } else {
                ComponenteAlmacen::create(array_merge($data, [
                    'empresa_id'  => $this->empresa_id,
                    'familia_id'  => $familia->id,
                    'activo'      => true,
                    'creado_por'  => Auth::id(),
                ]));
                $msg = "Componente «{$this->nombre}» creado.";
            }

            $this->close();
            $this->dispatch('componenteGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('ComponenteModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el componente.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['componenteId', 'empresa_id', 'nombre', 'descripcion', 'unidad', 'valores']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.almacen.componente-modal');
    }
}
