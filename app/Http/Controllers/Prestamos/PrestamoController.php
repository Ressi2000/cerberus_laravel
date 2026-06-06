<?php

namespace App\Http\Controllers\Prestamos;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\User;
use App\Services\PlanillaPrestamoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PrestamoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PlanillaPrestamoService $planillas)
    {
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
}
