<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Seguridad básica
        if (! $user) {
            abort(401);
        }

        if ($user->hasRole('Administrador') || $user->hasRole('Usuario')) {
            return $next($request);
        }

        // No tiene empresa activa
        if (! $user->empresa_activa_id) {
            return redirect()
                ->route('empresa.select')
                ->with('warning', 'Debes seleccionar una empresa para continuar.');
        }

        // La empresa activa NO le pertenece
        $pertenece = $user->empresasAsignadas()
            ->where('empresas.id', $user->empresa_activa_id)
            ->exists();

        if (! $pertenece) {
            // limpiamos estado inconsistente
            $user->update(['empresa_activa_id' => null]);

            return redirect()
                ->route('empresa.select')
                ->with('error', 'La empresa seleccionada no es válida.');
        }

        return $next($request);
    }
}
