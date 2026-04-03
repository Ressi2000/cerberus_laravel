<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function categorias()
    {
        return view('configuracion.categorias.categorias');
    }
 
    public function estados()
    {
        return view('configuracion.estados.estados');
    }
 
    public function atributos()
    {
        return view('configuracion.atributos.atributos');
    }
}
