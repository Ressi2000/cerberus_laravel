<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        // Admin y Usuario → directo
        if ($user->hasAnyRole(['Administrador', 'Usuario'])) {
            return redirect()->intended(route('dashboard'));
        }

        // Analista
        $empresas = $user->empresasAsignadas;

        if ($empresas->isEmpty()) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['username' => 'Tu usuario no tiene empresas asignadas. Contacta con el administrador.']);
        }

        if ($empresas->count() === 1) {
            $user->update([
                'empresa_activa_id' => $empresas->first()->id
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return redirect()->route('empresa.select');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user && $user->hasRole('Analista')) {
            $user->update([
                'empresa_activa_id' => null
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
