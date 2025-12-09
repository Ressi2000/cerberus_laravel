<?php

namespace App\Http\Controllers;

use App\Exports\CollectionExport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function usuarios(Request $request)
    {
        $format = $request->input('format', 'xlsx'); // xlsx (default) o csv

        // Convertimos los usuarios al arreglo exportable
        $users = User::with(['rol', 'empresa', 'departamento', 'cargo', 'ubicacion'])
            ->get()
            ->map(function ($u) {
                return [
                    'ID'           => $u->id,
                    'Nombre'       => $u->name,
                    'Email'        => $u->email,
                    'Rol'          => $u->roles->pluck('name')->join(', '),
                    'Estado'       => $u->estado ? 'Activo' : 'Inactivo',
                    'Empresa'      => $u->empresa->nombre ?? '',
                    'Departamento' => $u->departamento->nombre ?? '',
                    'Cargo'        => $u->cargo->nombre ?? '',
                    'Ubicación'    => $u->ubicacion->nombre ?? '',
                    'Creado'       => $u->created_at?->format('Y-m-d H:i'),
                ];
            });

        // ============================
        // EXPORTACIÓN XLSX
        // ============================
        if ($format === 'xlsx') {
            return Excel::download(new CollectionExport($users), 'usuarios.xlsx');
        }

        // ============================
        // EXPORTACIÓN CSV
        // ============================
        if ($format === 'csv') {
            $filename = 'usuarios.csv';

            return new StreamedResponse(function () use ($users) {
                $handle = fopen('php://output', 'w');

                // Escribimos el header
                if ($users->isNotEmpty()) {
                    fputcsv($handle, array_keys($users->first()));
                }

                // Escribimos las filas
                foreach ($users as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            }, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename={$filename}",
            ]);
        }

        abort(400, 'Formato no válido.');
    }
}
