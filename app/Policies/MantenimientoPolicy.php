<?php

namespace App\Policies;

use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * MantenimientoPolicy
 *
 * Administrador y Analista gestionan mantenimientos y reparaciones dentro de
 * su empresa activa. La única acción reservada exclusivamente al
 * Administrador es aprobar la baja de un equipo sin solución (antes(): true
 * para Administrador cubre esto; Analista siempre false en aprobarBaja()).
 */
class MantenimientoPolicy
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

    public function view(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $mantenimiento->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    public function update(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $mantenimiento->empresa_id
            && $mantenimiento->estaAbierto();
    }

    public function delete(User $user, Mantenimiento $mantenimiento): bool
    {
        return false; // Solo Administrador, vía before()
    }

    /** Aprobar la baja de un equipo sin solución — decisión patrimonial e irreversible. */
    public function aprobarBaja(User $user, Mantenimiento $mantenimiento): bool
    {
        return false; // Solo Administrador, vía before()
    }
}
