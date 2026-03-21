<?php

namespace App\Policies;

use App\Models\Equipo;
use App\Models\User;

class EquipoPolicy
{
    /**
     * Cortocircuito global antes de evaluar cualquier método.
     *
     *  Administrador → true  (acceso total, sin restricciones)
     *  Usuario       → false (sin acceso al módulo de equipos)
     *  Analista      → null  (se evalúa método a método)
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        if ($user->hasRole('Usuario')) {
            return false;
        }

        return null; // Analista: continúa evaluando
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos de la policy
    // ─────────────────────────────────────────────────────────────────────────

    /** Ver el listado de equipos */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Analista');
    }

    /** Ver el detalle de un equipo */
    public function view(User $user, Equipo $equipo): bool
    {
        return $this->equipoVisible($user, $equipo);
    }

    /** Crear un equipo nuevo */
    public function create(User $user): bool
    {
        return $user->hasRole('Analista');
    }

    /**
     * Editar un equipo existente.
     * Un equipo dado de baja (activo = false) no es editable por nadie.
     */
    public function update(User $user, Equipo $equipo): bool
    {
        if (! $equipo->activo) {
            return false;
        }

        return $this->equipoVisible($user, $equipo);
    }

    /**
     * Desactivación lógica: activo = false → "Dado de baja".
     * El registro permanece en BD para auditoría e historial.
     */
    public function delete(User $user, Equipo $equipo): bool
    {
        if (! $equipo->activo) {
            return false; // Ya está dado de baja
        }

        return $this->equipoVisible($user, $equipo);
    }

    /**
     * Eliminación definitiva (SoftDelete real / deleted_at).
     * Solo Administrador — cubierto por before(), nunca llega aquí para Analista.
     */
    public function forceDelete(User $user, Equipo $equipo): bool
    {
        return false;
    }

    /**
     * Restaurar un equipo eliminado con SoftDelete.
     * Solo Administrador — cubierto por before().
     */
    public function restore(User $user, Equipo $equipo): bool
    {
        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper privado — lógica de visibilidad del Analista
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Un Analista puede ver/gestionar un equipo cuando:
     *
     *   1. El equipo pertenece a su empresa activa (empresa_id = empresa_activa_id).
     *      → Esto cubre los equipos de la sede donde trabaja físicamente.
     *
     *   2. O la UBICACIÓN del equipo está marcada como foránea (es_estado = true).
     *      → Equipos en estados/ubicaciones remotas, visibles por todos los analistas.
     *
     * Si el Analista no tiene empresa activa (sesión incompleta), se deniega todo.
     */
    private function equipoVisible(User $user, Equipo $equipo): bool
    {
        $empresaActiva = $user->empresa_activa_id;

        if (! $empresaActiva) {
            return false;
        }

        // Condición 1: el equipo es de la empresa activa del analista
        if ((int) $equipo->empresa_id === (int) $empresaActiva) {
            return true;
        }

        // Condición 2: la ubicación del equipo es foránea (es_estado = true)
        // Cargamos la relación solo si no está ya cargada para no hacer N+1
        $ubicacion = $equipo->relationLoaded('ubicacion')
            ? $equipo->ubicacion
            : $equipo->ubicacion()->first();

        return $ubicacion?->es_estado === true;
    }
}