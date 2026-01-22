<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {

        return view('admin.auditoria.index', [
            'usuarios' => User::orderBy('name')->pluck('name', 'id'),
            'acciones' => Auditoria::select('accion')->distinct()->pluck('accion'),
            'tablas' => Auditoria::select('tabla')->distinct()->pluck('tabla'),
        ]);
    }
}
