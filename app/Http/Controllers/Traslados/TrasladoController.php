<?php

namespace App\Http\Controllers\Traslados;

use App\Http\Controllers\Controller;
use App\Models\Traslado;
use App\Services\FirmaService;
use App\Services\PlanillaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class TrasladoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PlanillaService $planillas,
        private FirmaService $firmas,
    ) {
    }

    public function index()
    {
        $this->authorize('viewAny', Traslado::class);

        return view('traslados.index');
    }

    public function create()
    {
        $this->authorize('create', Traslado::class);

        return view('traslados.crear');
    }

    public function show(Traslado $traslado)
    {
        $this->authorize('view', $traslado);

        return view('traslados.show', compact('traslado'));
    }

    public function planilla(Traslado $traslado)
    {
        $this->authorize('view', $traslado);

        $nombre = 'Traslado_' . $traslado->numero . '_' . now()->format('Ymd') . '.pdf';

        return $this->planillas->traslado($traslado)->download($nombre);
    }

    public function solicitarFirma(Traslado $traslado): RedirectResponse
    {
        $this->authorize('view', $traslado);

        $this->firmas->solicitar('traslado', $traslado);

        return back()->with('status', 'Se enviaron las solicitudes de firma digital.');
    }
}
