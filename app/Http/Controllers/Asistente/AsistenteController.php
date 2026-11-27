<?php

namespace App\Http\Controllers\Asistente;

use App\Asistente\Asistente;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AsistenteController extends Controller
{
    /**
     * Vista de pantalla completa de Cerberus Bot.
     * Solo accesible donde ASISTENTE_HABILITADO=true y para Administrador/Analista.
     */
    public function index(): View
    {
        abort_unless(Asistente::disponiblePara(auth()->user()), 404);

        return view('asistente.index');
    }
}
