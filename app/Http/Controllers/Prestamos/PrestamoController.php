<?php

namespace App\Http\Controllers\Prestamos;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\User;
use App\Services\FirmaService;
use App\Services\PlanillaPrestamoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class PrestamoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PlanillaPrestamoService $planillas,
        private FirmaService $firmas,
    ) {
    }

    public function index()
    {
        $this->authorize('viewAny', Prestamo::class);
        return view('prestamos.index');
    }

    public function create()
    {
        $this->authorize('create', Prestamo::class);
        return view('prestamos.create');
    }

    public function devolver(Prestamo $prestamo)
    {
        $this->authorize('devolver', $prestamo);
        return view('prestamos.devolver', compact('prestamo'));
    }

    /** Formulario de devolución por usuario (todos sus equipos en préstamo) */
    public function devolverUsuario(User $usuario)
    {
        $this->authorize('viewAny', Prestamo::class);
        return view('prestamos.devolver-usuario', compact('usuario'));
    }

    /** Página de historial por usuario */
    public function historial(User $usuario)
    {
        $this->authorize('viewAny', Prestamo::class);
        return view('prestamos.historial', compact('usuario'));
    }

    public function planillaPrestamo(Prestamo $prestamo)
    {
        $this->authorize('view', $prestamo);
        $nombre = 'Prestamo_' . $prestamo->id . '_' . now()->format('Ymd') . '.pdf';
        return $this->planillas->prestamo($prestamo)->download($nombre);
    }

    public function planillaDevolucion(Prestamo $prestamo)
    {
        $this->authorize('view', $prestamo);
        $nombre = 'DevolucionPrestamo_' . $prestamo->id . '_' . now()->format('Ymd') . '.pdf';
        return $this->planillas->devolucion($prestamo)->download($nombre);
    }

    public function solicitarFirma(Prestamo $prestamo): RedirectResponse
    {
        $this->authorize('view', $prestamo);

        $this->firmas->solicitar('prestamo', $prestamo);

        return back()->with('status', 'Se envió la solicitud de firma digital al receptor.');
    }
}
