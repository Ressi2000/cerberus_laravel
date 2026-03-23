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
        return $user->hasRole('Analista') && (bool) $user->empresa_activa_id;
    }
 
    /**
     * Ver el detalle de un equipo.
     */
    public function view(User $user, Equipo $equipo): bool
    {
        return $this->analistaVeEquipo($user, $equipo);
    }
 
    /**
     * Crear un equipo nuevo.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && (bool) $user->empresa_activa_id;
    }
 
    /**
     * Editar un equipo existente.
     * Un equipo dado de baja (activo = false) no es editable.
     */
    public function update(User $user, Equipo $equipo): bool
    {
        if (! $equipo->activo) return false;
        return $this->analistaVeEquipo($user, $equipo);
    }
 
    /**
     * Desactivación lógica: activo = false → "Dado de baja".
     * El equipo permanece en BD para auditoría e historial.
     * Solo sobre equipo de la empresa activa del analista.
     */
    public function delete(User $user, Equipo $equipo): bool
    {
        if (! $equipo->activo) return false;
        return $this->analistaVeEquipo($user, $equipo);
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
 
    // ─────────────────────────────────────────────────────
    // Helper central — análogo a scopeVisiblePara de User
    // ─────────────────────────────────────────────────────

    /**
     * Un analista puede operar sobre un equipo si:
     *   a) La ubicacion_id del equipo === empresa_activa_id del analista, O
     *   b) La ubicación del equipo está marcada como es_estado = true (foráneos)
     */
    private function analistaVeEquipo(User $user, Equipo $equipo): bool
    {
        if (! $user->empresa_activa_id) return false;

        // Caso A: equipo ubicado físicamente en la empresa activa
        if ((int) $equipo->ubicacion_id === (int) $user->empresa_activa_id) {
            return true;
        }

        // Caso B: equipo en ubicación foránea (estado)
        if ($equipo->ubicacion && $equipo->ubicacion->es_estado) {
            return true;
        }

        return false;
    }
}
