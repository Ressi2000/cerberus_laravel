<?php

namespace App\Livewire\Equipos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\CriterioRenovacion;
use App\Services\RenovacionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Ajustar los criterios de renovación de cada tipo de equipo. Solo el
 * Administrador. Arranca con los valores sugeridos (CriterioRenovacion::VALORES_SUGERIDOS).
 */
class CriteriosRenovacionModal extends Component
{
    public bool $open = false;
    public string $categoriaId = '';

    /** @var array<string, mixed> los campos del criterio */
    public array $f = [];

    const SISTEMAS = ['XP', 'Vista', '7', '8', '8.1', '10', '11'];

    #[On('openCriteriosRenovacion')]
    public function abrir(?int $categoriaId = null): void
    {
        $this->autorizar();
        // Sin categoría elegida: la primera que se evalúa (Laptop, Desktop…), no la primera del abecedario.
        $categoriaId ??= CategoriaEquipo::orderBy('nombre')->get()
            ->first(fn ($cat) => CriterioRenovacion::paraCategoria($cat)->evaluar)?->id
            ?? CategoriaEquipo::orderBy('nombre')->value('id');
        $this->categoriaId = (string) $categoriaId;
        $this->cargar();
        $this->open = true;
    }

    public function updatedCategoriaId(): void
    {
        $this->cargar();
    }

    private function cargar(): void
    {
        $this->resetValidation();
        unset($this->categoria, $this->atributos, $this->detectados);
        $categoria = $this->categoria;
        if (! $categoria) {
            $this->f = [];
            return;
        }

        $c = CriterioRenovacion::paraCategoria($categoria);
        $this->f = [
            'evaluar'                 => (bool) $c->evaluar,
            'atributo_procesador_id'  => (string) ($c->atributo_procesador_id ?? ''),
            'atributo_ram_id'         => (string) ($c->atributo_ram_id ?? ''),
            'atributo_disco_id'       => (string) ($c->atributo_disco_id ?? ''),
            'atributo_so_id'          => (string) ($c->atributo_so_id ?? ''),
            'edad_revisar'            => $c->edad_revisar,
            'edad_obsoleto'           => $c->edad_obsoleto,
            'intel_generacion_minima' => $c->intel_generacion_minima,
            'ryzen_serie_minima'      => $c->ryzen_serie_minima,
            'gama_basica'             => $c->gama_basica,
            'ram_minima_gb'           => $c->ram_minima_gb,
            'ram_tipos_obsoletos'     => implode(', ', $c->ram_tipos_obsoletos ?? []),
            'requiere_ssd'            => (bool) $c->requiere_ssd,
            'disco_minimo_gb'         => $c->disco_minimo_gb,
            'reparaciones_max_12m'    => $c->reparaciones_max_12m,
            'so_sin_soporte'          => array_values($c->so_sin_soporte ?? []),
        ];
    }

    public function sugeridos(): void
    {
        $this->autorizar();
        foreach (CriterioRenovacion::VALORES_SUGERIDOS as $k => $v) {
            $this->f[$k] = match ($k) {
                'ram_tipos_obsoletos' => implode(', ', $v),
                default               => $v,
            };
        }
        $this->resetValidation();
    }

    #[Computed]
    public function categoria(): ?CategoriaEquipo
    {
        return $this->categoriaId ? CategoriaEquipo::find($this->categoriaId) : null;
    }

    #[Computed]
    public function categorias()
    {
        return CategoriaEquipo::orderBy('nombre')->pluck('nombre', 'id');
    }

    /** Características de la ficha de esta categoría, para elegir de dónde sale cada dato. */
    #[Computed]
    public function atributos()
    {
        return $this->categoria
            ? AtributoEquipo::where('categoria_id', $this->categoria->id)->orderBy('orden')->pluck('nombre', 'id')
            : collect();
    }

    /** Lo que se detecta solo, para mostrarlo en la opción "Detectar". */
    #[Computed]
    public function detectados(): array
    {
        if (! $this->categoria) {
            return [];
        }
        $vacio = new CriterioRenovacion(CriterioRenovacion::VALORES_SUGERIDOS);

        return collect(app(RenovacionService::class)->atributosDe($this->categoria, $vacio))
            ->map(fn ($a) => $a?->nombre)
            ->all();
    }

    public function guardar(): void
    {
        $this->autorizar();

        $this->validate([
            'categoriaId'               => 'required|exists:categorias_equipos,id',
            'f.edad_revisar'            => 'required|integer|min:1|max:30',
            'f.edad_obsoleto'           => 'required|integer|min:1|max:40|gte:f.edad_revisar',
            'f.intel_generacion_minima' => 'required|integer|min:1|max:15',
            'f.ryzen_serie_minima'      => 'required|integer|min:1|max:9',
            'f.gama_basica'             => 'required|in:' . implode(',', array_keys(CriterioRenovacion::GAMA_BASICA)),
            'f.ram_minima_gb'           => 'required|integer|min:1|max:4096',
            'f.disco_minimo_gb'         => 'required|integer|min:1|max:100000',
            'f.reparaciones_max_12m'    => 'required|integer|min:1|max:50',
            'f.so_sin_soporte'          => 'array',
            'f.so_sin_soporte.*'        => 'in:' . implode(',', self::SISTEMAS),
        ], [
            'f.edad_obsoleto.gte' => 'Tiene que ser igual o mayor que la edad para revisar.',
        ]);

        $ids = $this->atributos->keys()->map(fn ($id) => (string) $id)->all();
        $atributo = fn (string $k) => in_array($this->f[$k] ?? '', $ids, true) ? (int) $this->f[$k] : null;

        CriterioRenovacion::updateOrCreate(['categoria_id' => $this->categoria->id], [
            'evaluar'                 => (bool) $this->f['evaluar'],
            'atributo_procesador_id'  => $atributo('atributo_procesador_id'),
            'atributo_ram_id'         => $atributo('atributo_ram_id'),
            'atributo_disco_id'       => $atributo('atributo_disco_id'),
            'atributo_so_id'          => $atributo('atributo_so_id'),
            'edad_revisar'            => (int) $this->f['edad_revisar'],
            'edad_obsoleto'           => (int) $this->f['edad_obsoleto'],
            'intel_generacion_minima' => (int) $this->f['intel_generacion_minima'],
            'ryzen_serie_minima'      => (int) $this->f['ryzen_serie_minima'],
            'gama_basica'             => $this->f['gama_basica'],
            'ram_minima_gb'           => (int) $this->f['ram_minima_gb'],
            'ram_tipos_obsoletos'     => collect(explode(',', (string) $this->f['ram_tipos_obsoletos']))
                ->map(fn ($t) => strtoupper(trim($t)))->filter()->unique()->values()->all(),
            'requiere_ssd'            => (bool) $this->f['requiere_ssd'],
            'disco_minimo_gb'         => (int) $this->f['disco_minimo_gb'],
            'reparaciones_max_12m'    => (int) $this->f['reparaciones_max_12m'],
            'so_sin_soporte'          => array_values(array_intersect(self::SISTEMAS, $this->f['so_sin_soporte'] ?? [])),
            'actualizado_por'         => Auth::id(),
        ]);

        $this->dispatch('criteriosRenovacionGuardados');
        $this->dispatch('toast', type: 'success', message: "Criterios de «{$this->categoria->nombre}» guardados.");
        $this->open = false;
    }

    public function close(): void
    {
        $this->open = false;
    }

    private function autorizar(): void
    {
        abort_unless(Auth::user()?->hasRole('Administrador'), 403);
    }

    public function render()
    {
        return view('livewire.equipos.criterios-renovacion-modal');
    }
}
