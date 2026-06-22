<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Services\FirmaResolver;
use App\Services\FirmaService;
use App\Services\Folio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Página pública de firma digital remota (destino del enlace expuesto en
 * la página de verificación, accesible desde el mismo QR impreso). No
 * requiere login: la URL llega firmada (middleware 'signed'). Antes de
 * mostrar el lienzo, pide la cédula del firmante como verificación liviana
 * de identidad — el documento es público (cualquiera con el QR llega aquí),
 * así que esto evita que alguien firme por la persona equivocada.
 */
class FirmaController extends Controller
{
    public function __construct(private FirmaService $firmas) {}

    public function show(Request $request, string $tipo, int $id, string $rol): View|Response
    {
        $firma = $this->buscarFirma($tipo, $id, $rol);

        if (! $firma) {
            return response()->view('firma.no-encontrado', [], 404);
        }

        if ($firma->estaFirmada()) {
            return $this->vistaCompletado($tipo, $id, $firma);
        }

        if (! $request->session()->get($this->claveVerificada($firma))) {
            return view('firma.verificar-cedula', [
                'firmante'        => $firma->user,
                'tituloDocumento' => FirmaResolver::tituloDocumento($tipo),
                'folio'           => Folio::etiqueta($tipo, $id),
            ]);
        }

        return view('firma.show', [
            'firma'           => $firma,
            'tituloDocumento' => FirmaResolver::tituloDocumento($tipo),
            'folio'           => Folio::etiqueta($tipo, $id),
            'rol'             => FirmaResolver::etiquetaRol($rol),
            'firmante'        => $firma->user,
        ]);
    }

    public function store(Request $request, string $tipo, int $id, string $rol): View|RedirectResponse
    {
        $firma = $this->buscarFirma($tipo, $id, $rol);

        if (! $firma || $firma->estaFirmada()) {
            return response()->view('firma.no-encontrado', [], 404);
        }

        if ($request->has('cedula')) {
            return $this->verificarCedula($request, $firma);
        }

        if (! $request->session()->get($this->claveVerificada($firma))) {
            return response()->view('firma.no-encontrado', [], 403);
        }

        $datos = $request->validate([
            'imagen' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $this->firmas->registrar($firma, $datos['imagen'], $request->ip(), $request->userAgent());

        return $this->vistaCompletado($tipo, $id, $firma);
    }

    private function verificarCedula(Request $request, Firma $firma): RedirectResponse
    {
        $request->validate(['cedula' => ['required', 'string']]);

        $coincide = $this->soloDigitos($request->input('cedula')) === $this->soloDigitos((string) $firma->user?->cedula);

        if (! $coincide) {
            return back()->withErrors(['cedula' => 'La cédula no coincide con el firmante esperado.']);
        }

        $request->session()->put($this->claveVerificada($firma), true);

        return redirect($request->fullUrl());
    }

    private function buscarFirma(string $tipo, int $id, string $rol): ?Firma
    {
        $documento = FirmaResolver::modelo($tipo, $id);

        if (! $documento) {
            return null;
        }

        return Firma::where('firmable_type', $documento::class)
            ->where('firmable_id', $documento->id)
            ->where('rol', $rol)
            ->first();
    }

    private function vistaCompletado(string $tipo, int $id, Firma $firma): View
    {
        return view('firma.completado', [
            'firma'           => $firma,
            'tituloDocumento' => FirmaResolver::tituloDocumento($tipo),
            'folio'           => Folio::etiqueta($tipo, $id),
        ]);
    }

    private function claveVerificada(Firma $firma): string
    {
        return "firma_verificada.{$firma->id}";
    }

    /**
     * La cédula se guarda como 'V-12345678' o 'E-12345678', pero el firmante
     * solo escribe los números (el formulario es de tipo numérico). Comparamos
     * solo los dígitos para no exigirle que recuerde el prefijo V/E.
     */
    private function soloDigitos(string $valor): string
    {
        return preg_replace('/\D/', '', $valor);
    }
}
