<?php

namespace App\Asistente;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Una consulta de solo lectura sobre los datos de Cerberus.
 *
 * Es la pieza con la que trabajará el asistente (el chat de Cerberus o
 * Copilot): la IA no escribe SQL ni ve la base de datos, solo elige una
 * herramienta, le pasa parámetros y recibe datos ya filtrados por lo que el
 * usuario puede ver. Por eso:
 *
 *  - Nunca modifica nada.
 *  - Siempre parte de los scopes visiblePara() del modelo: un analista solo
 *    recibe datos de su empresa activa, igual que en las pantallas.
 *  - Devuelve arreglos simples (texto, números, fechas, enlaces) que se
 *    pueden mostrar, convertir a JSON o pasar a un informe.
 *
 * Las herramientas son genéricas a propósito: la IA las combina para
 * responder preguntas que nadie previó ("¿qué equipos de Contabilidad
 * tienen más de 3 reparaciones?" = buscar_equipos + historial_equipo).
 */
abstract class Herramienta
{
    /**
     * true mientras se arma un informe (PDF/Excel): las listas no se cortan
     * en los 20-100 elementos pensados para el chat.
     */
    public static bool $modoInforme = false;

    /** Tope de elementos en un informe. */
    const MAX_INFORME = 2000;

    /** Nombre corto en snake_case con el que la IA la llama. */
    abstract public function nombre(): string;

    /** Para qué sirve y cuándo usarla (lo lee la IA para elegir). */
    abstract public function descripcion(): string;

    /**
     * Parámetros que acepta, en formato JSON Schema (solo "properties" y
     * "required"). Ej.: ['texto' => ['type' => 'string', 'description' => '…']].
     */
    abstract public function parametros(): array;

    /** Parámetros obligatorios. */
    public function requeridos(): array
    {
        return [];
    }

    /** Ejecuta la consulta. Los parámetros ya vienen validados. */
    abstract public function ejecutar(User $actor, array $args): array;

    /** Definición lista para entregarla a un modelo de IA (tool use / MCP). */
    public function definicion(): array
    {
        return [
            'name'         => $this->nombre(),
            'description'  => $this->descripcion(),
            'input_schema' => [
                'type'       => 'object',
                'properties' => (object) $this->parametros(),
                'required'   => $this->requeridos(),
            ],
        ];
    }

    // ── Ayudas comunes ─────────────────────────────────────────────────────

    protected function limite(array $args, int $porDefecto = 20, int $maximo = 100): int
    {
        if (self::$modoInforme) {
            return self::MAX_INFORME;
        }

        return max(1, min($maximo, (int) ($args['limite'] ?? $porDefecto)));
    }

    protected function fecha($valor, bool $conHora = false): ?string
    {
        if (! $valor) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($valor)->format($conHora ? 'd/m/Y H:i' : 'd/m/Y');
    }

    /** "%texto%" escapando los comodines de LIKE. */
    protected function like(string $texto): string
    {
        return '%' . addcslashes(trim($texto), '%_\\') . '%';
    }

    /**
     * Busca UN equipo visible por su código interno (exacto primero,
     * luego parecido) o por su id.
     */
    protected function encontrarEquipo(User $actor, string $codigo): Equipo
    {
        $codigo = trim($codigo);
        $base = fn (): Builder => Equipo::visiblePara($actor);

        $equipo = $base()->where('codigo_interno', $codigo)->first()
            ?? (ctype_digit($codigo) ? $base()->whereKey((int) $codigo)->first() : null);

        if ($equipo) {
            return $equipo;
        }

        $parecidos = $base()->where('codigo_interno', 'like', $this->like($codigo))->limit(6)->pluck('codigo_interno');

        if ($parecidos->count() === 1) {
            return $base()->where('codigo_interno', $parecidos->first())->first();
        }

        throw new HerramientaException($parecidos->isEmpty()
            ? "No encontré ningún equipo con el código «{$codigo}» (o no tienes acceso a él)."
            : "Hay varios equipos parecidos a «{$codigo}»: " . $parecidos->implode(', ') . '. ¿Cuál de ellos?');
    }

    /** Resumen de un equipo para listas. */
    protected function resumenEquipo(Equipo $e): array
    {
        $item = $e->relationLoaded('asignacionItemActivo') ? $e->asignacionItemActivo : null;

        return array_filter([
            'codigo'     => $e->codigo_interno,
            'categoria'  => $e->categoria?->nombre,
            'estado'     => $e->estado?->nombre,
            'ubicacion'  => $e->ubicacion?->nombre,
            'asignado_a' => $item?->asignacion?->receptorNombre(),
            'url'        => route('admin.equipos.show', $e),
        ], fn ($v) => $v !== null);
    }
}
