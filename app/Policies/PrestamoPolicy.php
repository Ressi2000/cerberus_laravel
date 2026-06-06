<?php

namespace App\Policies;

use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrestamoPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador')) return true;
        if ($user->hasRole('Usuario'))       return false;
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function view(User $user, Prestamo $prestamo): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $prestamo->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function devolver(User $user, Prestamo $prestamo): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $prestamo->empresa_id
            && $prestamo->estado !== 'Cerrado';
    }

    public function renovar(User $user, Prestamo $prestamo): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $prestamo->empresa_id
            && $prestamo->estado !== 'Cerrado';
    }

    public function delete(User $user, Prestamo $prestamo): bool
    {
        return false; // Solo Admin (cortocircuitado en before())
    }
}
