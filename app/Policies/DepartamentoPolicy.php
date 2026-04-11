<?php
 
namespace App\Policies;
 
use App\Models\Departamento;
use App\Models\User;
 
class DepartamentoPolicy
{
    /**
     * before(): cortocircuita para Admin (acceso total)
     * y bloquea a roles sin permisos de gestión.
     *
     * Regla CERBERUS:
     *   Administrador → CRUD completo.
     *   Analista      → solo lectura/contexto (NO create/update/delete).
     *   Usuario       → sin acceso.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Admin pasa todo sin restricciones
        if ($user->hasRole('Administrador')) {
            return true;
        }
 
        // Analista y Usuario: solo viewAny y view (lectura)
        if (in_array($ability, ['viewAny', 'view'])) {
            return null; // continúa evaluando el método específico
        }
 
        // Cualquier otra acción (create, update, delete): denegada
        return false;
    }
 
    public function viewAny(User $user): bool
    {
        return true; // Admin y Analista pueden listar (contexto en selects)
    }
 
    public function view(User $user, Departamento $departamento): bool
    {
        return true; // Admin y Analista pueden ver el detalle
    }
 
    public function create(User $user): bool
    {
        return false; // Llega aquí solo si before() no cortocircuitó (no Admin)
    }
 
    public function update(User $user, Departamento $departamento): bool
    {
        return false;
    }
 
    public function delete(User $user, Departamento $departamento): bool
    {
        return false;
    }
 
    public function restore(User $user, Departamento $departamento): bool
    {
        return false;
    }
}
