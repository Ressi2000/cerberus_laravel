<?php

namespace App\Asistente\Proveedores;

use App\Asistente\Privacidad;
use App\Asistente\Respuesta;
use App\Models\User;

/**
 * Un servicio de IA (Gemini hoy; mañana Copilot, Claude…). Recibe la
 * pregunta y la conversación previa, usa las herramientas de Cerberus las
 * veces que necesite y devuelve la respuesta final.
 */
interface ProveedorIA
{
    /**
     * $pregunta, $historial e $instrucciones ya vienen con los nombres
     * ocultos; el proveedor usa $privacidad al ejecutar cada herramienta.
     */
    public function responder(User $actor, string $pregunta, array $historial, string $instrucciones, Privacidad $privacidad): Respuesta;
}
