<?php

namespace App\Policies;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EquipoPolicy
{
    /**
     * Atajo global antes de evaluar cualquier método.
     *
     * - Administrador → siempre true (acceso total)
     * - Usuario normal → siempre false (sin acceso al módulo)
     * - Analista → null (evalúa cada método individualmente)
     */
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
 
    /**
     * Ver el listado de equipo.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Analista');
    }
 
    /**
     * Ver el detalle de un equipo.
     */
    public function view(User $user, Equipo $equipo): bool
    {
        return $this->perteneceAEmpresaActiva($user, $equipo);
    }
 
    /**
     * Crear un equipo nuevo.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Analista');
    }
 
    /**
     * Editar un equipo existente.
     * Un equipo dado de baja (activo = false) no es editable.
     */
    public function update(User $user, Equipo $equipo): bool
    {
        if (! $equipo->activo) {
            return false;
        }
 
        return $this->perteneceAEmpresaActiva($user, $equipo);
    }
 
    /**
     * Desactivación lógica: activo = false → "Dado de baja".
     * El equipo permanece en BD para auditoría e historial.
     * Solo sobre equipo de la empresa activa del analista.
     */
    public function delete(User $user, Equipo $equipo): bool
    {
        if (! $equipo->activo) {
            return false;
        }
 
        return $this->perteneceAEmpresaActiva($user, $equipo);
    }
 
    /**
     * Eliminación administrativa definitiva (soft delete real / deleted_at).
     * Solo Administrador — cubierto por before(), este método nunca
     * llega a evaluarse para Analista.
     */
    public function forceDelete(User $user, Equipo $equipo): bool
    {
        return false;
    }
 
    /**
     * Restaurar un equipo eliminado.
     * Solo Administrador — cubierto por before().
     */
    public function restore(User $user, Equipo $equipo): bool
    {
        return false;
    }
 
    // ──────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────
 
    /**
     * El equipo debe pertenecer a la empresa con la que
     * el analista inició sesión (empresa activa en sesión).
     */
    private function perteneceAEmpresaActiva(User $user, Equipo $equipo): bool
    {
        $empresaActiva = Auth::user()->empresa_activa_id;
 
        if (! $empresaActiva) {
            return false;
        }
 
        return (int) $equipo->empresa_id === (int) $empresaActiva;
    }
}
