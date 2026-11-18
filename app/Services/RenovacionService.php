<?php

namespace App\Services;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\CriterioRenovacion;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Support\ProcesadorInfo;
use App\Support\SistemaOperativoInfo;
use Illuminate\Support\Collection;

/**
 * RenovacionService
 * ─────────────────────────────────────────────────────────────────────────────
 * Clasifica cada equipo según los criterios de su categoría
 * (CriterioRenovacion), con reglas fijas y explicables — no con IA — para
 * que el mismo equipo dé siempre el mismo resultado y cada clasificación
 * diga por qué:
 *
 *   obsoleto       conviene reemplazarlo
 *   repotenciable  sirve, con una mejora (RAM, SSD, Windows 11…)
 *   revisar        todavía sirve, pero hay que vigilarlo
 *   sano           nada que hacer
 *   sin_datos      su ficha no tiene con qué evaluarlo
 *
 * Los datos salen de la ficha del equipo: procesador, RAM, discos y sistema
 * operativo (de qué característica sale cada uno se detecta solo o se
 * configura), fecha de adquisición (o edad estimada por el procesador) y
 * reparaciones de los últimos 12 meses.
 */
class RenovacionService
{
    const CLASIFICACIONES = [
        'obsoleto'      => 'Obsoleto',
        'repotenciable' => 'Repotenciable',
        'revisar'       => 'Por revisar',
        'sano'          => 'Sano',
        'sin_datos'     => 'Sin datos',
    ];

    // ── Qué característica de la ficha da cada dato ──────────────────────────

    /**
     * @return array{procesador: ?AtributoEquipo, ram: ?AtributoEquipo, disco: ?AtributoEquipo, so: ?AtributoEquipo}
     */
    public function atributosDe(CategoriaEquipo $categoria, CriterioRenovacion $criterio): array
    {
        $atributos = AtributoEquipo::with('familia')->where('categoria_id', $categoria->id)->orderBy('orden')->get();

        $elegido = fn (?int $id) => $id ? $atributos->firstWhere('id', $id) : null;
        $porNombre = fn (string $patron) => $atributos->first(function (AtributoEquipo $a) use ($patron) {
            return preg_match($patron, $this->normalizar($a->nombre)) || ($a->familia && preg_match($patron, $this->normalizar($a->familia->nombre)));
        });

        return [
            'procesador' => $elegido($criterio->atributo_procesador_id) ?? $porNombre('/\b(procesador|cpu)\b/'),
            'ram'        => $elegido($criterio->atributo_ram_id) ?? $porNombre('/\b(ram|memoria)\b/'),
            'disco'      => $elegido($criterio->atributo_disco_id) ?? $porNombre('/\b(disco|discos|almacenamiento|ssd|hdd)\b/'),
            'so'         => $elegido($criterio->atributo_so_id) ?? $porNombre('/\b(sistema operativo|so|os)\b/'),
        ];
    }

    // ── Evaluar ─────────────────────────────────────────────────────────────

    /**
     * Evalúa varios equipos de una vez (carga lo necesario en pocas consultas).
     *
     * @param  Collection<int, Equipo> $equipos
     * @return Collection<int, array{equipo: Equipo, resultado: array}>
     */
    public function evaluarEquipos(Collection $equipos): Collection
    {
        $equipos = new \Illuminate\Database\Eloquent\Collection($equipos->all());
        $equipos->loadMissing(['categoria', 'empresa', 'atributosActuales', 'grupoInstancias']);

        $reparaciones = Mantenimiento::correctivos()
            ->whereIn('equipo_id', $equipos->pluck('id'))
            ->where('fecha_inicio', '>=', now()->subYear()->toDateString())
            ->selectRaw('equipo_id, count(*) as total')
            ->groupBy('equipo_id')
            ->pluck('total', 'equipo_id');

        $porCategoria = [];

        return $equipos->map(function (Equipo $equipo) use (&$porCategoria, $reparaciones) {
            $catId = $equipo->categoria_id;
            if (! isset($porCategoria[$catId])) {
                $criterio = CriterioRenovacion::paraCategoria($equipo->categoria);
                $porCategoria[$catId] = [$criterio, $this->atributosDe($equipo->categoria, $criterio)];
            }
            [$criterio, $atributos] = $porCategoria[$catId];

            return [
                'equipo'    => $equipo,
                'criterio'  => $criterio,
                'resultado' => $this->evaluar($equipo, $criterio, $atributos, (int) ($reparaciones[$equipo->id] ?? 0)),
            ];
        });
    }

