<?php

namespace App\Livewire\Mantenimientos;

use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\FamiliaComponente;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Services\ExtraccionPiezaService;
use App\Services\SustitucionPiezaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Usar del almacén" — la ÚNICA forma de usar algo del almacén en un caso.
 *
 *   1. Familia y variante.
 *   2. Por unidad: se elige CUÁL pieza (por su serial). A granel: cuántas.
 *   3. Si la pieza es de una familia con ficha que el equipo usa (RAM), se
 *      ve la ficha del equipo antes → después y se elige agregar o
 *      reemplazar; la que sale queda registrada (Almacén o Depósito).
 *      Si no (pantalla, bisagra), solo se descuenta y queda anotado en el caso.
 *   4. Sin stock: queda como solicitud pendiente.
 *
 * Reemplaza a "Pedir componente" y a "Instalar pieza rescatada".
 */
class UsarDelAlmacenModal extends Component
{
    const SIN_SERIAL = 'sin-serial';

    public bool $open = false;

    #[Locked]
    public ?int $mantenimientoId = null;

    public string $familiaId    = '';
    /** Id de la variante, o "nueva" para solicitar una que no está en el almacén. */
    public string $componenteId = '';
    public string $nuevoNombre  = '';
    public array  $valores      = [];

    /** Id de la pieza elegida, o "sin-serial" para usar unidades antiguas sin identidad. */
    public string $piezaId  = '';
    public ?int   $cantidad = 1;

    // Ficha del equipo (familias con ficha que el equipo usa)
    public string $modo              = SustitucionPiezaService::MODO_AGREGAR;
    public string $instanciaId       = '';
    public string $destinoSaliente   = PiezaExtraida::ESTADO_EN_ALMACEN;
    public string $depositoSaliente  = '';
    /** Serial de la pieza que sale al reemplazar (opcional: si se conoce). */
    public string $serialSaliente    = '';

    #[On('openUsarDelAlmacen')]
    public function abrir(int $mantenimientoId): void
    {
        $m = Mantenimiento::findOrFail($mantenimientoId);
        $this->authorize('update', $m);

        $this->mantenimientoId = $m->id;
        $this->reset(['familiaId', 'componenteId', 'nuevoNombre', 'valores', 'piezaId', 'instanciaId', 'depositoSaliente', 'serialSaliente']);
        $this->cantidad = 1;
        $this->modo = SustitucionPiezaService::MODO_AGREGAR;
        $this->destinoSaliente = PiezaExtraida::ESTADO_EN_ALMACEN;
        $this->resetValidation();
        $this->open = true;
    }

    // ── Datos ────────────────────────────────────────────────────────────────

    #[Computed]
    public function mantenimiento(): ?Mantenimiento
    {
        return $this->mantenimientoId ? Mantenimiento::with('equipo')->find($this->mantenimientoId) : null;
    }

    #[Computed]
    public function familias()
    {
        return FamiliaComponente::activas()->orderBy('nombre')->get();
    }

    #[Computed]
    public function familia(): ?FamiliaComponente
    {
        return $this->familiaId ? FamiliaComponente::find($this->familiaId) : null;
    }

