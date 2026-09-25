<?php

namespace App\Policies;

use App\Models\PlanMantenimiento;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanMantenimientoPolicy
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

    public function view(User $user, PlanMantenimiento $plan): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $plan->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function update(User $user, PlanMantenimiento $plan): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $plan->empresa_id;
    }

    public function delete(User $user, PlanMantenimiento $plan): bool
    {
        return false; // Solo Administrador, vía before()
    }
}
