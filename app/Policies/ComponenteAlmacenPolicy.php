<?php

namespace App\Policies;

use App\Models\ComponenteAlmacen;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * ComponenteAlmacenPolicy
 *
 * Administrador y Analista pueden dar de alta componentes y registrar
 * entradas/salidas de stock dentro de su empresa activa. Desactivar
 * (eliminación administrativa) queda solo para Administrador, vía before().
 */
class ComponenteAlmacenPolicy
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

    public function view(User $user, ComponenteAlmacen $componente): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $componente->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function update(User $user, ComponenteAlmacen $componente): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $componente->empresa_id;
    }

    public function delete(User $user, ComponenteAlmacen $componente): bool
    {
        return false; // Solo Administrador, vía before()
    }

    /** Registrar entrada/salida de stock. Mismo alcance que update(). */
    public function registrarMovimiento(User $user, ComponenteAlmacen $componente): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $componente->empresa_id;
    }
}
