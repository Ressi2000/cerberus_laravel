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
        if ($user->hasRole('Administrador')) {
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
            && $user->empresa_activa_id === $traslado->empresa_id;
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
