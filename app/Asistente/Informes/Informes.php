<?php

namespace App\Asistente\Informes;

use App\Asistente\HerramientaException;
use App\Asistente\Herramientas;
use App\Exports\InformeAsistenteExport;
use App\Models\Empresa;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Informes en PDF o Excel a partir de cualquier consulta del asistente.
 *
 * El bot no escribe el archivo: pide "esta consulta, en este formato, con
 * este título". Cerberus guarda ese pedido (24 h) y entrega un enlace
 * firmado y temporal. Al descargarlo, Cerberus vuelve a ejecutar la consulta
 * con los permisos de quien la pidió —datos al momento— y arma el archivo con
 * el diseño de las planillas. Solo quien lo pidió puede descargarlo.
 */
class Informes
{
    const HORAS_VIGENCIA = 24;

    /** Consultas que no tiene sentido exportar. */
    const NO_EXPORTABLES = ['generar_informe', 'mostrar_grafico'];

    public function __construct(private Herramientas $herramientas, private Tabulador $tabulador)
    {
    }

    /**
     * Prepara el informe: comprueba que la consulta funciona y devuelve el
     * enlace de descarga.
     */
    public function preparar(User $actor, string $consulta, array $args, string $formato, string $titulo, ?string $resumen): array
    {
        if (in_array($consulta, self::NO_EXPORTABLES, true) || ! $this->herramientas->obtener($consulta)) {
            throw new HerramientaException("«{$consulta}» no es una consulta que se pueda exportar.");
        }

        $resultado = $this->herramientas->ejecutar($consulta, $actor, $args, paraInforme: true);
        if (! $resultado['ok']) {
            throw new HerramientaException('No se pudo armar el informe: ' . $resultado['error']);
        }

        $id = (string) Str::uuid();
        Cache::put($this->clave($id), [
            'user_id'  => $actor->id,
            'consulta' => $consulta,
            'args'     => $args,
            'formato'  => $formato,
            'titulo'   => mb_substr(trim($titulo) ?: 'Informe', 0, 120),
            'resumen'  => $resumen ? mb_substr(trim($resumen), 0, 3000) : null,
            'creado'   => now()->toIso8601String(),
        ], now()->addHours(self::HORAS_VIGENCIA));

        $partes = $this->tabulador->partes($resultado['datos']);

        return [
            'archivo' => $this->nombreArchivo($titulo, $formato),
            'formato' => $formato === 'excel' ? 'Excel' : 'PDF',
            'filas'   => collect($partes['tablas'])->sum(fn ($t) => count($t['filas'])),
            'url'     => URL::temporarySignedRoute('asistente.informes.descargar', now()->addHours(self::HORAS_VIGENCIA), ['informe' => $id]),
        ];
    }

    /** Genera el archivo de un informe preparado. */
    public function descargar(User $actor, string $id)
    {
        $pedido = Cache::get($this->clave($id));
        abort_unless($pedido, 410, 'Este informe ya venció. Pídeselo de nuevo al asistente.');
        abort_unless((int) $pedido['user_id'] === (int) $actor->id, 403);

        $resultado = $this->herramientas->ejecutar($pedido['consulta'], $actor, $pedido['args'], paraInforme: true);
        abort_unless($resultado['ok'], 422, $resultado['error'] ?? 'No se pudo armar el informe.');

        $contenido = [
            'titulo'     => $pedido['titulo'],
            'resumen'    => $pedido['resumen'],
            'partes'     => $this->tabulador->partes($resultado['datos']),
            'generadoPor' => $actor->name,
            'fecha'      => now()->format('d/m/Y H:i'),
            'empresa'    => $actor->empresa_activa_id ? Empresa::find($actor->empresa_activa_id)?->nombre : null,
        ];
        $archivo = $this->nombreArchivo($pedido['titulo'], $pedido['formato']);

        if ($pedido['formato'] === 'excel') {
            return Excel::download(new InformeAsistenteExport($contenido), $archivo);
        }

        $orientacion = collect($contenido['partes']['tablas'])->max(fn ($t) => count($t['columnas'])) > 6 ? 'landscape' : 'portrait';

        return Pdf::loadView('asistente.informe-pdf', $contenido)->setPaper('letter', $orientacion)->download($archivo);
    }

    private function nombreArchivo(string $titulo, string $formato): string
    {
        return (Str::slug(Str::limit($titulo, 60, ''), '-') ?: 'informe') . '-' . now()->format('Ymd-Hi')
            . ($formato === 'excel' ? '.xlsx' : '.pdf');
    }

    private function clave(string $id): string
    {
        return "asistente:informe:{$id}";
    }
}
