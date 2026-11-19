<?php

namespace App\Asistente;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Seudónimos: los nombres de las personas NUNCA salen de Cerberus hacia el
 * servicio de IA.
 *
 *  - ocultar(): antes de enviar algo a la IA (la pregunta, las instrucciones
 *    o el resultado de una consulta) cambia cada nombre de usuario por
 *    "Persona-{id}". La IA trabaja con esos códigos.
 *  - revelar(): al mostrar la respuesta (o al ejecutar una consulta que la
 *    IA pidió con un código) vuelve a poner el nombre real.
 *
 * El código depende solo del id del usuario, así que es el mismo en toda la
 * conversación y no hay que guardar ninguna tabla de equivalencias.
 *
 * Límite conocido: solo reconoce el nombre COMPLETO tal como está en
 * Cerberus (sin importar mayúsculas ni tildes). Si alguien escribe solo
 * "Henry", eso sí viaja tal cual.
 */
class Privacidad
{
    private ?array $patron = null;

    public function __construct(private bool $activa = true)
    {
    }

    public static function desdeConfig(): self
    {
        return new self((bool) config('asistente.seudonimos', true));
    }

    public function activa(): bool
    {
        return $this->activa;
    }

    /** Cambia nombres reales por códigos (texto o arreglo, recursivo). */
    public function ocultar(mixed $dato): mixed
    {
        if (! $this->activa) {
            return $dato;
        }
        if (is_array($dato)) {
            return array_map(fn ($v) => $this->ocultar($v), $dato);
        }
        if (! is_string($dato) || $dato === '' || ! $this->patron()) {
            return $dato;
        }

        [$regex, $ids] = $this->patron();

        return preg_replace_callback($regex, function ($m) use ($ids) {
            $id = $ids[$this->clave($m[0])] ?? null;

            return $id ? "Persona-{$id}" : $m[0];
        }, $dato) ?? $dato;
    }

    /** Cambia los códigos "Persona-12" por el nombre real (texto o arreglo). */
    public function revelar(mixed $dato): mixed
    {
        if (! $this->activa) {
            return $dato;
        }
        if (is_array($dato)) {
            return array_map(fn ($v) => $this->revelar($v), $dato);
        }
        if (! is_string($dato) || ! str_contains($dato, 'Persona-')) {
            return $dato;
        }

        return preg_replace_callback('/\bPersona-(\d+)\b/u', function ($m) {
            return $this->nombres()[(int) $m[1]] ?? $m[0];
        }, $dato) ?? $dato;
    }

    /** [id => nombre] de todos los usuarios (se cachea un minuto). */
    private function nombres(): array
    {
        return Cache::remember('asistente:nombres-usuarios', 60, fn () => User::query()
            ->whereNotNull('name')->pluck('name', 'id')
            ->map(fn ($n) => trim((string) $n))
            // Solo nombres de dos o más palabras: un usuario llamado "Soporte"
            // o "Sistemas" cambiaría esa palabra en todos los textos.
            ->filter(fn ($n) => mb_strlen($n) >= 5 && preg_match('/\S\s+\S/u', $n))
            ->all());
    }

    /** Una sola expresión con todos los nombres (los más largos primero). */
    private function patron(): ?array
    {
        if ($this->patron !== null) {
            return $this->patron ?: null;
        }

        $ids = [];
        $partes = [];
        foreach ($this->nombres() as $id => $nombre) {
            $clave = $this->clave($nombre);
            if (isset($ids[$clave])) {
                continue; // dos personas con el mismo nombre: comparten código (la consulta igual pregunta cuál)
            }
            $ids[$clave] = $id;
            $partes[$nombre] = mb_strlen($nombre);
        }

        if (! $partes) {
            $this->patron = [];
            return null;
        }

        arsort($partes);
        $alternativas = array_map(fn ($n) => $this->flexible($n), array_keys($partes));

        return $this->patron = ['/(?<![\pL\pN])(?:' . implode('|', $alternativas) . ')(?![\pL\pN])/iu', $ids];
    }

    /** Nombre como expresión que acepta espacios repetidos y letras con o sin tilde. */
    private function flexible(string $nombre): string
    {
        $tildes = ['a' => '[aáàä]', 'e' => '[eéèë]', 'i' => '[iíìï]', 'o' => '[oóòö]', 'u' => '[uúùü]', 'n' => '[nñ]'];
        $salida = '';
        foreach (preg_split('//u', $this->sinTildes(mb_strtolower($nombre)), -1, PREG_SPLIT_NO_EMPTY) as $c) {
            $salida .= match (true) {
                isset($tildes[$c]) => $tildes[$c],
                ctype_space($c)    => '\s+',
                default            => preg_quote($c, '/'),
            };
        }

        return $salida;
    }

    private function clave(string $texto): string
    {
        return preg_replace('/\s+/u', ' ', $this->sinTildes(mb_strtolower(trim($texto))));
    }

    private function sinTildes(string $texto): string
    {
        return strtr($texto, ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}
