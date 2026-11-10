<?php

namespace App\Console\Commands;

use App\Models\MantenimientoEvidencia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Mueve las fotos de evidencia del disco público (storage/app/public, que
 * cualquiera puede abrir con /storage/...) al disco privado
 * (storage/app/private), que solo se sirve por una ruta con permisos.
 *
 * Se puede correr más de una vez: lo ya movido se salta. Mientras no se
 * corra, la app sigue mostrando las fotos viejas (las busca en ambos
 * discos), pero siguen siendo públicas.
 *
 * También informa de las fotos "huérfanas" en el disco público (archivos
 * de mantenimientos/ que ya no pertenecen a ningún caso, por ejemplo de
 * casos borrados). Con --borrar-huerfanas se eliminan.
 */
class MoverEvidenciasPrivadas extends Command
{
    protected $signature = 'cerberus:mover-evidencias-privadas
                            {--borrar-huerfanas : Borra del disco público las fotos que no pertenecen a ningún caso}';

    protected $description = 'Mueve las fotos de evidencia de mantenimientos al disco privado (ya no accesibles sin sesión).';

    public function handle(): int
    {
        $privado = Storage::disk(MantenimientoEvidencia::DISCO);
        $publico = Storage::disk(MantenimientoEvidencia::DISCO_ANTERIOR);

        $movidas = $yaPrivadas = 0;
        $faltantes = [];

        foreach (MantenimientoEvidencia::orderBy('id')->cursor() as $evidencia) {
            $ruta = $evidencia->ruta_archivo;

            if ($privado->exists($ruta)) {
                $publico->delete($ruta); // por si quedó una copia pública
                $yaPrivadas++;
                continue;
            }

            if (! $publico->exists($ruta)) {
                $faltantes[] = "#{$evidencia->id} (caso #{$evidencia->mantenimiento_id}): {$ruta}";
                continue;
            }

            // Copia, verifica y recién entonces borra la pública.
            $privado->writeStream($ruta, $publico->readStream($ruta));

            if ($privado->size($ruta) !== $publico->size($ruta)) {
                $privado->delete($ruta);
                $this->error("No se pudo copiar completa la foto #{$evidencia->id}; se dejó donde estaba.");
                continue;
            }

            $publico->delete($ruta);
            $movidas++;
        }

        $this->info("Fotos movidas al disco privado: {$movidas}. Ya estaban en privado: {$yaPrivadas}.");

        if ($faltantes) {
            $this->warn(count($faltantes) . ' foto(s) registradas no tienen archivo en ningún disco:');
            foreach ($faltantes as $linea) {
                $this->line("   - {$linea}");
            }
        }

        $this->revisarHuerfanas($publico);

        return self::SUCCESS;
    }

    private function revisarHuerfanas($publico): void
    {
        $registradas = MantenimientoEvidencia::pluck('ruta_archivo')->flip();
        $huerfanas   = collect($publico->files('mantenimientos'))->reject(fn ($ruta) => $registradas->has($ruta));

        if ($huerfanas->isEmpty()) {
            return;
        }

        if (! $this->option('borrar-huerfanas')) {
            $this->warn("{$huerfanas->count()} foto(s) en el disco público no pertenecen a ningún caso y siguen siendo públicas. Para borrarlas, repite con --borrar-huerfanas.");
            return;
        }

        $publico->delete($huerfanas->all());
        $this->info("{$huerfanas->count()} foto(s) huérfanas borradas del disco público.");
    }
}
