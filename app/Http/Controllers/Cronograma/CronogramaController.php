<?php

namespace App\Http\Controllers\Cronograma;

use App\Http\Controllers\Controller;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CronogramaController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', PlanMantenimiento::class);
        return view('cronograma.index');
    }
}
