<?php

namespace App\Http\Controllers;

use App\Exports\CollectionExport;
use App\Exports\UsuariosExport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function usuarios(Request $request)
    {
        $format = $request->input('format', 'xlsx'); // xlsx (default) o csv

        // ==============================
        // Construir query con filtros activos
        // ==============================
        $query = User::with(['empresa', 'departamento', 'cargo', 'ubicacion', 'roles']);

        if ($request->search) {
            $search = $request->search;
            $query->where(
                fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
            );
        }

        if ($request->rol_id) {
            $query->whereHas('roles', fn($q) => $q->where('id', $request->rol_id));
        }
        if ($request->empresa_id) {
            $query->where('empresa_id', $request->empresa_id);
        }
        if ($request->departamento_id) {
            $query->where('departamento_id', $request->departamento_id);
        }
        if ($request->cargo_id) {
            $query->where('cargo_id', $request->cargo_id);
        }
        if ($request->ubicacion_id) {
            $query->where('ubicacion_id', $request->ubicacion_id);
        }
        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        // ==============================
        // Exportación XLSX o CSV
        // ==============================
        $filename = 'usuarios.' . $format;

        return Excel::download(new UsuariosExport($query), $filename);
    }
}