    public function evaluar(Equipo $equipo, CriterioRenovacion $c, array $atributos, int $reparaciones12m): array
    {
        $d = $this->leer($equipo, $atributos);
        $cpu = ProcesadorInfo::desde($d['procesador_texto']);
        $so  = SistemaOperativoInfo::desde($d['so_texto']);

        $obsoleto = $mejora = $revisar = $faltan = $info = [];

        // ── Procesador ──────────────────────────────────────────────────────
        $cpuW11 = match ($cpu->linea) {
            'core'    => $cpu->generacion >= $c->intel_generacion_minima,
            'ultra'   => true,
            'ryzen'   => $cpu->generacion >= $c->ryzen_serie_minima,
            'antiguo' => false,
            default   => null,   // gama básica, servidor, Apple o no reconocido
        };

        if ($atributos['procesador']) {
            if ($d['procesador_texto'] === null) {
                $faltan[] = 'Falta el procesador en la ficha.';
            } elseif (! $cpu->reconocido()) {
                $faltan[] = "No se reconoce el procesador «{$cpu->texto}»: revisa cómo está escrito en la ficha.";
            }
        }

        if ($cpuW11 === false) {
            $obsoleto[] = "Procesador {$cpu->nombre}: por debajo del mínimo (Intel {$c->intel_generacion_minima}.ª gen / Ryzen {$c->ryzen_serie_minima}000), no cumple los requisitos de Windows 11.";
        }
        if ($cpu->linea === 'gama_basica') {
            match ($c->gama_basica) {
                'obsoleto' => $obsoleto[] = "Procesador de gama básica ({$cpu->nombre}).",
                'revisar'  => $revisar[]  = "Procesador de gama básica ({$cpu->nombre}): rinde poco para trabajo de oficina exigente.",
                default    => null,
            };
        }

        // ── Edad ────────────────────────────────────────────────────────────
        $edad = null;
        $estimada = false;
        if ($equipo->fecha_adquisicion) {
            $edad = (int) floor($equipo->fecha_adquisicion->diffInYears(now()));
        } elseif ($cpu->anio) {
            $edad = max(0, (int) now()->year - $cpu->anio);
            $estimada = true;
        } else {
            $faltan[] = 'Falta la fecha de adquisición.';
        }

        if ($edad !== null) {
            $anios = $edad . ' año' . ($edad === 1 ? '' : 's');
            if ($estimada) {
                // Solo es una estimación (el equipo pudo comprarse después de que
                // salió su procesador): nunca lo vuelve obsoleto por sí sola.
                if ($edad >= $c->edad_revisar) {
                    $revisar[] = "Procesador de hace {$anios}: registra la fecha de adquisición para saber su edad real.";
                }
            } elseif ($edad >= $c->edad_obsoleto) {
                $obsoleto[] = "{$anios} de uso: llegó al límite de {$c->edad_obsoleto} años.";
            } elseif ($edad >= $c->edad_revisar) {
                $revisar[] = "{$anios} de uso.";
            }
        }

        // ── RAM ─────────────────────────────────────────────────────────────
        if ($atributos['ram']) {
            if ($d['ram_gb'] === null) {
                $faltan[] = 'Falta la RAM en la ficha.';
            } elseif ($d['ram_gb'] < $c->ram_minima_gb) {
                $mejora[] = "Subir la RAM de {$d['ram_gb']} GB a {$c->ram_minima_gb} GB o más.";
            }

            $viejos = array_intersect(array_map('strtoupper', $d['ram_tipos']), array_map('strtoupper', $c->ram_tipos_obsoletos ?? []));
            if ($viejos) {
                $obsoleto[] = 'Memoria ' . implode('/', $viejos) . ': ya no se consigue para repotenciarlo.';
            }
        }

        // ── Discos ──────────────────────────────────────────────────────────
        if ($atributos['disco']) {
            if (! $d['discos']) {
                $faltan[] = 'Falta el disco en la ficha.';
            } else {
                if ($c->requiere_ssd && $d['tiene_ssd'] === false) {
                    $mejora[] = 'Cambiar el disco HDD por un SSD (la mejora que más se nota).';
                }
                if ($d['disco_gb'] !== null && $d['disco_gb'] < $c->disco_minimo_gb) {
                    $mejora[] = "Disco de {$d['disco_gb']} GB: poner uno de {$c->disco_minimo_gb} GB o más.";
                }
            }
        }

        // ── Sistema operativo ───────────────────────────────────────────────
        if ($so->windows && in_array($so->windows, $c->so_sin_soporte ?? [], true)) {
            $nombreSo = $so->nombre();
            match (true) {
                $cpuW11 === true  => $mejora[]   = "Tiene {$nombreSo}, sin soporte de seguridad: actualizar a Windows 11.",
                $cpuW11 === false => $obsoleto[] = "Tiene {$nombreSo}, sin soporte de seguridad, y su procesador no cumple los requisitos de Windows 11.",
                default           => $revisar[]  = "Tiene {$nombreSo}, sin soporte de seguridad: verificar si puede pasar a Windows 11.",
            };
        } elseif ($atributos['so'] && $d['so_texto'] === null) {
            $faltan[] = 'Falta el sistema operativo en la ficha.';
        }

        // ── Reparaciones ────────────────────────────────────────────────────
        if ($reparaciones12m >= $c->reparaciones_max_12m) {
            $obsoleto[] = "{$reparaciones12m} reparaciones en los últimos 12 meses: cuesta más mantenerlo que reemplazarlo.";
        } elseif ($reparaciones12m > 0) {
            $info[] = "{$reparaciones12m} reparación(es) en los últimos 12 meses.";
        }

        if ($equipo->fecha_garantia_fin && $equipo->fecha_garantia_fin->isPast()) {
            $info[] = 'Garantía vencida desde ' . $equipo->fecha_garantia_fin->format('d/m/Y') . '.';
        }

        $hayDatos = $cpu->reconocido() || $d['ram_gb'] !== null || $d['discos'] || $edad !== null || $so->windows;

        $clasificacion = match (true) {
            (bool) $obsoleto => 'obsoleto',
            (bool) $mejora   => 'repotenciable',
            (bool) $revisar  => 'revisar',
            ! $hayDatos      => 'sin_datos',
            default          => 'sano',
        };

        return [
            'clasificacion' => $clasificacion,
            'obsoleto'      => $obsoleto,
            'mejoras'       => $mejora,
            'revisar'       => $revisar,
            'faltan'        => $faltan,
            'info'          => $info,
            'datos'         => [
                'procesador'      => $cpu->nombre ?? $d['procesador_texto'],
                'procesador_texto'=> $d['procesador_texto'],
                'windows11'       => $cpuW11,
                'ram'             => $d['ram_gb'] !== null ? trim($d['ram_gb'] . ' GB ' . implode('/', array_unique($d['ram_tipos']))) : null,
                'discos'          => $d['discos'] ? implode(' + ', $d['discos']) : null,
                'so'              => $so->nombre() ?? $d['so_texto'],
                'edad'            => $edad,
                'edad_estimada'   => $estimada,
                'reparaciones'    => $reparaciones12m,
            ],
        ];
    }

