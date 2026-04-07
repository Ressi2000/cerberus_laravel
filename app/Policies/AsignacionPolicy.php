<?php

namespace App\Policies;

use App\Models\Asignacion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * AsignacionPolicy
 *
 * Control de acceso para el módulo de asignaciones.
 * Sigue exactamente el mismo patrón que EquipoPolicy:
 *   - before()      → cortocircuita Administrador (todo permitido) y Usuario (todo denegado)
 *   - Analista      → filtrado por empresa_activa_id
 *
 * Permisos cubiertos:
 *   viewAny  → ver el listado de asignaciones
 *   view     → ver el detalle de una asignación específica
 *   create   → crear una nueva asignación
 *   devolver → registrar devolución total o parcial
 *   delete   → eliminación administrativa (solo Administrador, vía before())
 */
class AsignacionPolicy
{
    use HandlesAuthorization;

    /**
     * Cortocircuito antes de cualquier verificación.
     *  - Administrador → null (continúa a la policy específica sin restricciones)
     *  - Usuario       → false (denegado siempre, sin llegar a los métodos)
     *  - Analista      → null (continúa a la validación de empresa)
     *
     * NOTA: retornar null desde before() permite que Laravel siga evaluando
     * el método específico de la policy. Retornar true o false cortocircuita.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador')) {
            return true;   // Admin puede todo — no llega a los métodos
        }

        if ($user->hasRole('Usuario')) {
            return false;  // Rol Usuario nunca accede — no llega a los métodos
        }

        return null;       // Analista → evalúa el método específico
    }

    /**
     * Ver el listado de asignaciones.
     * El Analista solo ve las asignaciones de su empresa activa (filtrado en query).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    /**
     * Ver el detalle de una asignación específica.
     * El Analista solo puede ver asignaciones de su empresa activa.
     */
    public function view(User $user, Asignacion $asignacion): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $asignacion->empresa_id;
    }

    /**
     * Crear una nueva asignación.
     * Solo Analistas con empresa activa establecida.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Analista') && $user->empresa_activa_id !== null;
    }

    /**
     * Registrar devolución (total o parcial) de una asignación.
     * Solo si la asignación pertenece a la empresa activa del Analista
     * y aún tiene items activos.
     */
    public function devolver(User $user, Asignacion $asignacion): bool
    {
        return $user->hasRole('Analista')
            && $user->empresa_activa_id === $asignacion->empresa_id
            && $asignacion->estado !== 'Cerrada';
    }

    /**
     * Eliminación administrativa.
     * Solo accesible para el Administrador (manejado por before()).
     * Este método nunca llega a ejecutarse para otros roles.
     */
    public function delete(User $user, Asignacion $asignacion): bool
    {
        return false; // Solo Admin (cortocircuitado arriba en before())
    }
}