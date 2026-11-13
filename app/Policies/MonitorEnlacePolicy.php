<?php

namespace App\Policies;

use App\Models\MonitorEnlace;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * MonitorEnlacePolicy
 *
 * Enlaces de internet (proveedores) de cada localidad. Administrador: todo.
 * Usuario: nada. Analista: solo ve y gestiona los de su empresa activa.
 */
class MonitorEnlacePolicy
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

    public function view(User $user, MonitorEnlace $enlace): bool
    {
        return $this->esDeSuEmpresa($user, $enlace);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function update(User $user, MonitorEnlace $enlace): bool
    {
        return $this->esDeSuEmpresa($user, $enlace);
    }

    public function delete(User $user, MonitorEnlace $enlace): bool
    {
        return $this->esDeSuEmpresa($user, $enlace);
    }

    private function esDeSuEmpresa(User $user, MonitorEnlace $enlace): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id !== null
            && (int) $user->empresa_activa_id === (int) $enlace->empresa_id;
    }
}
