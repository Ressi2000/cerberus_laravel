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
        // Si el principal está saturado o no existe, se usa este.
        'modelo_respaldo' => env('GEMINI_MODEL_RESPALDO', 'gemini-3.6-flash'),
        // Reintentos cuando Gemini responde "saturado" (503) y espera entre ellos.
        'reintentos' => 2,
        'espera_ms'  => 1500,
        'url'     => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => 60,
        // Si la red de la empresa sale a internet por un proxy: http://usuario:clave@proxy:puerto
        'proxy'   => env('GEMINI_PROXY'),
    ],

    // Seudónimos: los nombres de las personas viajan a la IA como "Persona-12"
    // y Cerberus los vuelve a poner al mostrar la respuesta.
    'seudonimos' => (bool) env('ASISTENTE_SEUDONIMOS', true),

    // Características de equipos que el asistente nunca lee ni envía a la IA
    // (palabras que aparecen en el nombre de la característica).
    'caracteristicas_ocultas' => ['puk', 'pin', 'clave', 'contrasena', 'password', 'key', 'token'],

    // Cuántas consultas puede encadenar la IA para responder una pregunta.
    'max_pasos' => 8,

    // Preguntas por usuario por hora (evita gastos sorpresa).
    'preguntas_por_hora' => (int) env('ASISTENTE_PREGUNTAS_POR_HORA', 40),

    // Mensajes de la conversación que se reenvían a la IA como contexto.
    'max_turnos_memoria' => 10,
];
