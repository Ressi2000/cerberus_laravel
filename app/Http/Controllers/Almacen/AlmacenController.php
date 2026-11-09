<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Models\ComponenteAlmacen;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AlmacenController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', ComponenteAlmacen::class);
        return view('almacen.index');
    }

    /** Bandeja de componentes pedidos por los casos que siguen sin entregarse. */
    public function solicitudes()
    {
        $this->authorize('viewAny', ComponenteAlmacen::class);
        return view('almacen.solicitudes');
    }
}
