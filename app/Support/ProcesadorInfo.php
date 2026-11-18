<?php

namespace App\Support;

/**
 * Lo que se puede saber de un procesador a partir de cómo está escrito en
 * la ficha ("i5-1135G7 @ 2.40GHz", "Intel Core i3 2328M", "Ultra 7 155U",
 * "AMD Ryzen 5 3500U", "Pentium(R) CPU G645T"…).
 *
 * - marca:       intel | amd | apple | null
 * - linea:       core | ultra | ryzen | gama_basica | antiguo | servidor | apple | null
 * - generacion:  Intel: generación (2, 3… 14; Core Ultra = 15). AMD Ryzen: serie (1, 2… 9).
 * - anio:        año aproximado de lanzamiento (para estimar la edad del equipo)
 * - nombre:      cómo mostrarlo ("Intel Core i5 · 11.ª gen")
 */
final class ProcesadorInfo
{
    /** Año de lanzamiento por generación de Intel Core. */
    const ANIO_INTEL = [1 => 2010, 2 => 2011, 3 => 2012, 4 => 2013, 5 => 2015, 6 => 2015, 7 => 2016, 8 => 2017,
        9 => 2018, 10 => 2019, 11 => 2020, 12 => 2021, 13 => 2022, 14 => 2023, 15 => 2024];

    /** Año de lanzamiento por serie de AMD Ryzen. */
    const ANIO_RYZEN = [1 => 2017, 2 => 2018, 3 => 2019, 4 => 2020, 5 => 2021, 6 => 2022, 7 => 2023, 8 => 2024, 9 => 2024];

    private function __construct(
        public readonly string $texto,
        public readonly ?string $marca = null,
        public readonly ?string $linea = null,
        public readonly ?int $generacion = null,
        public readonly ?int $anio = null,
        public readonly ?string $nombre = null,
    ) {}

    public function reconocido(): bool
    {
        return $this->linea !== null;
    }

    public static function desde(?string $texto): self
    {
        $original = trim((string) $texto);
        $t = strtoupper(preg_replace('/\((R|TM|C)\)|®|™/i', ' ', $original));
        $t = preg_replace('/\s+/', ' ', $t);

        if ($t === '') {
            return new self($original);
        }

        // ── Apple ───────────────────────────────────────────────────────────
        if (preg_match('/\bAPPLE\b|\bM[1-4]\b( PRO| MAX| ULTRA)?/', $t) && ! str_contains($t, 'INTEL')) {
            return new self($original, 'apple', 'apple', null, null, 'Apple Silicon');
        }

        // ── Intel Core Ultra (Meteor Lake y posteriores) ─────────────────────
        if (preg_match('/ULTRA ?[3579][ -]?(\d)\d{2}/', $t, $m)) {
            $anio = (int) $m[1] >= 2 ? 2024 : 2023;
            return new self($original, 'intel', 'ultra', 15, $anio, 'Intel Core Ultra');
        }

        // ── Intel Core 2 / Pentium 4 / Pentium D / Core Solo-Duo (sin "i") ───
        if (preg_match('/CORE ?2|CORE (SOLO|DUO)\b|PENTIUM ?(4|D|M)\b/', $t)) {
            return new self($original, 'intel', 'antiguo', null, 2008, 'Intel (anterior a Core i)');
        }

        // ── Intel Core i3/i5/i7/i9 ───────────────────────────────────────────
        if (preg_match('/\bI([3579])[ -]?(\d{3,5})/', $t, $m)) {
            $digitos = $m[2];
            $gen = match (true) {
                strlen($digitos) === 5                                => (int) substr($digitos, 0, 2),
                strlen($digitos) === 4 && $digitos[0] === '1'         => (int) substr($digitos, 0, 2),
                strlen($digitos) === 4                                => (int) $digitos[0],
                default                                               => 1,     // i5-520M: 1.ª gen
            };
            $gen = max(1, min($gen, 14));

            return new self($original, 'intel', 'core', $gen, self::ANIO_INTEL[$gen] ?? null, "Intel Core i{$m[1]} · {$gen}.ª gen");
        }

        // ── Intel Xeon (servidores): no se juzga por generación ──────────────
        if (str_contains($t, 'XEON')) {
            return new self($original, 'intel', 'servidor', null, null, 'Intel Xeon');
        }

        // ── Intel gama básica ────────────────────────────────────────────────
        if (preg_match('/CELERON|PENTIUM|ATOM|INTEL ?N\d{2,3}\b/', $t)) {
            return new self($original, 'intel', 'gama_basica', null, null, 'Intel gama básica (Celeron/Pentium/Atom)');
        }

        // ── AMD Ryzen ────────────────────────────────────────────────────────
        if (preg_match('/RYZEN ?(AI)?/', $t)) {
            if (preg_match('/RYZEN ?(?:AI ?)?(?:[3579]|THREADRIPPER)?(?: PRO)? ?(\d)\d{3}/', $t, $m)) {
                $serie = (int) $m[1];
                return new self($original, 'amd', 'ryzen', $serie, self::ANIO_RYZEN[$serie] ?? null, "AMD Ryzen · serie {$serie}000");
            }
            if (str_contains($t, 'AI')) {
                return new self($original, 'amd', 'ryzen', 9, 2024, 'AMD Ryzen AI');
            }
        }

        // ── AMD anteriores a Ryzen (no soportan Windows 11) ─────────────────
        if (preg_match('/\bA(4|6|8|9|10|12)[ -]\d{4}|\bFX[ -]?\d|\bE[12][ -]\d{4}|PHENOM|SEMPRON|TURION|ATHLON ?(II|64|X2)/', $t)) {
            return new self($original, 'amd', 'antiguo', null, 2012, 'AMD (anterior a Ryzen)');
        }

        // ── AMD gama básica moderna (Athlon Silver/Gold 3000…) ───────────────
        if (str_contains($t, 'ATHLON')) {
            return new self($original, 'amd', 'gama_basica', null, preg_match('/\b3\d{3}/', $t) ? 2020 : null, 'AMD Athlon (gama básica)');
        }

        if (str_contains($t, 'EPYC') || str_contains($t, 'OPTERON')) {
            return new self($original, 'amd', 'servidor', null, null, 'AMD EPYC/Opteron');
        }

        return new self($original);
    }
}
