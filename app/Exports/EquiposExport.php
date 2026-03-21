<?php

namespace App\Exports;

use App\Models\AtributoEquipo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

class EquiposExport implements FromIterator, ShouldAutoSize
{
    protected $query;
 
    /**
     * Cache de atributos por categoria_id.
     * Estructura: [ categoria_id => Collection<AtributoEquipo> ]
     */
    protected array $atributosPorCategoria = [];
 
    /**
     * Lista plana de todos los nombres de atributos únicos
     * encontrados en el query (para construir encabezados dinámicos).
     */
    protected array $nombresAtributos = [];
 
    public function __construct($query)
    {
        // Clonamos el query para precargar sin afectar el cursor principal
        $this->precargarAtributos(clone $query);
        $this->query = $query;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Pre-carga de atributos EAV únicos del conjunto de equipos a exportar
    // ─────────────────────────────────────────────────────────────────────────
    protected function precargarAtributos($queryClone): void
    {
        // Obtener todos los categoria_id únicos del resultado
        $categoriaIds = $queryClone
            ->select('categoria_id')
            ->distinct()
            ->pluck('categoria_id')
            ->toArray();
 
        // Por cada categoría, obtener sus atributos ordenados
        foreach ($categoriaIds as $catId) {
            $atributos = AtributoEquipo::where('categoria_id', $catId)
                ->orderBy('orden')
                ->get();
 
            $this->atributosPorCategoria[$catId] = $atributos;
 
            // Acumular nombres únicos de atributos para los encabezados
            foreach ($atributos as $attr) {
                if (! in_array($attr->nombre, $this->nombresAtributos)) {
                    $this->nombresAtributos[] = $attr->nombre;
                }
            }
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Encabezados + filas
    // ─────────────────────────────────────────────────────────────────────────
    public function iterator(): \Iterator
    {
        // ── FILA 1: Encabezados fijos + dinámicos ────────────────────────────
        $encabezadosFijos = [
            'Código interno',
            'Categoría',
            'Serial',
            'Nombre máquina',
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
 
        // Los atributos EAV van al final, marcados con su nombre
        yield array_merge($encabezadosFijos, $this->nombresAtributos);
 
        // ── FILAS DE DATOS ───────────────────────────────────────────────────
        foreach ($this->query->cursor() as $equipo) {
 
            // Columnas fijas
            $fila = [
                $equipo->codigo_interno,
                $equipo->categoria?->nombre          ?? '—',
                $equipo->serial                      ?? '—',
                $equipo->nombre_maquina              ?? '—',
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
 
            // Columnas EAV dinámicas
            // Obtener valores actuales de este equipo como mapa atributo_nombre → valor
            $valoresActuales = $equipo->atributosActuales
                ->mapWithKeys(fn($v) => [$v->atributo?->nombre => $v->valor])
                ->toArray();
 
            // Por cada nombre de atributo conocido, buscar el valor o '—'
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
 
            yield $fila;
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Estilos: encabezado azul Cerberus
    // ─────────────────────────────────────────────────────────────────────────
    // public function styles(Worksheet $sheet): array
    // {
    //     return [
    //         1 => [
    //             'font' => [
    //                 'bold'  => true,
    //                 'color' => ['argb' => 'FFFFFFFF'],
    //             ],
    //             'fill' => [
    //                 'fillType'   => Fill::FILL_SOLID,
    //                 'startColor' => ['argb' => 'FF1E40AF'],
    //             ],
    //         ],
    //     ];
    // }
}
