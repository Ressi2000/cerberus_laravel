<?php

namespace App\Http\Controllers\Equipo;

use App\Http\Controllers\Controller;
use App\Models\Equipos;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function index()
    {
        return view('equipos.index');
    }
}
