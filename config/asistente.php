<?php

/*
 * Cerberus Bot (asistente con IA).
 *
 * Está en PRUEBA: se activa solo donde ASISTENTE_HABILITADO=true (QA).
 * En producción no se pone (o se deja en false) y no aparece en ningún lado.
 */
return [

    'habilitado' => (bool) env('ASISTENTE_HABILITADO', false),

    'nombre' => env('ASISTENTE_NOMBRE', 'Cerberus Bot'),

    // Por ahora solo Gemini; las herramientas (app/Asistente) sirven para cualquier proveedor.
    'proveedor' => 'gemini',

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'modelo'  => env('GEMINI_MODEL', 'gemini-3.8-flash'),
        'url'     => 'https://generativelanguage.googleapis.com/v1beta',
        'timeout' => 60,
    ],

    // Cuántas consultas puede encadenar la IA para responder una pregunta.
    'max_pasos' => 8,

    // Preguntas por usuario por hora (evita gastos sorpresa).
    'preguntas_por_hora' => (int) env('ASISTENTE_PREGUNTAS_POR_HORA', 40),

    // Mensajes de la conversación que se reenvían a la IA como contexto.
    'max_turnos_memoria' => 10,
];
