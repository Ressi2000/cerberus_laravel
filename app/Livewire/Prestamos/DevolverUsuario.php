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

/**
 * DevolverUsuario (Préstamos)
 *
 * Análogo a App\Livewire\Asignaciones\DevolverUsuario — devolución unificada
 * por usuario, mostrando todos los items activos (Activo|Vencido) con
 * checkbox individual, agrupados por préstamo.
 */
class DevolverUsuario extends Component
{
    public int $usuarioId;

    public array $seleccionados    = [];
    public array $observaciones    = [];
    public bool  $seleccionarTodos = false;

    public function mount(User $usuario): void
    {
        $this->authorize('viewAny', Prestamo::class);
        $this->usuarioId = $usuario->id;

        foreach ($this->todosLosItemsActivos as $item) {
            $this->observaciones[$item->id] = '';
        }
    }

    #[Computed]
    public function usuario(): User
    {
        return User::with(['cargo', 'empresaNomina', 'ubicacion'])->findOrFail($this->usuarioId);
    }

    #[Computed]
    public function todosLosItemsActivos()
    {
        return PrestamoItem::with([
            'equipo.categoria',
            'prestamo',
            'padre.equipo',
            'prestamo.empresa',
            'hijosActivos.equipo.categoria',
        ])
            ->whereHas('prestamo', fn ($q) =>
                $q->where('usuario_id', $this->usuarioId)->whereIn('estado', ['Activo', 'Vencido'])
            )
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

    public function confirmar(): void
    {
        $this->authorize('viewAny', Prestamo::class);

        if (empty($this->seleccionados)) {
            $this->addError('seleccionados', 'Selecciona al menos un equipo para devolver.');
            return;
        }

        $this->validate(
            collect($this->observaciones)->mapWithKeys(
                fn ($v, $k) => ["observaciones.{$k}" => 'nullable|string|max:1000']
            )->toArray()
        );

        try {
            $prestamosAfectados = [];

            DB::transaction(function () use (&$prestamosAfectados) {
                // Periféricos primero: ver nota equivalente en DevolverPrestamo.
                $items = PrestamoItem::whereIn('id', $this->seleccionados)
                    ->whereHas('prestamo', fn ($q) => $q->where('usuario_id', $this->usuarioId))
                    ->where('devuelto', false)
                    ->orderByRaw('equipo_padre_id IS NULL')
                    ->get();

                foreach ($items as $item) {
                    $item->registrarDevolucion($this->observaciones[$item->id] ?: null);
                    $prestamosAfectados[$item->prestamo_id] = ($prestamosAfectados[$item->prestamo_id] ?? 0) + 1;
                }
            });

            $cant = count($this->seleccionados);
            session()->flash('success', "Devolución registrada. {$cant} equipo(s) liberado(s).");

            rescue(function () use ($prestamosAfectados) {
                foreach ($prestamosAfectados as $prestamoId => $cantPorPrestamo) {
                    $prestamo = Prestamo::with(['empresa', 'usuario', 'areaEmpresa', 'areaDepartamento'])->find($prestamoId);
                    if (! $prestamo) continue;
                    $notif = new DevolucionPrestamoNotification($prestamo, auth()->user(), $cantPorPrestamo);
                    if ($prestamo->usuario_id) {
                        $prestamo->usuario?->notify($notif);
                    }
                    User::role('Administrador')->each(fn ($admin) => $admin->notify($notif));
                }
            }, report: true);

            $this->redirect(route('admin.prestamos.historial', $this->usuarioId), navigate: true);

        } catch (\Exception $e) {
            Log::error('DevolverUsuario@confirmar (Préstamos): ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar la devolución.');
        }
    }

    public function render()
    {
        return view('livewire.prestamos.devolver-usuario');
    }
}
