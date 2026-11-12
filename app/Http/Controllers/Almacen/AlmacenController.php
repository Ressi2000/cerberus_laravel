<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Models\ComponenteAlmacen;
use App\Models\PiezaExtraida;
use App\Services\QrCodeGenerator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

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

    /**
     * Hoja de etiquetas para imprimir y pegar en cada pieza: su código
     * (serial o AUTO-…) en grande y un QR con ese mismo código, que al
     * escanearlo en el buscador del almacén abre la pieza.
     *
     * ?piezas=1,2,3  piezas puntuales (ej. lo que acaba de entrar)
     * ?componente=ID todas las que hay en stock de esa variante
     */
    public function etiquetas(Request $request)
    {
        $this->authorize('viewAny', ComponenteAlmacen::class);

        $query = PiezaExtraida::visiblePara($request->user())
            ->with(['componenteAlmacen.familia', 'empresa']);

        if ($request->filled('componente')) {
            $componente = ComponenteAlmacen::findOrFail((int) $request->input('componente'));
            $this->authorize('view', $componente);
            $query->where('componente_almacen_id', $componente->id)->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN);
        } else {
            $ids = collect(explode(',', (string) $request->input('piezas')))
                ->map(fn ($id) => (int) $id)->filter()->unique()->take(300);
            $query->whereIn('id', $ids);
        }

        $piezas = $query->orderBy('componente_almacen_id')->orderBy('id')->limit(300)->get()
            ->map(function (PiezaExtraida $p) {
                $p->setAttribute('qr', QrCodeGenerator::base64Png($p->identificador ?: "PIEZA-{$p->id}", 200));

                return $p;
            });

        abort_if($piezas->isEmpty(), 404);

        return view('almacen.etiquetas', compact('piezas'));
    }
}
