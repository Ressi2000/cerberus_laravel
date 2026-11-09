<?php

namespace App\Livewire\Mantenimientos;

use App\Models\AsignacionItem;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Reportar problema encontrado": desde un mantenimiento Preventivo en
 * curso, el analista puede descubrir una falla real en el equipo. Este
 * modal CIERRA el Preventivo (completar()) y en el mismo paso crea un caso
 * Correctivo nuevo, enlazado al Preventivo que lo originó
 * (mantenimiento_origen_id), reutilizando todo el ciclo de
 * vida/seguimiento/evidencia que ya tiene Correctivo.
 *
 * Se cierra el Preventivo primero porque el sistema exige que un equipo
 * tenga como máximo UN caso abierto a la vez (ver CrearMantenimiento y el
 * comando del cronograma) — así el Correctivo bloquea el equipo desde su
 * propio estado "anterior" real (el que tenía antes del Preventivo), en
 * vez de encadenarse sobre un bloqueo que ya estaba activo.
 */
class ReportarProblemaModal extends Component
{
    public bool $open       = false;
    public ?int $origenId   = null;
    public string $falla_reportada = '';

    #[On('openReportarProblema')]
    public function abrir(int $mantenimientoId): void
    {
        $origen = Mantenimiento::findOrFail($mantenimientoId);
        $this->authorize('update', $origen);

        $this->origenId        = $origen->id;
        $this->falla_reportada = '';
        $this->resetValidation();
        $this->open = true;
    }

    protected function rules(): array
    {
        return [
            'falla_reportada' => 'required|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'falla_reportada.required' => 'Describe el problema que encontraste en el equipo.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $origen = Mantenimiento::findOrFail($this->origenId);
            $this->authorize('update', $origen);

            if (! $origen->esPreventivo() || $origen->estado !== 'En proceso') {
                $this->dispatch('toast', type: 'error', message: 'Solo se puede reportar un problema desde un mantenimiento preventivo en proceso.');
                return;
            }

            $correctivo = DB::transaction(function () use ($origen) {
                $asignacionActiva = AsignacionItem::where('equipo_id', $origen->equipo_id)
                    ->where('devuelto', false)
                    ->value('asignacion_id');

                $equipo    = $origen->equipo;
                $enGarantia = $equipo && $equipo->fecha_garantia_fin && $equipo->fecha_garantia_fin->isFuture();

                // Cierra el Preventivo (libera el equipo a su estado previo real)
                // antes de abrir el Correctivo, para no tener dos casos abiertos
                // a la vez sobre el mismo equipo.
                $origen->completar();

                $correctivo = Mantenimiento::create([
                    'empresa_id'              => $origen->empresa_id,
                    'equipo_id'               => $origen->equipo_id,
                    'asignacion_id'           => $asignacionActiva,
                    'mantenimiento_origen_id' => $origen->id,
                    'tipo'                    => Mantenimiento::TIPO_CORRECTIVO,
                    'estado'                  => 'Reportado',
                    'reportado_por_id'        => Auth::id(),
                    'fecha_inicio'            => now()->toDateString(),
                    'falla_reportada'         => $this->falla_reportada,
                    'en_garantia'             => $enGarantia,
                ]);

                $correctivo->bloquearEquipo();

                return $correctivo;
            });

            $this->close();
            session()->flash('success', "Problema reportado. Se creó el caso correctivo #{$correctivo->id}.");
            $this->redirect(route('admin.mantenimientos.show', $correctivo), navigate: true);
        } catch (\Exception $e) {
            Log::error('ReportarProblemaModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al reportar el problema.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['origenId', 'falla_reportada']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.mantenimientos.reportar-problema-modal');
    }
}
