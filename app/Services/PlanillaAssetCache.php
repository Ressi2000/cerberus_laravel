<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PlanillaAssetCache
{
    /**
     * Devuelve la imagen en public/{$relativePath} como data URI base64,
     * cacheada para evitar leer y codificar el archivo en cada PDF generado.
     */
    public static function base64(string $relativePath): ?string
    {
        return Cache::rememberForever("planilla-asset:{$relativePath}", function () use ($relativePath) {
            $path = public_path($relativePath);

            if (! file_exists($path)) {
                return null;
            }

            $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        });
    }
}
