<?php

namespace App\Livewire\Almacen;

use App\Models\AtributoEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Services\ExtraccionPiezaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * PiezaNuevaModal
 * ─────────────────────────────────────────────────────────────────────────────
 * Da de alta en Almacén una pieza NUEVA (comprada, nunca estuvo en ningún
 * equipo) que corresponde a un atributo de equipo conocido — RAM, disco,
 * cualquier cosa ya definida como atributo reutilizable en alguna categoría.
 * A diferencia de "Nuevo componente" (ComponenteModal, para cosas sin
 * atributo de equipo como bisagras), acá el formulario pide exactamente las
 * mismas características que ese atributo le pide a un equipo — para que la
 * pieza quede completa y, si más adelante se instala, SÍ pueda actualizar la
 * ficha técnica del equipo receptor (ver ExtraccionPiezaService::registrarNueva()).
 */
class PiezaNuevaModal extends Component
{
    public bool   $open       = false;
    public string $empresa_id = '';
    public ?int   $atributoId = null;
    public array  $valores    = [];
    public int    $cantidad   = 1;
    public string $observaciones = '';

    #[On('openPiezaNueva')]
    public function abrir(): void
    {
        $this->authorize('create', ComponenteAlmacen::class);

        $actor = Auth::user();
        $this->empresa_id = $actor->hasRole('Analista') ? (string) ($actor->empresa_activa_id ?? '') : '';
        $this->reset(['atributoId', 'valores', 'observaciones']);
        $this->cantidad = 1;
        $this->resetValidation();
        $this->open = true;
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

    /** Atributos reutilizables de TODAS las categorías — con la categoría en la etiqueta
     *  porque "RAM — Desktop" y "RAM — Laptop" pueden tener características distintas. */
    #[Computed]
    public function atributosOpciones()
    {
        return AtributoEquipo::reutilizables()
            ->with('categoria')
            ->orderBy('categoria_id')
            ->orderBy('orden')
            ->get()
            ->mapWithKeys(fn (AtributoEquipo $a) => [$a->id => "{$a->nombre} — {$a->categoria?->nombre}"]);
    }

    #[Computed]
    public function atributoSeleccionado(): ?AtributoEquipo
    {
        return $this->atributoId ? AtributoEquipo::find($this->atributoId) : null;
    }

    /** Cambiar de atributo invalida los valores que se hubieran llenado para el anterior. */
    public function updatedAtributoId(): void
    {
        $this->valores = [];
        $atributo = $this->atributoSeleccionado;

        if ($atributo?->esGrupo()) {
            foreach ($atributo->sub_campos ?? [] as $sc) {
                $this->valores[$sc['id']] = '';
            }
        } else {
            $this->valores['valor'] = '';
        }

        $this->resetValidation();
    }

    protected function rules(): array
    {
        $rules = [
            'empresa_id'    => 'required|exists:empresas,id',
            'atributoId'    => 'required|exists:atributos_equipos,id',
            'cantidad'      => 'required|integer|min:1|max:50',
            'observaciones' => 'nullable|string|max:500',
        ];

        $atributo = $this->atributoSeleccionado;

        if ($atributo?->esGrupo()) {
            foreach ($atributo->sub_campos ?? [] as $sc) {
                $rules["valores.{$sc['id']}"] = (! empty($sc['requerido']) ? 'required' : 'nullable') . '|string|max:255';
            }
        } elseif ($atributo) {
            $rules['valores.valor'] = 'required|string|max:255';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'atributoId.required' => 'Selecciona a cuál atributo corresponde esta pieza.',
            'valores.valor.required' => 'Indica el valor de esta característica.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        $atributo = $this->atributoSeleccionado;

        try {
            $piezas = app(ExtraccionPiezaService::class)->registrarNueva(
                (int) $this->empresa_id,
                $atributo,
                $this->valores,
                Auth::user(),
                cantidad: $this->cantidad,
                observaciones: $this->observaciones ?: null,
            );

            $this->close();
            $this->dispatch('componenteGuardado');
            $this->dispatch('stockActualizado');
            $this->dispatch('toast', type: 'success', message: "{$piezas->count()} unidad(es) de «{$atributo->nombre}» registrada(s) en Almacén.");
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Exception $e) {
            Log::error('PiezaNuevaModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al registrar la pieza.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['empresa_id', 'atributoId', 'valores', 'cantidad', 'observaciones']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.almacen.pieza-nueva-modal');
    }
}
