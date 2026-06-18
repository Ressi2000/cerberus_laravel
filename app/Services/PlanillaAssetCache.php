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
        $path = public_path($relativePath);

        if (! file_exists($path)) {
            return null;
        }

        // El mtime entra en la cache key para que un reemplazo del archivo
        // (p. ej. al optimizar su tamaño) invalide automáticamente la cache.
        $cacheKey = "planilla-asset:{$relativePath}:" . filemtime($path);

        return Cache::rememberForever($cacheKey, function () use ($path) {
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
