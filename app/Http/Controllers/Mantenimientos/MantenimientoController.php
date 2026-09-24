<?php

namespace App\Http\Controllers\Mantenimientos;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller delgado — solo coordina, cero lógica de negocio.
 * La lógica de listado/filtros/acciones vive en los componentes Livewire.
 */
class MantenimientoController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Mantenimiento::class);
        return view('mantenimientos.index');
    }

    /** Vista "En Reparación" del sidebar: mismo listado, filtrado a correctivos abiertos. */
    public function enReparacion()
    {
        $this->authorize('viewAny', Mantenimiento::class);
        return view('mantenimientos.en-reparacion');
    }

    public function create()
    {
        $this->authorize('create', Mantenimiento::class);
        return view('mantenimientos.crear');
    }

    public function show(Mantenimiento $mantenimiento)
    {
        $this->authorize('view', $mantenimiento);
        return view('mantenimientos.show', compact('mantenimiento'));
    }
}
