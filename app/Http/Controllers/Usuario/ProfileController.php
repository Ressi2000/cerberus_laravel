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

        return view('admin.profile.edit', [
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

    public function updatePhoto(Request $request)
    {
        try {
            $request->validate([
                'foto' => ['required', 'image', 'max:2048'],
            ]);

            $user = $request->user();

            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }

            $path = $request->file('foto')->store('users', 'public');

            $user->update([
                'foto' => $path,
            ]);

            return back()->with(['success' => 'Foto actualizada correctamente.', 'photo-updated' => true]);
        } catch (\Exception $e) {
            return back()->withErrors(['photo' => 'Error al actualizar la foto: ' . $e->getMessage()]);
        }
    }

    public function profileActivity(Request $request): View
    {
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

        return view('admin.profile.activity', [
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
