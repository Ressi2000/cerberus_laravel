<?php

namespace App\Asistente;

/**
 * Error "esperado" de una herramienta (no encontró el equipo, falta un
 * parámetro…). Su mensaje está pensado para mostrarlo tal cual al usuario.
 */
class HerramientaException extends \RuntimeException
{
}
