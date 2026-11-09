<?php

namespace App\Policies;

use App\Models\TareaMantenimientoCatalogo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * El catálogo de tareas es una configuración global (no está scopeada por
 * empresa) — solo el Administrador la gestiona. Analista y Usuario no
 * tienen acceso a la página, pero sí consumen el catálogo (ya activo) al
 * armar la checklist de un caso o de un plan.
 */
class TareaMantenimientoCatalogoPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador')) return true;
        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TareaMantenimientoCatalogo $tarea): bool
    {
        return false;
    }

    public function delete(User $user, TareaMantenimientoCatalogo $tarea): bool
    {
        return false;
    }
}
