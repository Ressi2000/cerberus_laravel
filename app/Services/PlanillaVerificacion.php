<?php

namespace App\Services;

use Illuminate\Support\Facades\URL;

/**
 * Genera el folio + QR de verificación que se embebe en las planillas PDF,
 * apuntando a la ruta pública firmada 'verificacion.show'.
 */
class PlanillaVerificacion
{
    public static function datos(string $tipo, int $id): array
    {
        $url = URL::signedRoute('verificacion.show', ['tipo' => $tipo, 'id' => $id]);

        return [
            'qrBase64' => QrCodeGenerator::base64Png($url),
            'folio'    => Folio::etiqueta($tipo, $id),
        ];
    }
}