    #[Computed]
    public function variantes()
    {
        if (! $this->familia || ! $this->mantenimiento) {
            return collect();
        }

        return ComponenteAlmacen::where('familia_id', $this->familia->id)
            ->where('empresa_id', $this->mantenimiento->empresa_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    #[Computed]
    public function componente(): ?ComponenteAlmacen
    {
        return ctype_digit($this->componenteId) ? $this->variantes->firstWhere('id', (int) $this->componenteId) : null;
    }

    /** Piezas con serial disponibles de la variante (familias por unidad). */
    #[Computed]
    public function piezas()
    {
        if (! $this->componente || $this->componente->esAGranel()) {
            return collect();
        }

        return $this->componente->piezasEnAlmacen()->with('equipoOrigen')->oldest()->get();
    }

    #[Computed]
    public function pieza(): ?PiezaExtraida
    {
        return ctype_digit($this->piezaId) ? $this->piezas->firstWhere('id', (int) $this->piezaId) : null;
    }

    /** Característica del equipo donde entra la pieza (si es de una familia con ficha que el equipo usa). */
    #[Computed]
    public function atributoDestino()
    {
        return $this->pieza && $this->pieza->atributo_id
            ? app(SustitucionPiezaService::class)->atributoDestino($this->pieza, $this->mantenimiento->equipo)
            : null;
    }

    /** Ficha actual del equipo en esa característica. */
    #[Computed]
    public function fichaActual()
    {
        if (! $this->atributoDestino) {
            return collect();
        }

        return EquipoAtributoGrupoInstancia::where('equipo_id', $this->mantenimiento->equipo_id)
            ->where('atributo_id', $this->atributoDestino->id)
            ->where('es_actual', true)
            ->orderBy('orden')
            ->get();
    }

    #[Computed]
    public function depositos()
    {
        return $this->mantenimiento
            ? Deposito::where('empresa_id', $this->mantenimiento->empresa_id)->activos()->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
    }

    /** ¿Lo que se va a usar se cuenta por cantidad? (a granel, o unidades antiguas sin serial). */
    #[Computed]
    public function porCantidad(): bool
    {
        return $this->componente && ($this->componente->esAGranel() || $this->piezaId === self::SIN_SERIAL);
    }

    public function updatedFamiliaId(): void
    {
        $this->reset(['componenteId', 'nuevoNombre', 'valores', 'piezaId', 'instanciaId']);
        $this->resetValidation();
    }

    public function updatedComponenteId(): void
    {
        $this->reset(['piezaId', 'instanciaId']);
        $this->cantidad = 1;
        $this->resetValidation();
    }

    public function updatedPiezaId(): void
    {
        $this->instanciaId = '';
        $this->modo = SustitucionPiezaService::MODO_AGREGAR;
        $this->resetValidation();
    }

    // ── Acciones ─────────────────────────────────────────────────────────────

    public function usar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->permiteRegistrarTrabajo()) {
            $this->addError('general', 'Solo se puede usar algo del almacén mientras el caso está en trabajo.');
            return;
        }

        $componente = $this->componente;
        if (! $componente) {
            $this->addError('componenteId', 'Elige la variante.');
            return;
        }

        try {
            if ($this->porCantidad) {
                $this->validate(['cantidad' => 'required|integer|min:1|max:500']);
                $item = $m->pedirComponente($componente, $this->cantidad, Auth::user());
                $msg = $item->estado === 'Entregado'
                    ? "Se usaron {$this->cantidad} de «{$componente->nombre}»."
                    : "No había suficiente: quedó como solicitud pendiente.";
            } else {
                $pieza = $this->pieza;
                if (! $pieza) {
                    $this->addError('piezaId', 'Elige cuál pieza (por su serial).');
                    return;
                }

                if ($this->atributoDestino) {
                    $deposito = null;
                    if ($this->modo === SustitucionPiezaService::MODO_SUSTITUIR && $this->destinoSaliente === PiezaExtraida::ESTADO_EN_DEPOSITO) {
                        $deposito = Deposito::where('empresa_id', $m->empresa_id)->activos()->find($this->depositoSaliente);
                        if (! $deposito) {
                            $this->addError('depositoSaliente', 'Elige a qué depósito va la pieza que sale.');
                            return;
                        }
                    }

                    app(SustitucionPiezaService::class)->instalar(
                        $pieza, $m->equipo, $this->modo, Auth::user(), $m,
                        $this->instanciaId !== '' ? (int) $this->instanciaId : null,
                        $this->destinoSaliente, $deposito,
                        mb_substr(trim($this->serialSaliente), 0, 100) ?: null,
                    );
                    $msg = "«{$pieza->descripcion()}» instalada y la ficha del equipo actualizada.";
                } else {
                    $m->usarPieza($pieza, Auth::user());
                    $msg = "«{$pieza->descripcion()}» quedó en el equipo.";
                }
            }

            $this->terminar($msg);
        } catch (\InvalidArgumentException $e) {
            $this->addError('general', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('UsarDelAlmacenModal@usar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error y no se registró nada.');
        }
    }

    /**
     * Pide al almacén algo que no hay (o una variante que no existe): queda
     * como solicitud pendiente y aparece en la bandeja del almacén.
     */
    public function solicitar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);
        $this->validate(['cantidad' => 'required|integer|min:1|max:500']);

        $familia = $this->familia;
        if (! $familia) {
            $this->addError('familiaId', 'Elige la familia.');
            return;
        }

        try {
            $componente = DB::transaction(function () use ($m, $familia) {
                if ($this->componenteId !== 'nueva') {
                    return $this->componente ?? throw new \InvalidArgumentException('Elige la variante.');
                }

                $atributo = $familia->tieneFicha() ? $familia->atributoRepresentativo() : null;

                if ($atributo) {
                    $valores = collect($familia->campos)->mapWithKeys(fn ($c) => [$c['id'] => trim((string) ($this->valores[$c['id']] ?? '')) ?: null]);
                    if ($valores->filter()->isEmpty()) {
                        throw new \InvalidArgumentException('Llena la ficha de la variante que necesitas.');
                    }

                    return app(ExtraccionPiezaService::class)->varianteDeFicha($m->empresa_id, $atributo, $valores->all(), Auth::user());
                }

                if (trim($this->nuevoNombre) === '') {
                    throw new \InvalidArgumentException('Escribe el nombre de lo que necesitas.');
                }

                $this->authorize('create', ComponenteAlmacen::class);

                return ComponenteAlmacen::create([
                    'empresa_id'   => $m->empresa_id,
                    'familia_id'   => $familia->id,
                    'nombre'       => trim($this->nuevoNombre),
                    'unidad'       => 'unidad',
                    'stock_actual' => 0,
                    'activo'       => true,
                    'creado_por'   => Auth::id(),
                ]);
            });

            $item = $m->pedirComponente($componente, $this->cantidad, Auth::user());

            $this->terminar($item->estado === 'Entregado'
                ? "Había stock: se usaron {$this->cantidad} de «{$componente->nombre}»."
                : "Solicitado: «{$componente->nombre}» quedó pendiente en el almacén.");
        } catch (\InvalidArgumentException $e) {
            $this->addError('general', $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('UsarDelAlmacenModal@solicitar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error y no se registró nada.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetValidation();
    }

    private function terminar(string $mensaje): void
    {
        $this->close();
        $this->dispatch('toast', type: 'success', message: $mensaje);
        $this->dispatch('componenteRefrescar');
        $this->dispatch('mantenimientoActualizado');
    }

    public function render()
    {
        return view('livewire.mantenimientos.usar-del-almacen-modal');
    }
}
