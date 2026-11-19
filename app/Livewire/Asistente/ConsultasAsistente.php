<?php

namespace App\Livewire\Asistente;

use App\Models\AsistenteConsulta;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Bitácora de Cerberus Bot (solo Administrador): qué se preguntó, qué
 * consultas hizo la IA, cuánto tardó y cuáles no pudo responder. Las "sin
 * respuesta" dicen qué herramienta nueva haría falta.
 */
class ConsultasAsistente extends Component
{
    use WithPagination;

    #[Url]
    public string $filtro = 'todas'; // todas | sin_respuesta | errores

    #[Url]
    public string $buscar = '';

    public ?int $abierta = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole('Administrador'), 403);
    }

    public function updatedFiltro(): void
    {
        $this->resetPage();
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function alternar(int $id): void
    {
        $this->abierta = $this->abierta === $id ? null : $id;
    }

    public function render()
    {
        $q = AsistenteConsulta::with('usuario:id,name', 'empresa:id,nombre')->latest();

        match ($this->filtro) {
            'sin_respuesta' => $q->where('sin_respuesta', true),
            'errores'       => $q->whereNotNull('error'),
            default         => null,
        };
        if (trim($this->buscar) !== '') {
            $q->where('pregunta', 'like', '%' . addcslashes(trim($this->buscar), '%_\\') . '%');
        }

        return view('livewire.asistente.consultas-asistente', [
            'consultas' => $q->paginate(25),
            'totales'   => [
                'todas'         => AsistenteConsulta::count(),
                'sin_respuesta' => AsistenteConsulta::where('sin_respuesta', true)->count(),
                'errores'       => AsistenteConsulta::whereNotNull('error')->count(),
            ],
        ]);
    }
}
