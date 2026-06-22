<?php

namespace App\Services;

use App\Models\Firma;
use App\Notifications\FirmaCompletadaNotification;
use App\Notifications\SolicitudFirmaNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Orquesta el ciclo de vida de la firma digital remota: crear las
 * solicitudes pendientes, notificar a cada firmante con un enlace firmado,
 * y al completarse todos los roles, regenerar el PDF con las firmas
 * estampadas y distribuirlo por correo.
 */
class FirmaService
{
    public function __construct(
        private PlanillaService $planillas,
        private PlanillaPrestamoService $planillasPrestamo,
    ) {}

    /**
     * Crea (o reutiliza) las firmas pendientes para un documento y notifica
     * a cada firmante con un enlace firmado para estampar su firma.
     */
    public function solicitar(string $tipo, Model $documento): void
    {
        $firmantes = FirmaResolver::firmantes($tipo, $documento);
        $titulo    = FirmaResolver::tituloDocumento($tipo);
        $folio     = Folio::etiqueta($tipo, $documento->id);

        foreach (FirmaResolver::rolesAplicables($tipo) as $rol) {
            $firmante = $firmantes[$rol] ?? null;

            if (! $firmante) {
                continue;
            }

            $firma = Firma::firstOrCreate(
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

            if ($firma->estaFirmada()) {
                continue;
            }

            $url = URL::signedRoute('firma.show', [
                'tipo' => $tipo,
                'id'   => $documento->id,
                'rol'  => $rol,
            ], now()->addDays(7));

            $firmante->notify(new SolicitudFirmaNotification($firma, $titulo, $folio, $url));
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
        $titulo  = FirmaResolver::tituloDocumento($tipo);
        $folio   = Folio::etiqueta($tipo, $documento->id);
        $archivo = strtolower(str_replace(' ', '_', $titulo)).'-'.$folio.'.pdf';

        $pdf = match ($tipo) {
            'asignacion' => $this->planillas->asignacion($documento),
            'prestamo'   => $this->planillasPrestamo->prestamo($documento),
            'traslado'   => $this->planillas->traslado($documento),
        };

        $binario = $pdf->output();

        foreach (FirmaResolver::firmantes($tipo, $documento) as $firmante) {
            if ($firmante) {
                $firmante->notify(new FirmaCompletadaNotification($titulo, $folio, $binario, $archivo));
            }
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
