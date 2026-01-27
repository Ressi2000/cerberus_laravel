<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use App\Notifications\CerberusPasswordResetAlert;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'username' => 'required|string',
            'email' => 'required',
            'email',
            'password' => 'required',
            'confirmed',
            Rules\Password::defaults(),
        ]);

        $user = User::where('username', $request->username)
            ->where('email', $request->email)
            ->first();

        if (! $user || $user->estado !== 'Activo') {
            throw ValidationException::withMessages([
                'username' => 'No se puede restablecer la contraseña de este usuario.',
            ]);
        }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $valoresPrevios = [
                    'remember_token' => $user->remember_token,
                ];

                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // 🔥 Elimina sesiones activas si usas database driver
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();

                // 🧾 Auditoría explícita
                Auditoria::create([
                    'usuario_id' => $user->id,
                    'tabla' => 'users',
                    'registro_id' => $user->id,
                    'accion' => 'PASSWORD_RESET',
                    'valores_previos' => json_encode($valoresPrevios),
                    'valores_nuevos' => json_encode([
                        'remember_token' => $user->remember_token,
                    ]),
                    'created_at' => now(),
                ]);

                // 📩 Alerta post reset
                $user->notify(new CerberusPasswordResetAlert());
                
                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
