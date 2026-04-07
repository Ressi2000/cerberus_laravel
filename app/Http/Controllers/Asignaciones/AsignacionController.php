<?php

namespace App\Http\Controllers\Asignaciones;

// ============================================================
// app/Http/Controllers/Asignaciones/AsignacionController.php
// ============================================================
// Controller delgado — solo coordina, cero lógica de negocio.
// El patrón es el mismo que EquipoController.
// ============================================================

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\User;
use App\Services\PlanillaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AsignacionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PlanillaService $planillas)
    {
    }
 
    // ── Vistas principales ────────────────────────────────────────────────────
    /**
     * Listado de asignaciones.
     * La lógica y filtros están en AsignacionesTable (Livewire).
     */
    public function index()
    {
        $this->authorize('viewAny', Asignacion::class);

        return view('asignaciones.index');
    }

    /**
     * Formulario de creación.
     * El wizard completo está en CrearAsignacion (Livewire).
     */
    public function create()
    {
        $this->authorize('create', Asignacion::class);

        return view('asignaciones.create');
    }

    /** Página de historial por usuario */
    public function historial(User $usuario)
    {
        $this->authorize('viewAny', Asignacion::class);
        return view('asignaciones.historial', compact('usuario'));
    }

    /** Formulario de devolución por usuario (todos sus equipos activos) */
    public function devolverUsuario(User $usuario)
    {
        $this->authorize('viewAny', Asignacion::class);
        return view('asignaciones.devolver-usuario', compact('usuario'));
    }

    /**
     * Formulario de devolución.
     * Recibe la asignación via route model binding.
     * Lógica en DevolverAsignacion (Livewire).
     */
    public function devolver(Asignacion $asignacion)
    {
        $this->authorize('devolver', $asignacion);

        return view('asignaciones.devolver', compact('asignacion'));
    }

    // ── Planillas PDF ─────────────────────────────────────────────────────────

    public function planillaAsignacion(Asignacion $asignacion)
    {
        $this->authorize('view', $asignacion);

        $nombre = 'Asignacion_' . $asignacion->id . '_' . now()->format('Ymd') . '.pdf';
        return $this->planillas->asignacion($asignacion)->download($nombre);
    }

    public function planillaDevolucion(Asignacion $asignacion)
    {
        $this->authorize('view', $asignacion);

        $nombre = 'Devolucion_' . $asignacion->id . '_' . now()->format('Ymd') . '.pdf';
        return $this->planillas->devolucion($asignacion)->download($nombre);
    }

    public function planillaEgreso(User $usuario)
    {
        $this->authorize('viewAny', Asignacion::class);

        $nombre = 'Egreso_' . str_replace(' ', '_', $usuario->name) . '_' . now()->format('Ymd') . '.pdf';
        return $this->planillas->egreso($usuario)->download($nombre);
    }
}
