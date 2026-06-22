<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Services\FirmaResolver;
use App\Services\FirmaService;
use App\Services\Folio;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Página pública de firma digital remota (destino del enlace enviado por
 * correo). No requiere login: la URL llega firmada (middleware 'signed'),
 * así que no se puede adivinar ni alterar el tipo/id/rol del documento.
 */
class FirmaController extends Controller
{
    public function __construct(private FirmaService $firmas) {}

    public function show(Request $request, string $tipo, int $id, string $rol): View|Response
    {
        $documento = FirmaResolver::modelo($tipo, $id);

        if (! $documento) {
            return response()->view('firma.no-encontrado', [], 404);
        }

        $firma = Firma::where('firmable_type', $documento::class)
            ->where('firmable_id', $documento->id)
            ->where('rol', $rol)
            ->first();

        if (! $firma) {
            return response()->view('firma.no-encontrado', [], 404);
        }

        if ($firma->estaFirmada()) {
            return view('firma.completado', [
                'firma'           => $firma,
                'tituloDocumento' => FirmaResolver::tituloDocumento($tipo),
                'folio'           => Folio::etiqueta($tipo, $id),
            ]);
        }

        return view('firma.show', [
            'firma'           => $firma,
            'tipo'            => $tipo,
            'id'              => $id,
            'rol'             => $rol,
            'tituloDocumento' => FirmaResolver::tituloDocumento($tipo),
            'folio'           => Folio::etiqueta($tipo, $id),
            'firmante'        => $firma->user,
        ]);
    }

    public function store(Request $request, string $tipo, int $id, string $rol): View
    {
        $documento = FirmaResolver::modelo($tipo, $id);

        if (! $documento) {
            return response()->view('firma.no-encontrado', [], 404);
        }

        $firma = Firma::where('firmable_type', $documento::class)
            ->where('firmable_id', $documento->id)
            ->where('rol', $rol)
            ->first();

        if (! $firma || $firma->estaFirmada()) {
            return response()->view('firma.no-encontrado', [], 404);
        }

        $datos = $request->validate([
            'imagen' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $this->firmas->registrar($firma, $datos['imagen'], $request->ip(), $request->userAgent());

        return view('firma.completado', [
            'firma'           => $firma,
            'tituloDocumento' => FirmaResolver::tituloDocumento($tipo),
            'folio'           => Folio::etiqueta($tipo, $id),
        ]);
    }
}
