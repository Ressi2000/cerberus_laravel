<?php

namespace App\Livewire\Equipos;

use Livewire\Component;
use App\Models\Equipo;
use App\Models\Equipos;

class HistorialEquipo extends Component
{
    public Equipos $equipo;

    public $historial = [];

    public function mount(Equipos $equipo)
    {
        $this->authorize('view', $equipo);

        $this->equipo = $equipo->load([
            'categoria',
            'atributosHistorico.atributo',
            'atributosHistorico' => function ($query) {
                $query->with('usuario')->orderByDesc('created_at');
            }
        ]);


        $this->organizarHistorial();
    }

    private function organizarHistorial()
    {
        $agrupado = $this->equipo->atributosHistorico
            ->sortByDesc('created_at')
            ->groupBy('atributo_id');

        $this->historial = $agrupado;
    }

    public function render()
    {
        return view('livewire.equipos.historial-equipo');
    }
}
