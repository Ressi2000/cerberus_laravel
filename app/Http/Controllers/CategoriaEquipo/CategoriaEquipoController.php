<?php

namespace App\Http\Controllers\CategoriaEquipo;

use App\Http\Controllers\Controller;
use App\Models\CategoriaEquipo;
use Illuminate\Http\Request;

class CategoriaEquipoController extends Controller
{
    public function index()
    {
        return view('categoria-equipos.index');
    }

    public function create()
    {
        return view('categoria-equipos.create');
    }

    public function edit(CategoriaEquipo $categoria)
    {
        return view('categoria-equipos.edit', compact('categoria'));
    }
}
