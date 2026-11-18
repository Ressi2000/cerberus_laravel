<?php

namespace App\Exceptions;

/**
 * Error de validación que pertenece a un campo concreto del formulario
 * (ej. los seriales del 2.º equipo de origen), para mostrarlo junto a ese campo.
 */
class ErrorEnCampoException extends \InvalidArgumentException
{
    public function __construct(public readonly string $campo, string $mensaje)
    {
        parent::__construct($mensaje);
    }
}
