<?php

namespace App\Asistente\Proveedores;

use App\Asistente\Respuesta;
use App\Models\User;

/**
 * Un servicio de IA (Gemini hoy; mañana Copilot, Claude…). Recibe la
 * pregunta y la conversación previa, usa las herramientas de Cerberus las
 * veces que necesite y devuelve la respuesta final.
 */
interface ProveedorIA
{
    public function responder(User $actor, string $pregunta, array $historial, string $instrucciones): Respuesta;
}
