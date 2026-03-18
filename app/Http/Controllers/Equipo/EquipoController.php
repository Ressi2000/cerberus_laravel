<?php

namespace App\Http\Controllers\Equipo;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use Illuminate\Support\Facades\Auth;

class EquipoController extends Controller
{
    public function index()
    {
        return view('equipos.index');
    }

    public function create()
    {
        return view('equipos.create');
    }

    public function edit(Equipo $equipo)
    {
        return view('equipos.edit', compact('equipo'));
    }
}
