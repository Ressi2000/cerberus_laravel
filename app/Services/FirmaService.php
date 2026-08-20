<?php

namespace App\Services;

use App\Models\Firma;
use App\Notifications\FirmaCompletadaNotification;
use App\Notifications\PlanillaFirmadaNotification;
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

        $this->enviarPlanillaFirmadaPorCorreo($tipo, $documento, $folio);
    }

    /**
     * Envía al receptor (solo a él, no al analista ni a su jefe) la planilla
     * ya firmada por ambas partes, adjunta en PDF. Por ahora solo cubre
     * asignación y préstamo — traslado queda fuera de este flujo.
     */
    private function enviarPlanillaFirmadaPorCorreo(string $tipo, Model $documento, string $folio): void
    {
        if (! in_array($tipo, ['asignacion', 'prestamo'], true)) {
            return;
        }

        $receptor = FirmaResolver::firmantes($tipo, $documento)['receptor'] ?? null;

        if (! $receptor?->email) {
            return;
        }

        $pdf = match ($tipo) {
            'asignacion' => app(PlanillaService::class)->asignacion($documento),
            'prestamo'   => app(PlanillaPrestamoService::class)->prestamo($documento),
        };

        $receptor->notify(new PlanillaFirmadaNotification(
            tipo: $tipo,
            folio: $folio,
            totalItems: $documento->items()->count(),
            empresaNombre: $documento->empresa?->nombre,
            // Base64: la notificación se encola y el payload se serializa a
            // JSON, que exige UTF-8 válido — el PDF binario lo rompe.
            pdfContenidoBase64: base64_encode($pdf->output()),
        ));
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
