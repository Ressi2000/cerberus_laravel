<?php

namespace App\Livewire\Prestamos;

use App\Models\Prestamo;
use App\Models\PrestamoItem;
use App\Models\User;
use App\Notifications\DevolucionPrestamoNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DevolverPrestamo extends Component
{
    public int $prestamoId;

    public array $seleccionados    = [];
    public array $observaciones    = [];
    public bool  $seleccionarTodos = false;

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(Prestamo $prestamo): void
    {
        $this->authorize('devolver', $prestamo);

        if ($prestamo->estado === 'Cerrado') {
            session()->flash('error', 'Este préstamo ya está cerrado y no admite devoluciones.');
            $this->redirect(route('admin.prestamos.index'), navigate: true);
            return;
        }

        $this->prestamoId = $prestamo->id;

        foreach ($this->todosLosItemsActivos as $item) {
            $this->observaciones[$item->id] = '';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function prestamo(): Prestamo
    {
        return Prestamo::with([
            'empresa', 'analista',
            'usuario.cargo', 'usuario.ubicacion',
            'areaEmpresa', 'areaDepartamento', 'areaResponsable.cargo',
        ])->findOrFail($this->prestamoId);
    }

    #[Computed]
    public function todosLosItemsActivos()
    {
        return PrestamoItem::with([
            'equipo.categoria',
            'padre.equipo',
            'hijosActivos.equipo.categoria',
        ])
            ->where('prestamo_id', $this->prestamoId)
            ->where('devuelto', false)
            ->orderByRaw('COALESCE(equipo_padre_id, id)')
            ->orderBy('equipo_padre_id')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function principalesConHuerfanos()
    {
        $selIds = collect($this->seleccionados)->map(fn ($id) => (int) $id);

        return $this->todosLosItemsActivos
            ->whereNull('equipo_padre_id')
            ->filter(fn ($item) => $selIds->contains($item->id))
            ->filter(fn ($item) =>
                $item->hijosActivos->isNotEmpty() &&
                $item->hijosActivos->contains(fn ($hijo) => ! $selIds->contains($hijo->id))
            );
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function updatedSeleccionarTodos(bool $value): void
    {
        $this->seleccionados = $value
            ? $this->todosLosItemsActivos->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    public function updatedSeleccionados(): void
    {
        $total = $this->todosLosItemsActivos->count();
        $this->seleccionarTodos = $total > 0 && count($this->seleccionados) === $total;
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->authorize('devolver', $this->prestamo);

        if (empty($this->seleccionados)) {
            $this->addError('seleccionados', 'Debes seleccionar al menos un equipo para devolver.');
            return;
        }

        $this->validate(
            collect($this->observaciones)->mapWithKeys(
                fn ($v, $k) => ["observaciones.{$k}" => 'nullable|string|max:1000']
            )->toArray()
        );

        try {
            DB::transaction(function () {
                // Periféricos primero: ver nota equivalente en DevolverAsignacion.
                $items = PrestamoItem::whereIn('id', $this->seleccionados)
                    ->where('prestamo_id', $this->prestamoId)
                    ->where('devuelto', false)
                    ->orderByRaw('equipo_padre_id IS NULL')
                    ->get();

                foreach ($items as $item) {
                    $item->registrarDevolucion($this->observaciones[$item->id] ?: null);
                }
            });

            $cant = count($this->seleccionados);
            session()->flash('success', "Devolución registrada correctamente. {$cant} equipo(s) liberado(s).");

            rescue(function () use ($cant) {
                $prestamo = Prestamo::with(['empresa', 'usuario', 'areaEmpresa', 'areaDepartamento'])->find($this->prestamoId);
                if (! $prestamo) return;
                $notif = new DevolucionPrestamoNotification($prestamo, auth()->user(), $cant);
                if ($prestamo->usuario_id) {
                    $prestamo->usuario?->notify($notif);
                }
                User::role('Administrador')->each(fn ($admin) => $admin->notify($notif));
            }, report: true);

            $this->redirect(route('admin.prestamos.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('DevolverPrestamo@confirmar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar la devolución.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.prestamos.devolver-prestamo');
    }
}
