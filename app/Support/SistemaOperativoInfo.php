<?php

namespace App\Support;

/**
 * Versión de Windows a partir de cómo está escrita ("W10", "Win 8.1 Pro x64",
 * "Windows 11 Pro", "Win 7"…). Otros sistemas (Linux, macOS) quedan como
 * no-Windows y no se juzgan por soporte.
 */
final class SistemaOperativoInfo
{
    private function __construct(
        public readonly string $texto,
        public readonly ?string $windows = null,   // 'XP' | 'Vista' | '7' | '8' | '8.1' | '10' | '11'
        public readonly bool $otro = false,         // Linux, macOS, Android…
    ) {}

    public function nombre(): ?string
    {
        return $this->windows ? "Windows {$this->windows}" : ($this->otro ? $this->texto : null);
    }

    public static function desde(?string $texto): self
    {
        $original = trim((string) $texto);
        $t = strtoupper($original);

        if ($t === '') {
            return new self($original);
        }

        if (preg_match('/\bXP\b/', $t)) {
            return new self($original, 'XP');
        }
        if (str_contains($t, 'VISTA')) {
            return new self($original, 'Vista');
        }
        if (preg_match('/\b(?:W|WIN|WINDOWS)\s*(\d{1,2}(?:[.,]1)?)\b/', $t, $m)) {
            $v = str_replace(',', '.', $m[1]);
            if (in_array($v, ['7', '8', '8.1', '10', '11'], true)) {
                return new self($original, $v);
            }
        }
        if (preg_match('/LINUX|UBUNTU|DEBIAN|CENTOS|RED ?HAT|MAC ?OS|ANDROID|IOS\b/', $t)) {
            return new self($original, null, true);
        }

        return new self($original);
    }
}
