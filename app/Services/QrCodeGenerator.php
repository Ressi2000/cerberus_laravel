<?php

namespace App\Services;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;

/**
 * Genera códigos QR pequeños (PNG) para embeber en las planillas PDF.
 * Un QR de verificación es solo texto+blanco/negro, así que el PNG
 * resultante pesa unos pocos KB — no repite el problema de las imágenes
 * de los logos.
 */
class QrCodeGenerator
{
    public static function base64Png(string $contenido, int $sizePx = 160): string
    {
        $renderer = new GDLibRenderer($sizePx, margin: 2);
        $writer   = new Writer($renderer);

        return 'data:image/png;base64,' . base64_encode($writer->writeString($contenido));
    }
}
