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

    public function departamentos()
    {
        return view('configuracion.departamentos.departamentos');
    }
 
    public function cargos()
    {
        return view('configuracion.cargos.cargos');
    }
 
    public function ubicaciones()
    {
        return view('configuracion.ubicaciones.ubicaciones');
    }
 
    public function empresas()
    {
        return view('configuracion.empresas.empresas');
    }
}
