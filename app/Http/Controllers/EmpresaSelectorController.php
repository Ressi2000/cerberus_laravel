<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaSelectorController extends Controller
{
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Solo Analistas pueden llegar aquí
        abort_unless($user->hasRole('Analista'), 403);

        // Si ya tiene empresa activa → no debe estar aquí
        if ($user->empresa_activa_id) {
            return redirect()->route('dashboard');
        }

        // Si tiene 0 empresas → situación inválida
        if ($user->empresasAsignadas->isEmpty()) {
            abort(403);
        }

        // Si solo tiene una empresa → se asigna automáticamente
        if ($user->empresasAsignadas->count() === 1) {
            $user->update([
                'empresa_activa_id' => $user->empresasAsignadas->first()->id
            ]);

            return redirect()->route('dashboard');
        }

        // Caso válido: Analista con múltiples empresas
        return view('auth.select-empresa', [
            'empresas' => $user->empresasAsignadas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id']
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Seguridad: validar pertenencia
        if (! $user->empresasAsignadas->contains($request->empresa_id)) {
            abort(403);
        }

        $user->update([
            'empresa_activa_id' => $request->empresa_id
        ]);

        return redirect()->route('dashboard');
    }

    public function switch(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->empresasAsignadas()->where('empresas.id', $request->empresa_id)->exists()) {
            abort(403);
        }

        $empresaAnterior = $user->empresa_activa_id;

        $user->update([
            'empresa_activa_id' => $request->empresa_id,
        ]);

        // 🧾 Auditoría explícita de negocio
        Auditoria::create([
            'usuario_id' => $user->id,
            'tabla' => 'users',
            'registro_id' => $user->id,
            'accion' => 'CAMBIO_EMPRESA_ACTIVA',
            'valores_previos' => json_encode([
                'empresa_activa_id' => $empresaAnterior,
            ]),
            'valores_nuevos' => json_encode([
                'empresa_activa_id' => $request->empresa_id,
            ]),
            'created_at' => now(),
        ]);

        return back();
    }
}
