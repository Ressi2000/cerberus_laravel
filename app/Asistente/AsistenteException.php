<?php

namespace App\Asistente;

/**
 * Falla al hablar con la IA. El mensaje es para el usuario (no menciona al
 * proveedor); el detalle técnico es para el Administrador y la bitácora.
 */
class AsistenteException extends \RuntimeException
{
    public function __construct(string $mensaje, public readonly ?string $detalle = null)
    {
        parent::__construct($mensaje);
    }
}
