<?php

/*
|--------------------------------------------------------------------------
| Monitor de red
|--------------------------------------------------------------------------
|
| Parámetros del módulo de monitoreo de disponibilidad (ping + respaldo TCP).
| Las revisiones las ejecuta el scheduler (`cerberus:monitor-red`) cada
| minuto; la pantalla solo lee el último estado guardado.
|
*/

return [

    // Segundos de espera por host (ping y conexión TCP de respaldo).
    'timeout' => (int) env('MONITOR_RED_TIMEOUT', 2),

    // Fallos consecutivos necesarios para declarar un host "Caído".
    // Con 1, un solo paquete perdido ya genera caída y alerta (falsos positivos).
    'fallos_para_caida' => (int) env('MONITOR_RED_FALLOS_PARA_CAIDA', 2),

    // Pings simultáneos por revisión (procesos en paralelo).
    'concurrencia' => (int) env('MONITOR_RED_CONCURRENCIA', 20),

    // Segundos entre refrescos automáticos de la pantalla (solo lectura de BD).
    'intervalo_pantalla' => (int) env('MONITOR_RED_INTERVALO_PANTALLA', 15),

    // Días que se conservan las mediciones de latencia (gráficas).
    // El historial de caídas no se purga.
    'retencion_mediciones_dias' => (int) env('MONITOR_RED_RETENCION_DIAS', 15),

    // Enviar notificaciones de Cerberus (campana + correo) en caídas y recuperaciones.
    'notificar' => (bool) env('MONITOR_RED_NOTIFICAR', true),

    // Tipos sugeridos en el formulario (se puede escribir cualquier otro).
    'tipos_sugeridos' => ['Router', 'Switch', 'Servidor', 'Firewall', 'Access Point', 'Impresora', 'Cámara', 'Servicio', 'Otro'],
];
