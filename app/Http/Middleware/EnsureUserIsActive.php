<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
         /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && $user->estado !== 'Activo') {
            // reset empresa_Activa before logging out
            $user->empresa_activa_id = null;
            $user->save();

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'username' => 'Tu sesión fue cerrada porque tu usuario fue inactivado.',
                ]);
        }

        return $next($request);
    }
}
