<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $auditorias = Auditoria::where('usuario_id', Auth::id())
            ->latest('created_at')
            ->limit(10)
            ->get();

        $user = $request->user()->load([
            'roles',
            'empresaNomina',
            'empresaActiva',
            'empresasAsignadas',
            'departamento',
            'cargo',
            'ubicacion',
            'jefe',
        ]);

        return view('profile.edit', [
            'user' => $user,
            'auditorias' => $auditorias,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $request->user()->fill($request->validated());

            if ($request->user()->isDirty('email')) {
                $request->user()->email_verified_at = null;
            }

            $request->user()->save();

            return Redirect::route('profile.edit')->with(['success' => 'Perfil actualizado correctamente.', 'profile-updated' => true]);
        } catch (\Exception $e) {
            return back()->withErrors(['profile' => 'Error al actualizar el perfil: ' . $e->getMessage()]);
        }
    }

    /**
     * Cambiar la foto de perfil. La página ya la achica en el navegador; si
     * llega grande igual (o sin JavaScript), se achica aquí a 600 px. Si el
     * servidor la rechazó por tamaño antes de llegar a Laravel, se dice claro.
     */
    public function updatePhoto(Request $request)
    {
        // PHP rechazó el archivo por su límite (upload_max_filesize / post_max_size).
        $error = $_FILES['foto']['error'] ?? null;
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            || ($request->isMethod('put') && empty($_FILES) && (int) $request->server('CONTENT_LENGTH') > 0)) {
            return back()->withErrors(['photo' => 'La foto pesa más de lo que permite el servidor (' . ini_get('upload_max_filesize') . '). Elige una más liviana.']);
        }

        $request->validate([
            'foto' => ['required', 'image', 'max:10240'],
        ], [
            'foto.required' => 'Elige una foto.',
            'foto.image'    => 'El archivo tiene que ser una imagen (JPG, PNG…).',
            'foto.max'      => 'La foto no puede pesar más de 10 MB.',
            'foto.uploaded' => 'No se pudo subir la foto. Intenta con una más liviana.',
        ]);

        try {
            $user = $request->user();
            $path = $this->guardarAchicada($request->file('foto'));

            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
            $user->update(['foto' => $path]);

            return back()->with(['success' => 'Foto actualizada correctamente.', 'photo-updated' => true]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['photo' => 'No se pudo guardar la foto. Intenta de nuevo.']);
        }
    }

    /** Guarda la foto en storage/app/public/users, achicada a 600 px de lado si hace falta. */
    private function guardarAchicada(\Illuminate\Http\UploadedFile $archivo): string
    {
        $info = @getimagesize($archivo->getRealPath());

        if ($info && function_exists('imagecreatefromstring') && max($info[0], $info[1]) > 600) {
            $origen = @imagecreatefromstring(file_get_contents($archivo->getRealPath()));
            if ($origen) {
                $escala = 600 / max($info[0], $info[1]);
                $destino = imagescale($origen, (int) round($info[0] * $escala), (int) round($info[1] * $escala));
                ob_start();
                imagejpeg($destino, null, 88);
                $jpeg = ob_get_clean();
                imagedestroy($origen);
                imagedestroy($destino);

                $path = 'users/' . \Illuminate\Support\Str::random(40) . '.jpg';
                Storage::disk('public')->put($path, $jpeg);

                return $path;
            }
        }

        return $archivo->store('users', 'public');
    }

    public function profileActivity(Request $request): View
    {
        // Temporalmente deshabilitado mientras se mejora el módulo — el
        // código real de abajo queda intacto, solo hay que quitar este
        // return anticipado cuando se reactive.
        return view('profile.activity-en-mejora');

        $query = Auditoria::where('usuario_id', Auth::id());

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('tabla')) {
            $query->where('tabla', $request->tabla);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        return view('profile.activity', [
            'auditorias' => $query->latest()->paginate(20)->withQueryString(),
        ]);
    }


    /**
     * Delete the user's account.
     */
    // public function destroy(Request $request): RedirectResponse
    // {
    //     $request->validateWithBag('userDeletion', [
    //         'password' => ['required', 'current_password'],
    //     ]);

    //     $user = $request->user();

    //     Auth::logout();

    //     $user->delete();

    //     $request->session()->invalidate();
    //     $request->session()->regenerateToken();

    //     return Redirect::to('/');
    // }
}