    // ── Leer la ficha ───────────────────────────────────────────────────────

    private function leer(Equipo $equipo, array $atributos): array
    {
        $simple = fn (?AtributoEquipo $a) => $a
            ? (trim((string) $equipo->atributosActuales->firstWhere('atributo_id', $a->id)?->valor) ?: null)
            : null;
        $grupo = fn (?AtributoEquipo $a) => $a
            ? $equipo->grupoInstancias->where('atributo_id', $a->id)->values()
            : collect();

        // RAM: suma de los módulos (o el valor si es un campo simple).
        $ramGb = null;
        $ramTipos = [];
        if ($a = $atributos['ram']) {
            if ($a->esGrupo()) {
                foreach ($grupo($a) as $inst) {
                    [$gb, $tipo] = $this->capacidadYTipo($a, $inst->valores ?? []);
                    if ($gb !== null) {
                        $ramGb = ($ramGb ?? 0) + $gb;
                    }
                    if ($tipo) {
                        $ramTipos[] = strtoupper(preg_replace('/\s+/', '', $tipo));
                    }
                }
            } else {
                $ramGb = $this->numeroGb($simple($a), $a->nombre);
            }
        }

        // Discos: cada uno con su capacidad y si es SSD o HDD.
        $discos = [];
        $discoGb = null;
        $tieneSsd = null;
        if ($a = $atributos['disco']) {
            $lista = $a->esGrupo()
                ? $grupo($a)->map(fn ($i) => $this->capacidadYTipo($a, $i->valores ?? []))->all()
                : ($simple($a) !== null ? [[$this->numeroGb($simple($a), $a->nombre), $simple($a)]] : []);

            foreach ($lista as [$gb, $tipo]) {
                $clase = $this->claseDisco((string) $tipo);
                $discos[] = trim(($gb !== null ? $this->formatoGb($gb) : '') . ' ' . ($clase ? strtoupper($clase) : (string) $tipo)) ?: 'Disco';
                if ($gb !== null) {
                    $discoGb = ($discoGb ?? 0) + $gb;
                }
                if ($clase === 'ssd') {
                    $tieneSsd = true;
                } elseif ($clase === 'hdd' && $tieneSsd === null) {
                    $tieneSsd = false;
                }
            }
        }

        return [
            'procesador_texto' => $simple($atributos['procesador']),
            'so_texto'         => $simple($atributos['so']),
            'ram_gb'           => $ramGb,
            'ram_tipos'        => $ramTipos,
            'discos'           => $discos,
            'disco_gb'         => $discoGb,
            'tiene_ssd'        => $tieneSsd,
        ];
    }

