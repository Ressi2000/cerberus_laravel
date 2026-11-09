<?php

namespace App\Policies;

use App\Models\Deposito;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Los depósitos los crea y gestiona solo el Administrador ("según la
 * necesidad"). El Analista podrá VERLOS más adelante (para elegir destino
 * al dar de baja un equipo, por ejemplo) pero no gestionarlos — por ahora,
 * mientras solo existe el CRUD, toda la gestión es admin-only.
 */
class DepositoPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador')) return true;
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function view(User $user, Deposito $deposito): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $deposito->empresa_id;
    }

    public function create(User $user): bool
    {
        return false; // Solo Administrador, vía before()
    }

    public function update(User $user, Deposito $deposito): bool
    {
        return false; // Solo Administrador, vía before()
    }

    public function delete(User $user, Deposito $deposito): bool
    {
        return false; // Solo Administrador, vía before()
    }
}
