<?php

namespace App\Http\Controllers\Mantenimientos;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

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

    public function create()
    {
        $this->authorize('create', Mantenimiento::class);
        return view('mantenimientos.crear');
    }

    public function show(Request $request, Mantenimiento $mantenimiento)
    {
        $this->authorize('view', $mantenimiento);

        // Si se llegó desde el Lote de un plan (Cronograma), "volver" debe
        // regresar ahí en vez de al listado general de Mantenimientos.
        $lote = null;
        if ($request->filled('lote')) {
            $lote = PlanMantenimiento::withTrashed()
                ->with(['categoria', 'empresa'])
                ->find($request->query('lote'));
        }

        return view('mantenimientos.show', compact('mantenimiento', 'lote'));
    }
}
