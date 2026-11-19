<?php

namespace App\Asistente;

use App\Models\AtributoEquipo;
use App\Models\Equipo;
use Illuminate\Support\Collection;

/**
 * Ayudas para que el asistente trabaje con las características de los
 * equipos (EAV) sin depender de cómo se llamen: "RAM", "RAM (GB)" o
 * "Memoria" se encuentran igual, y en una característica de varias entradas
 * (RAM, discos) se puede filtrar por uno de sus campos ("Capacidad (GB)",
 * "Tipo").
 */
class Caracteristicas
{
    /** Ids de las características que nunca se leen (PUK, claves…; ver config). */
    public static function ocultas(): array
    {
        return AtributoEquipo::query()->get(['id', 'nombre'])
            ->filter(fn ($a) => self::esOculta($a->nombre))
            ->pluck('id')->all();
    }

    public static function esOculta(string $nombre): bool
    {
        $palabras = explode(' ', self::normalizar($nombre));

        return (bool) array_intersect($palabras, config('asistente.caracteristicas_ocultas', []));
    }

    /** minúsculas, sin tildes ni signos: "RAM (GB)" → "ram gb". */
    public static function normalizar(?string $texto): string
    {
        $t = mb_strtolower(trim((string) $texto));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $t));
    }

    /**
     * Qué característica (y campo, si es de varias entradas) corresponde al
     * nombre que pidió la IA. Devuelve [[atributo, campoId|null], …]: la
     * coincidencia más exacta de cada categoría.
     */
    public static function resolver(string $nombre, ?Collection $categoriaIds = null): array
    {
        $q = self::normalizar($nombre);
        if ($q === '') {
            return [];
        }

        $puntaje = function (string $candidato) use ($q): int {
            $c = self::normalizar($candidato);

            return match (true) {
                $c === $q                                   => 3,
                str_starts_with($c, $q . ' ') || str_starts_with($q, $c . ' ') => 2,
                str_contains($c, $q) || str_contains($q, $c) => 1,
                default                                     => 0,
            };
        };

        $candidatos = [];
        $atributos = AtributoEquipo::query()
            ->when($categoriaIds, fn ($w) => $w->whereIn('categoria_id', $categoriaIds))
            ->whereNotIn('id', self::ocultas())
            ->get();

        foreach ($atributos as $a) {
            if ($p = $puntaje($a->nombre)) {
                $candidatos[] = [$p, $a, null];
            }
            if ($a->esGrupo()) {
                foreach ($a->sub_campos ?? [] as $campo) {
                    if ($p = $puntaje($campo['nombre'] ?? '')) {
                        $candidatos[] = [$p, $a, $campo['id']];
                    }
                }
            }
        }

        if (! $candidatos) {
            return [];
        }

        // La mejor coincidencia de CADA categoría ("RAM" en Laptop y "RAM (GB)" en Desktop).
        return collect($candidatos)
            ->groupBy(fn ($c) => $c[1]->categoria_id)
            ->map(fn ($grupo) => $grupo->sortByDesc(fn ($c) => $c[0])->first())
            ->map(fn ($c) => [$c[1], $c[2]])->values()->all();
    }

    /** Valores de esa característica en un equipo (uno por entrada si es de varias). */
    public static function valores(Equipo $equipo, AtributoEquipo $atributo, ?string $campoId): array
    {
        if (! $atributo->esGrupo()) {
            $v = trim((string) $equipo->atributosActuales->firstWhere('atributo_id', $atributo->id)?->valor);

            return $v === '' ? [] : [$v];
        }

        $nombres = collect($atributo->sub_campos ?? [])->pluck('nombre', 'id');

        return $equipo->grupoInstancias->where('atributo_id', $atributo->id)
            ->map(function ($i) use ($campoId, $nombres) {
                $valores = $i->valores ?? [];
                if ($campoId) {
                    return trim((string) ($valores[$campoId] ?? ''));
                }

                return collect($valores)->reject(fn ($v) => $v === null || $v === '')
                    ->map(fn ($v, $id) => ($nombres[$id] ?? 'Campo') . ': ' . $v)->implode(', ');
            })
            ->filter(fn ($v) => $v !== '')->values()->all();
    }

    /**
     * Campo numérico de una característica de varias entradas, para sumar
     * (ej. "Capacidad (GB)" de la RAM: 8 + 8 = 16 GB en total).
     */
    public static function campoNumerico(AtributoEquipo $atributo): ?string
    {
        foreach ($atributo->sub_campos ?? [] as $campo) {
            $n = self::normalizar($campo['nombre'] ?? '');
            if (in_array($campo['tipo'] ?? '', ['integer', 'decimal', 'number'], true)
                || preg_match('/\b(capacidad|gb|tb|tamano|cantidad)\b/', $n)) {
                return $campo['id'];
            }
        }

        return null;
    }

    /** Primer número del texto, pasado a la unidad del campo si dice TB/MB y el campo es en GB. */
    public static function numero(?string $texto, string $nombreCampo = ''): ?float
    {
        if (! preg_match('/(\d+(?:[.,]\d+)?)/', (string) $texto, $m)) {
            return null;
        }
        $n = (float) str_replace(',', '.', $m[1]);
        $t = self::normalizar($texto);
        $enGb = str_contains(self::normalizar($nombreCampo), 'gb');

        return match (true) {
            $enGb && preg_match('/\btb\b/', $t) => $n * 1024,
            $enGb && preg_match('/\bmb\b/', $t) => $n / 1024,
            default                            => $n,
        };
    }
}
