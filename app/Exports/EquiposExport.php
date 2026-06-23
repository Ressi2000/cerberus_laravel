<?php

namespace App\Exports;

use App\Models\AtributoEquipo;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EquiposExport implements FromIterator, ShouldAutoSize
{
    protected $query;

    /**
     * Lista plana de todos los nombres de atributos simples (no-grupo) únicos
     * encontrados en el query (para construir encabezados dinámicos).
     */
    protected array $nombresAtributos = [];

    /**
     * Atributos tipo 'group' encontrados, indexados por id.
     */
    protected array $gruposPorId = [];

    /**
     * Cantidad máxima de instancias por atributo de grupo, entre todos
     * los equipos a exportar. Define cuántas columnas "#N - subcampo" generar.
     */
    protected array $maxInstanciasPorAtributo = [];

    /**
     * Columnas de grupo aplanadas, en el orden en que se exportan:
     * ['atributo_id' => int, 'instancia' => int (1-based), 'sub_id' => string, 'label' => string]
     */
    protected array $columnasGrupo = [];

    public function __construct($query)
    {
        // Clonamos el query para precargar sin afectar el cursor principal
        $this->precargarAtributos(clone $query, clone $query);
        $this->query = $query;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pre-carga de atributos EAV (simples y de grupo) del conjunto a exportar
    // ─────────────────────────────────────────────────────────────────────────
    protected function precargarAtributos($queryParaCategorias, $queryParaIds): void
    {
        // Obtener todos los categoria_id únicos del resultado
        $categoriaIds = $queryParaCategorias
            ->select('categoria_id')
            ->distinct()
            ->pluck('categoria_id')
            ->toArray();

        // Por cada categoría, obtener sus atributos ordenados y separarlos
        // entre simples (van en columnas fijas) y de grupo (van en columnas
        // expandidas por instancia + sub-campo).
        foreach ($categoriaIds as $catId) {
            $atributos = AtributoEquipo::where('categoria_id', $catId)
                ->orderBy('orden')
                ->get();

            foreach ($atributos as $attr) {
                if ($attr->tipo === AtributoEquipo::TIPO_GROUP) {
                    $this->gruposPorId[$attr->id] = $attr;
                    continue;
                }

                if (! in_array($attr->nombre, $this->nombresAtributos)) {
                    $this->nombresAtributos[] = $attr->nombre;
                }
            }
        }

        if (empty($this->gruposPorId)) {
            return;
        }

        // Cantidad máxima de instancias por atributo de grupo, considerando
        // sólo los equipos que efectivamente se van a exportar.
        $equipoIds = $queryParaIds->pluck('id')->toArray();

        if (empty($equipoIds)) {
            return;
        }

        $conteos = DB::table('equipo_atributo_grupo_instancias')
            ->select('atributo_id', 'equipo_id')
            ->selectRaw('count(*) as cnt')
            ->whereIn('equipo_id', $equipoIds)
            ->whereIn('atributo_id', array_keys($this->gruposPorId))
            ->groupBy('atributo_id', 'equipo_id')
            ->get()
            ->groupBy('atributo_id');

        foreach ($conteos as $atributoId => $filas) {
            $this->maxInstanciasPorAtributo[$atributoId] = (int) $filas->max('cnt');
        }

        // Construir columnas de grupo aplanadas: una por cada (instancia, sub-campo).
        // Si ningún equipo exportado tiene instancias de un atributo de grupo dado,
        // no se generan columnas para él (evita columnas vacías "#1" sin sentido).
        foreach ($this->gruposPorId as $atributoId => $atributo) {
            $max = $this->maxInstanciasPorAtributo[$atributoId] ?? 0;
            if ($max < 1) continue;

            $subCampos = $atributo->sub_campos ?? [];

            for ($i = 1; $i <= $max; $i++) {
                foreach ($subCampos as $sub) {
                    $this->columnasGrupo[] = [
                        'atributo_id' => $atributoId,
                        'instancia'   => $i,
                        'sub_id'      => $sub['id'],
                        'label'       => "{$atributo->nombre} #{$i} - {$sub['nombre']}",
                    ];
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Encabezados + filas
    // ─────────────────────────────────────────────────────────────────────────
    public function iterator(): \Iterator
    {
        // ── FILA 1: Encabezados fijos + EAV simples + grupo ──────────────────
        $encabezadosFijos = [
            'Código interno',
            'Categoría',
            'Estado',
            'Activo',
            'Empresa',
            'Ubicación',
            'Fecha adquisición',
            'Fecha garantía fin',
            'Observaciones',
            'Creado por',
            'Fecha creación',
        ];

        yield array_merge(
            $encabezadosFijos,
            $this->nombresAtributos,
            array_column($this->columnasGrupo, 'label')
        );

        // ── FILAS DE DATOS ───────────────────────────────────────────────────
        foreach ($this->query->cursor() as $equipo) {

            // Columnas fijas
            $fila = [
                $equipo->codigo_interno,
                $equipo->categoria?->nombre          ?? '—',
                $equipo->estado?->nombre             ?? '—',
                $equipo->activo ? 'Activo' : 'Baja lógica',
                $equipo->empresa?->nombre            ?? '—',
                $equipo->ubicacion?->nombre          ?? '—',
                $equipo->fecha_adquisicion
                    ? \Carbon\Carbon::parse($equipo->fecha_adquisicion)->format('d/m/Y')
                    : '—',
                $equipo->fecha_garantia_fin
                    ? \Carbon\Carbon::parse($equipo->fecha_garantia_fin)->format('d/m/Y')
                    : '—',
                $equipo->observaciones               ?? '—',
                $equipo->creadoPor?->name            ?? '—',
                $equipo->created_at?->format('d/m/Y H:i') ?? '—',
            ];

            // Columnas EAV simples dinámicas
            $valoresActuales = $equipo->atributosActuales
                ->mapWithKeys(fn($v) => [$v->atributo?->nombre => $v->valor])
                ->toArray();

            foreach ($this->nombresAtributos as $nombreAttr) {
                $valor = $valoresActuales[$nombreAttr] ?? '—';

                // Formatear booleanos para que sean legibles
                if ($valor === '1' || $valor === 1) {
                    $valor = 'Sí';
                } elseif ($valor === '0' || $valor === 0) {
                    $valor = 'No';
                }

                $fila[] = $valor;
            }

            // Columnas de grupo: instancias del equipo agrupadas por atributo,
            // ya ordenadas por 'orden' gracias a la relación grupoInstancias().
            $instanciasPorAtributo = $equipo->grupoInstancias->groupBy('atributo_id');

            foreach ($this->columnasGrupo as $col) {
                // ->values() reindexa: tras groupBy() las claves originales
                // de la colección no quedan en 0,1,2..., así que get($i) fallaría.
                $instancias = $instanciasPorAtributo->get($col['atributo_id'])?->values();
                $instancia  = $instancias?->get($col['instancia'] - 1);
                $valor      = $instancia?->valores[$col['sub_id']] ?? null;

                if ($valor === true || $valor === '1' || $valor === 1) {
                    $valor = 'Sí';
                } elseif ($valor === false || $valor === '0' || $valor === 0) {
                    $valor = 'No';
                } elseif ($valor === null || $valor === '') {
                    $valor = '—';
                }

                $fila[] = $valor;
            }

            yield $fila;
        }
    }
}
