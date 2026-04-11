<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AuditoriaController extends Controller
{
    public function index()
    {
        return view('auditoria.index');
    }
}
