<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Modelo MantenimientoEvidencia
 *
 * Galería de fotos de un mantenimiento/reparación (antes/durante/después).
 */
class MantenimientoEvidencia extends Model
{
    protected $table = 'mantenimiento_evidencias';

    protected $fillable = [
        'mantenimiento_id',
        'ruta_archivo',
        'tipo',
        'subido_por_id',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por_id');
    }

    /**
     * Disco PRIVADO (storage/app/private): las fotos no se pueden abrir con
     * una URL directa; solo por la ruta admin.mantenimientos.evidencias.show,
     * que exige sesión y permiso para ver el caso.
     */
    const DISCO = 'local';

    /**
     * Disco donde se guardaban antes (público, accesible por /storage/...).
     * Solo se lee de aquí mientras no se haya corrido
     * `php artisan cerberus:mover-evidencias-privadas`.
     */
    const DISCO_ANTERIOR = 'public';

    /** Dirección protegida de la foto (sirve para el <img> y para abrirla). */
    public function url(): string
    {
        return route('admin.mantenimientos.evidencias.show', [$this->mantenimiento_id, $this->id]);
    }

    /** Disco donde está el archivo hoy: el privado, o el público si todavía no se movió. */
    public function discoDelArchivo(): ?string
    {
        foreach ([self::DISCO, self::DISCO_ANTERIOR] as $disco) {
            if (Storage::disk($disco)->exists($this->ruta_archivo)) {
                return $disco;
            }
        }

        return null;
    }

    /** Borra el archivo del disco, esté donde esté. */
    public function borrarArchivo(): void
    {
        foreach ([self::DISCO, self::DISCO_ANTERIOR] as $disco) {
            Storage::disk($disco)->delete($this->ruta_archivo);
        }
    }
}
