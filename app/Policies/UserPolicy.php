<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Analista']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // $user → usuario autenticado (actor)
        // $model → usuario que se quiere ver (objetivo)

        return User::query()
            ->visiblePara($user)
            ->whereKey($model->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Analista']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Nadie excepto el administrador se edita a sí mismo
        if ($user->id === $model->id && ! $user->hasRole('Administrador')) {
            return false;
        }

        // Admin todo
        if ($user->hasRole('Administrador')) {
            return true;
        }

        // Usuario nunca edita
        if ($user->hasRole('Usuario')) {
            return false;
        }

        // Analista
        if (
            $user->hasRole('Analista') &&
            $model->hasRole('Usuario') &&
            (
                $model->ubicacion_id === $user->empresa_activa_id ||
                $model->ubicacion?->es_estado === true
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // $user → usuario autenticado (actor)
        // $model → usuario que se quiere eliminar (objetivo)

        // Nunca a sí mismo
        if ($user->id === $model->id) {
            return false;
        }

        // Admin no elimina admins
        if ($user->hasRole('Administrador')) {
            return ! $model->hasRole('Administrador');
        }

        // Analista SOLO usuarios de su ubicación física
        if (
            $user->hasRole('Analista') &&
            $model->hasRole('Usuario') &&
            (
                $model->ubicacion_id === $user->empresa_activa_id ||
                $model->ubicacion?->es_estado === true
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
