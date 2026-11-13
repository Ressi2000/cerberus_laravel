<?php

namespace App\Policies;

use App\Models\MonitorHost;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * MonitorHostPolicy
 *
 * Administrador: todo. Usuario: nada. Analista: gestiona y revisa los hosts
 * de su empresa activa (alta, edición, pausa, eliminación, test de velocidad).
 */
class MonitorHostPolicy
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

    public function view(User $user, MonitorHost $host): bool
    {
        return $this->esDeSuEmpresa($user, $host);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function update(User $user, MonitorHost $host): bool
    {
        return $this->esDeSuEmpresa($user, $host);
    }

    public function delete(User $user, MonitorHost $host): bool
    {
        return $this->esDeSuEmpresa($user, $host);
    }

    /** Lanzar una revisión manual ("Revisar ahora"). */
    public function revisar(User $user): bool
    {
        return $this->viewAny($user);
    }

    private function esDeSuEmpresa(User $user, MonitorHost $host): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id !== null
            && (int) $user->empresa_activa_id === (int) $host->empresa_id;
    }
}
