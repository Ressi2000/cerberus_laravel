<?php

namespace App\Services;

use App\Models\Firma;
use App\Notifications\FirmaCompletadaNotification;
use Illuminate\Database\Eloquent\Model;

/**
 * Orquesta el ciclo de vida de la firma digital remota: crea las firmas
 * pendientes apenas se genera la planilla (sus enlaces firmados se exponen
 * luego en la página de verificación, destino del QR impreso) y, al
 * completarse todos los roles, avisa por la notificación interna.
 */
class FirmaService
{
    /**
     * Crea (si no existen) las firmas pendientes para un documento. Se llama
     * apenas se genera el PDF, antes de que exista cualquier enlace de firma.
     */
    public function inicializar(string $tipo, Model $documento): void
    {
        $firmantes = FirmaResolver::firmantes($tipo, $documento);

        foreach (FirmaResolver::rolesAplicables($tipo) as $rol) {
            $firmante = $firmantes[$rol] ?? null;

            if (! $firmante) {
                continue;
            }

            Firma::firstOrCreate(
                [
                    'firmable_type' => $documento::class,
                    'firmable_id'   => $documento->id,
                    'rol'           => $rol,
                ],
                [
                    'user_id' => $firmante->id,
                    'estado'  => 'pendiente',
                ]
            );
        }
    }

    /**
     * Registra el trazo capturado para una firma pendiente y, si con esto
     * el documento queda completamente firmado, dispara el cierre del flujo.
     */
    public function registrar(Firma $firma, string $imagenBase64, string $ip, ?string $userAgent): void
    {
        $firma->update([
            'estado'     => 'firmado',
            'imagen'     => $imagenBase64,
            'ip'         => $ip,
            'user_agent' => $userAgent,
            'firmado_at' => now(),
        ]);

        $tipo       = $this->tipoDesdeClase($firma->firmable_type);
        $documento  = $firma->firmable;
        $rolesFalta = FirmaResolver::rolesAplicables($tipo);

        $completo = $documento->firmas()
            ->get()
            ->whereIn('rol', $rolesFalta)
            ->every(fn (Firma $f) => $f->estaFirmada());

        if ($completo) {
            $this->completar($tipo, $documento);
        }
    }

    private function completar(string $tipo, Model $documento): void
    {
        $titulo = FirmaResolver::tituloDocumento($tipo);
        $folio  = Folio::etiqueta($tipo, $documento->id);

        foreach (FirmaResolver::firmantes($tipo, $documento) as $firmante) {
            $firmante?->notify(new FirmaCompletadaNotification($titulo, $folio));
        }
    }

    private function tipoDesdeClase(string $clase): string
    {
        return match ($clase) {
            \App\Models\Asignacion::class => 'asignacion',
            \App\Models\Prestamo::class   => 'prestamo',
            \App\Models\Traslado::class   => 'traslado',
        };
    }
}
