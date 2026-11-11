<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Deposito;
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

    /** Familias de piezas: ficha técnica compartida por todas las categorías. */
    public function familias()
    {
        return view('configuracion.familias.familias');
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

    public function licenciasMicrosoft()
    {
        return view('configuracion.licencias-microsoft.licencias-microsoft');
    }

    public function tareasMantenimiento()
    {
        return view('configuracion.tareas-mantenimiento.tareas-mantenimiento');
    }

    public function depositos()
    {
        return view('configuracion.depositos.depositos');
    }

    public function depositoDetalle(Deposito $deposito)
    {
        return view('configuracion.depositos.detalle', compact('deposito'));
    }
}
