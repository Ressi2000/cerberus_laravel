<?php

namespace App\Http\Controllers\Mantenimientos;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\MantenimientoEvidencia;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Entrega una foto de evidencia. Las fotos están en el disco privado:
     * solo las ve quien puede ver el caso. scopeBindings() en la ruta
     * garantiza que la foto pertenezca a ESE caso.
     */
    public function evidencia(Mantenimiento $mantenimiento, MantenimientoEvidencia $evidencia)
    {
        $this->authorize('view', $mantenimiento);

        $disco = $evidencia->discoDelArchivo();
        abort_if($disco === null, 404);

        return Storage::disk($disco)->response($evidencia->ruta_archivo, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
