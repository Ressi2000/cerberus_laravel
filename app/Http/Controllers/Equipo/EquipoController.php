<?php

namespace App\Http\Controllers\Equipo;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Equipo;
use Illuminate\Support\Facades\Auth;

class EquipoController extends Controller
{
    use AuthorizesRequests;

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

    public function show(Equipo $equipo)
    {
        $this->authorize('view', $equipo);

        return view('equipos.historial-equipo', compact('equipo'));
    }
}
