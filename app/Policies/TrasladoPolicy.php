<?php

namespace App\Policies;

use App\Models\Traslado;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrasladoPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        // 'revertir' se excluye del atajo: debe evaluarse siempre en el método
        // específico, que revisa si el traslado ya fue revertido. De lo
        // contrario, un Administrador vería el botón "Revertir" indefinidamente.
        if ($user->hasRole('Administrador') && $ability !== 'revertir') {
            return true;
        }

        if ($user->hasRole('Usuario')) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function view(User $user, Traslado $traslado): bool
    {
        return $user->hasRole('Analista')
            && (int) $user->empresa_activa_id === (int) $traslado->empresa_id;
    }

    public function revertir(User $user, Traslado $traslado): bool
    {
        if ($traslado->estado === 'revertido') {
            return false;
        }

        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->hasRole('Analista')
            && (int) $user->empresa_activa_id === (int) $traslado->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function delete(User $user, Traslado $traslado): bool
    {
        return false; // Solo Admin (cortocircuitado en before())
    }
}
