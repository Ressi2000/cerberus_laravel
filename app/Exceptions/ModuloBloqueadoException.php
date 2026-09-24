<?php

namespace App\Exceptions;

/**
 * Se lanza cuando una acción de negocio se bloquea por una regla explícita
 * del dominio (ej. devolver un equipo con un mantenimiento/reparación
 * abierto). El mensaje está pensado para mostrarse tal cual al usuario.
 */
class ModuloBloqueadoException extends \RuntimeException
{
}
