<?php

namespace App\Asistente;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Registro de las herramientas del asistente: las lista, valida los
 * parámetros y las ejecuta con los permisos del usuario.
 *
 * Uso: app(Herramientas::class)->ejecutar('ver_equipo', $usuario, ['codigo' => 'EQ-00057']).
 */
class Herramientas
{
    /** Clases de las herramientas disponibles. Para agregar una nueva, súmala aquí. */
    const CLASES = [
        Herramientas\BuscarEquipos::class,
        Herramientas\CaracteristicasEquipos::class,
        Herramientas\VerEquipo::class,
        Herramientas\HistorialEquipo::class,
        Herramientas\EquiposDePersona::class,
        Herramientas\BuscarPiezas::class,
        Herramientas\RecorridoPieza::class,
        Herramientas\StockAlmacen::class,
        Herramientas\CasosMantenimiento::class,
        Herramientas\RenovacionEquipos::class,
        Herramientas\ResumenInventario::class,
    ];

    /** @var array<string, Herramienta>|null */
    private ?array $instancias = null;

    /** @return array<string, Herramienta> */
    public function todas(): array
    {
        if ($this->instancias === null) {
            $this->instancias = [];
            foreach (self::CLASES as $clase) {
                $h = app($clase);
                $this->instancias[$h->nombre()] = $h;
            }
        }

        return $this->instancias;
    }

    public function obtener(string $nombre): ?Herramienta
    {
        return $this->todas()[$nombre] ?? null;
    }

    /** Definiciones para el modelo de IA. */
    public function definiciones(): array
    {
        return array_values(array_map(fn (Herramienta $h) => $h->definicion(), $this->todas()));
    }

    /** Solo el personal técnico usa el asistente (los usuarios finales no ven inventario). */
    public function puedeUsar(User $actor): bool
    {
        return $actor->hasAnyRole(['Administrador', 'Analista']);
    }

    /**
     * Ejecuta una herramienta. Nunca lanza excepciones hacia afuera:
     * devuelve ['ok' => true, 'datos' => …] o ['ok' => false, 'error' => '…'],
     * que es lo que la IA necesita para seguir la conversación.
     */
    public function ejecutar(string $nombre, User $actor, array $args = []): array
    {
        $herramienta = $this->obtener($nombre);

        if (! $herramienta) {
            return ['ok' => false, 'error' => "No existe la herramienta «{$nombre}»."];
        }
        if (! $this->puedeUsar($actor)) {
            return ['ok' => false, 'error' => 'Tu usuario no tiene acceso a las consultas del asistente.'];
        }

        try {
            $datos = $herramienta->ejecutar($actor, $this->validar($herramienta, $args));

            return ['ok' => true, 'datos' => $datos];
        } catch (HerramientaException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error("Asistente: falló la herramienta {$nombre}", ['args' => $args, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'No se pudo completar la consulta por un error interno.'];
        }
    }

    /** Quita parámetros desconocidos, convierte tipos y exige los obligatorios. */
    private function validar(Herramienta $h, array $args): array
    {
        $limpio = [];

        foreach ($h->parametros() as $nombre => $def) {
            $valor = $args[$nombre] ?? null;
            if ($valor === null || $valor === '') {
                continue;
            }

            $tipo = $def['type'] ?? 'string';
            $valor = match ($tipo) {
                'integer' => is_numeric($valor) ? (int) $valor : throw new HerramientaException("«{$nombre}» debe ser un número."),
                'boolean' => filter_var($valor, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? throw new HerramientaException("«{$nombre}» debe ser sí o no."),
                default   => is_scalar($valor) ? mb_substr(trim((string) $valor), 0, 200) : throw new HerramientaException("«{$nombre}» debe ser texto."),
            };

            if (isset($def['enum']) && ! in_array($valor, $def['enum'], true)) {
                throw new HerramientaException("«{$nombre}» debe ser uno de: " . implode(', ', $def['enum']) . '.');
            }

            $limpio[$nombre] = $valor;
        }

        foreach ($h->requeridos() as $req) {
            if (! array_key_exists($req, $limpio)) {
                throw new HerramientaException("Falta el dato «{$req}».");
            }
        }

        return $limpio;
    }
}
