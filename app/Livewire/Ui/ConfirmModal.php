<?php

namespace App\Livewire\Ui;

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * ConfirmModal — modal de confirmación genérico y reutilizable.
 *
 * Reemplaza wire:confirm (window.confirm() nativo del navegador) en todo
 * Cerberus por un modal propio, estilizado y consistente con el resto del
 * sistema (mismo look que EmpresaDeleteModal/DepartamentoDeleteModal, etc.).
 *
 * Cualquier botón que antes disparaba la acción directo + wire:confirm ahora
 * dispara el evento 'confirmar' con los datos del mensaje y, al confirmar,
 * este componente reenvía la acción ORIGINAL ($accionEvento) al componente
 * que la necesita — ese componente solo necesita escucharla con #[On(...)],
 * sin cambiar en nada su lógica de negocio existente.
 *
 * Uso en Blade (reemplaza wire:click="metodo(1)" wire:confirm="¿Seguro?"):
 *
 *   <button wire:click="$dispatch('confirmar', {
 *       mensaje: '¿Eliminar el plan X?',
 *       accionEvento: 'planEliminarConfirmado',
 *       accionParams: [1],
 *   })">Eliminar</button>
 *
 * Y en el componente que hace el trabajo real:
 *
 *   #[On('planEliminarConfirmado')]
 *   public function eliminar(int $id): void { ... } // sin cambios
 */
class ConfirmModal extends Component
{
    public bool $open = false;

    public string $titulo       = '¿Confirmar?';
    public string $mensaje      = '';
    public string $confirmLabel = 'Confirmar';
    public string $cancelLabel  = 'Cancelar';

    /** 'danger' (rojo, acciones destructivas) | 'warning' (ámbar) | 'primary' (azul, acciones no destructivas). */
    public string $variant = 'danger';

    /** Evento Livewire a reenviar cuando se confirma, con sus parámetros. */
    public ?string $accionEvento  = null;
    public array   $accionParams  = [];

    #[On('confirmar')]
    public function abrir(
        string $mensaje,
        string $accionEvento,
        array $accionParams = [],
        string $titulo = '¿Confirmar?',
        string $confirmLabel = 'Confirmar',
        string $cancelLabel = 'Cancelar',
        string $variant = 'danger',
    ): void {
        $this->mensaje       = $mensaje;
        $this->accionEvento  = $accionEvento;
        $this->accionParams  = $accionParams;
        $this->titulo        = $titulo;
        $this->confirmLabel  = $confirmLabel;
        $this->cancelLabel   = $cancelLabel;
        $this->variant       = $variant;
        $this->open          = true;
    }

    public function confirmar(): void
    {
        if ($this->accionEvento) {
            $this->dispatch($this->accionEvento, ...$this->accionParams);
        }

        $this->cerrar();
    }

    public function cerrar(): void
    {
        $this->open = false;
        $this->reset(['titulo', 'mensaje', 'confirmLabel', 'cancelLabel', 'variant', 'accionEvento', 'accionParams']);
    }

    public function render()
    {
        return view('livewire.ui.confirm-modal');
    }
}
