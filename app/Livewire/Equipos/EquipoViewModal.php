<?php

namespace App\Livewire\Equipos;

use App\Models\AsignacionItem;
use App\Models\Equipo;
use App\Models\EquipoAtributoValor;
use Livewire\Component;
use Livewire\Attributes\On;

class EquipoViewModal extends Component
{
    public bool $open = false;
    public ?Equipo $equipo = null;
    public ?AsignacionItem $asignacionActiva = null;

    // Historial de atributos agrupado por atributo
    public array $historial = [];

    #[On('openEquipoView')]
    public function openEquipoView(int $id): void
    {
        $this->equipo = Equipo::with([
            'categoria',
            'estado',
            'ubicacion',
            'atributosActuales.atributo',
        ])->findOrFail($id);

        // ── Cargar asignación activa (si existe) ──────────────────────────────
        $this->asignacionActiva = AsignacionItem::with([
            'asignacion.usuario.cargo',
            'asignacion.usuario.ubicacion',
            'asignacion.usuario.departamento',
            'asignacion.areaDepartamento',
            'asignacion.areaEmpresa',
            'asignacion.areaResponsable',
            'asignacion.analista',
        ])
            ->where('equipo_id', $id)
            ->where('devuelto', false)
            ->whereHas('asignacion', fn($q) => $q->where('estado', 'Activa'))
            ->latest()
            ->first();

        $this->historial = $this->buildHistorial($id);
        $this->open = true;
    }

    private function buildHistorial(int $equipoId): array
    {
        // Cargamos todos los valores (actuales e históricos) agrupados por atributo
        $valores = EquipoAtributoValor::with(['atributo', 'usuario'])
            ->where('equipo_id', $equipoId)
            ->orderBy('created_at', 'desc')
            ->get();

        $agrupado = [];

        foreach ($valores as $valor) {
            $nombre = $valor->atributo?->nombre ?? 'Atributo eliminado';
            $agrupado[$nombre][] = [
                'valor'      => $valor->valor,
                'es_actual'  => $valor->es_actual,
                'fecha'      => $valor->created_at?->format('d/m/Y H:i'),
                'usuario'    => $valor->usuario?->name ?? 'Sistema',
            ];
        }

        return $agrupado;
    }

    public function close(): void
    {
        $this->reset(['open', 'equipo', 'historial', 'asignacionActiva']);
    }

    public function render()
    {
        return view('livewire.equipos.equipo-view-modal');
    }
}
