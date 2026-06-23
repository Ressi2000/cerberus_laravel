<?php

namespace App\Services;

/**
 * Formatea el folio legible que se imprime junto al QR de verificación
 * (ej. "AS-000418"). La seguridad real la da la URL firmada de Laravel
 * detrás del QR — el folio es solo una referencia visual para humanos.
 */
class Folio
{
    private const PREFIJOS = [
        'asignacion' => 'AS',
        'prestamo'   => 'PR',
        'traslado'   => 'TR',
    ];

    public static function etiqueta(string $tipo, int $id): string
    {
        $prefijo = self::PREFIJOS[$tipo] ?? throw new \InvalidArgumentException("Tipo de folio desconocido: {$tipo}");

        return $prefijo . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
