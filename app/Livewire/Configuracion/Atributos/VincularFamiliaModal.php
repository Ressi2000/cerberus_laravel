<?php

namespace App\Livewire\Configuracion\Atributos;

use App\Models\AtributoEquipo;
use App\Models\FamiliaComponente;
use App\Services\VinculacionFamiliaService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Asistente para que una característica que YA tiene datos en equipos (ej.
 * la "RAM (GB)" de Desktop) pase a usar la ficha de una familia (ej. RAM).
 *
 * Pasos: elegir la familia → decir de dónde sale cada campo de la familia
 * (un sub-campo actual, un valor fijo para todos, o vacío) → ver la vista
 * previa (antes → después, sin guardar) → confirmar. La conversión la hace
 * VinculacionFamiliaService en una sola transacción.
 */
class VincularFamiliaModal extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $atributoId = null;

    #[Locked]
    public ?int $categoriaId = null;

    public ?int $familiaId = null;

    /**
     * Lo que se elige en pantalla, por campo de la familia:
     *   fuente: "sub:{id del sub-campo}" | "fijo" | "vacio";  valor: solo con "fijo".
     *
     * @var array<string, array{fuente:string, valor:string}>
     */
    public array $mapeo = [];

    public ?array $vistaPrevia = null;

    #[On('openVincularFamilia')]
    public function abrir(int $atributoId, ?int $categoriaId = null): void
    {
        $this->autorizar();

        $atributo = AtributoEquipo::findOrFail($atributoId);

        $this->atributoId  = $atributo->id;
        $this->categoriaId = $categoriaId ?? $atributo->categoria_id;
        $this->familiaId   = null;
        $this->mapeo       = [];
        $this->vistaPrevia = null;
        $this->resetValidation();
        $this->open = true;

        // Si solo hay una familia, se elige sola.
        if ($this->familias->count() === 1) {
            $this->familiaId = $this->familias->first()->id;
            $this->updatedFamiliaId();
        }
    }

    #[Computed]
    public function atributo(): ?AtributoEquipo
    {
        return $this->atributoId ? AtributoEquipo::with('categoria')->find($this->atributoId) : null;
    }

    #[Computed]
    public function familias()
    {
        return FamiliaComponente::activas()->whereJsonLength('campos', '>', 0)->orderBy('nombre')->get();
    }

    #[Computed]
    public function familia(): ?FamiliaComponente
    {
        return $this->familiaId ? FamiliaComponente::find($this->familiaId) : null;
    }

    /** Sub-campos actuales de la característica, para elegir el origen de cada campo. */
    #[Computed]
    public function subCampos(): array
    {
        return $this->atributo ? app(VinculacionFamiliaService::class)->subCamposPropios($this->atributo) : [];
    }

    public function updatedFamiliaId(): void
    {
        unset($this->familia);
        $this->vistaPrevia = null;
        $this->resetValidation();

        $sugerido = $this->familia && $this->atributo
            ? app(VinculacionFamiliaService::class)->mapeoSugerido($this->atributo, $this->familia)
            : [];

        $this->mapeo = collect($sugerido)
            ->map(fn ($regla) => [
                'fuente' => $regla['origen'] === VinculacionFamiliaService::ORIGEN_SUB_CAMPO ? 'sub:' . $regla['sub_campo_id'] : 'vacio',
                'valor'  => '',
            ])
            ->all();
    }

    /** Cualquier cambio en el mapeo invalida la vista previa anterior. */
    public function updatedMapeo(): void
    {
        $this->vistaPrevia = null;
    }

    public function previsualizar(): void
    {
        $this->autorizar();

        if (! $this->familia || ! $this->atributo) {
            $this->addError('familiaId', 'Elige una familia.');
            return;
        }

        try {
            $this->vistaPrevia = app(VinculacionFamiliaService::class)
                ->vistaPrevia($this->atributo, $this->familia, $this->mapeoLimpio());
        } catch (\InvalidArgumentException $e) {
            $this->vistaPrevia = null;
            $this->addError('mapeo', $e->getMessage());
        }
    }

    public function confirmar(): void
    {
        $this->autorizar();

        if ($this->vistaPrevia === null) {
            $this->addError('mapeo', 'Revisa la vista previa antes de confirmar.');
            return;
        }

        try {
            $resultado = app(VinculacionFamiliaService::class)
                ->vincular($this->atributo, $this->familia, $this->mapeoLimpio());

            $this->dispatch('toast', type: 'success', message:
                "«{$this->atributo->nombre}» ahora usa la ficha de «{$this->familia->nombre}». "
                . "Se convirtieron {$resultado['instancias']} registro(s) de equipos y {$resultado['piezas']} pieza(s).");
            $this->dispatch('atributoGuardado');

            $categoriaId = $this->categoriaId;
            $this->close();
            $this->dispatch('openAtributosEditor', categoriaId: $categoriaId);
        } catch (\InvalidArgumentException $e) {
            $this->addError('mapeo', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('VincularFamiliaModal@confirmar: ' . $e->getMessage());
            $this->addError('mapeo', 'Ocurrió un error y no se convirtió nada. Inténtalo de nuevo.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['atributoId', 'categoriaId', 'familiaId', 'mapeo', 'vistaPrevia']);
        $this->resetValidation();
    }

    /** Normaliza lo que llega del formulario al formato del servicio. */
    private function mapeoLimpio(): array
    {
        return collect($this->mapeo)
            ->map(function ($regla) {
                $fuente = (string) ($regla['fuente'] ?? 'vacio');

                if (str_starts_with($fuente, 'sub:')) {
                    return ['origen' => VinculacionFamiliaService::ORIGEN_SUB_CAMPO, 'sub_campo_id' => substr($fuente, 4)];
                }

                if ($fuente === 'fijo') {
                    return ['origen' => VinculacionFamiliaService::ORIGEN_FIJO, 'valor' => trim((string) ($regla['valor'] ?? '')) ?: null];
                }

                return ['origen' => VinculacionFamiliaService::ORIGEN_VACIO];
            })
            ->all();
    }

    private function autorizar(): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);
    }

    public function render()
    {
        return view('livewire.configuracion.atributos.vincular-familia-modal');
    }
}
