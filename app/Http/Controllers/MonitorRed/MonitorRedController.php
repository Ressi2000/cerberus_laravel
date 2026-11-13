<?php

namespace App\Http\Controllers\MonitorRed;

use App\Exports\MonitorCaidasExport;
use App\Http\Controllers\Controller;
use App\Livewire\MonitorRed\CaidasTable;
use App\Models\MonitorEnlace;
use App\Models\MonitorHost;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class MonitorRedController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', MonitorHost::class);
        return view('monitor-red.index');
    }

    public function hosts()
    {
        $this->authorize('viewAny', MonitorHost::class);
        return view('monitor-red.hosts');
    }

    public function caidas()
    {
        $this->authorize('viewAny', MonitorHost::class);
        return view('monitor-red.caidas');
    }

    public function enlaces()
    {
        $this->authorize('viewAny', MonitorEnlace::class);
        return view('monitor-red.enlaces');
    }

    public function velocidad()
    {
        $this->authorize('viewAny', MonitorHost::class);
        return view('monitor-red.velocidad');
    }

    /** Excel del historial con los mismos filtros que la tabla (query string). */
    public function exportarCaidas(Request $request)
    {
        $this->authorize('viewAny', MonitorHost::class);

        $query = CaidasTable::consulta(
            Auth::user(),
            $request->only(['search', 'empresa_id', 'host_id', 'desde', 'hasta', 'en_curso'])
        );

        return Excel::download(
            new MonitorCaidasExport($query),
            'historial_caidas_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