    /** [GB, tipo] de una entrada de un grupo (RAM o disco), según los nombres de sus campos. */
    private function capacidadYTipo(AtributoEquipo $a, array $valores): array
    {
        $gb = $tipo = null;
        foreach ($a->sub_campos ?? [] as $campo) {
            $valor = $valores[$campo['id']] ?? null;
            if ($valor === null || $valor === '') {
                continue;
            }
            $nombre = $this->normalizar($campo['nombre']);
            if ($gb === null && preg_match('/capacidad|cantidad|tamano|size|\bgb\b|\btb\b/', $nombre)) {
                $gb = $this->numeroGb((string) $valor, $campo['nombre']);
            } elseif ($tipo === null && preg_match('/tipo|tecnologia|interfaz/', $nombre)) {
                $tipo = (string) $valor;
            }
        }

        return [$gb, $tipo];
    }

    private function numeroGb(?string $valor, string $nombreCampo = ''): ?int
    {
        if ($valor === null || ! preg_match('/(\d+(?:[.,]\d+)?)/', $valor, $m)) {
            return null;
        }
        $n = (float) str_replace(',', '.', $m[1]);
        $enTb = stripos($valor, 'tb') !== false || stripos($nombreCampo, 'tb') !== false;

        return (int) round($enTb ? $n * 1000 : $n);
    }

    private function claseDisco(string $tipo): ?string
    {
        $t = strtoupper($tipo);

        return match (true) {
            (bool) preg_match('/SSD|NVME|M\.?2|MSATA|EMMC/', $t) => 'ssd',
            (bool) preg_match('/HDD|MECANIC|7200|5400|\bSATA\b/', $t) => 'hdd',
            default => null,
        };
    }

    private function formatoGb(int $gb): string
    {
        return $gb >= 1000 && $gb % 1000 === 0 ? ($gb / 1000) . ' TB' : "{$gb} GB";
    }

    private function normalizar(string $texto): string
    {
        $t = mb_strtolower($texto);

        return strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    }
}
