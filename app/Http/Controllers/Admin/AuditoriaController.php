<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AuditoriaController extends Controller
{
    public function index()
    {
        // Temporalmente deshabilitado mientras se mejora el módulo — la
        // vista real (auditoria.index) queda intacta, solo hay que
        // devolverla acá cuando se reactive.
        return view('auditoria.en-mejora');
    }
}
