<?php

namespace App\Livewire\Cronograma;

use App\Models\PlanMantenimiento;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Vista de calendario de los eventos del cronograma: cada plan activo
 * aparece como un evento en su fecha_proximo. Un plan es un evento masivo
 * (aplica a todos los equipos activos de esa categoría/empresa), por eso
 * se muestra como UN evento por plan, no uno por equipo.
 *
 * Se usa tanto en la página de Cronograma (vista completa) como, en
 * versión compacta, en el Dashboard del Administrador.
 */
class CronogramaCalendario extends Component
{
    /** 'full' en Cronograma, 'compact' en el Dashboard (limita altura). */
    public string $modo = 'full';

    #[On('planGuardado')]
    #[On('planEliminado')]
    public function refrescar(): void
    {
        // El componente FullCalendar en el navegador vuelve a pedir los
        // eventos vía wire:click en el propio JS (ver Blade), no hace
        // falta re-renderizar el server aquí.
    }

    public function eventos(): array
    {
        $planes = PlanMantenimiento::visiblePara(Auth::user())
            ->activos()
            ->with(['categoria:id,nombre', 'empresa:id,nombre'])
            ->get();

        return $planes->map(function (PlanMantenimiento $plan) {
            $color = $plan->estaVencido() ? '#dc2626' : ($plan->estaProximo() ? '#d97706' : '#16a34a');
            $progreso = $plan->progresoLoteActual();
            $equiposCount = $plan->equiposAlcanzados()->count();

            $titulo = "{$plan->categoria->nombre} — {$plan->empresa->nombre}";
            if ($progreso) {
                $titulo .= " ({$progreso['completados']}/{$progreso['total']})";
            } else {
                $titulo .= " ({$equiposCount} equipo(s))";
            }

            return [
                'title' => $titulo,
                'start' => $plan->fecha_proximo->toDateString(),
                'color' => $color,
                'url'   => route('admin.cronograma.index'),
            ];
        })->values()->toArray();
    }

    public function render()
    {
        return view('livewire.cronograma.cronograma-calendario', [
            'eventos' => $this->eventos(),
        ]);
    }
}
