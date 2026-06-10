<?php

namespace App\Exports;

use App\Models\AsignacionItem;
use App\Models\Equipo;
use App\Models\Empresa;
use App\Models\Prestamo;
use App\Models\Traslado;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly User $user,
        private readonly ?int $empresaFiltroId = null,
    ) {}

    public function sheets(): array
    {
        $u  = $this->user;
        $eid = $this->empresaFiltroId;

        $empresa = $eid ? Empresa::find($eid, ['id', 'nombre']) : null;
        $contexto = $empresa ? $empresa->nombre : 'Todas las empresas';

        return [
            new DashboardResumenSheet($u, $eid, $contexto),
            new DashboardPorEstadoSheet($u, $eid),
            new DashboardPorCategoriaSheet($u, $eid),
            new DashboardPrestamosVencidosSheet($u, $eid),
            new DashboardGarantiasSheet($u, $eid),
            new DashboardDetalleEdadesSheet($u, $eid),
        ];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Trait compartido: estilos Cerberus
// ─────────────────────────────────────────────────────────────────────────────

trait CerberusSheetStyle
{
    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B3A5C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    private function subHeaderStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF1B3A5C'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6EAF8']],
        ];
    }

    private function metaStyle(): array
    {
        return [
            'font' => ['italic' => true, 'color' => ['argb' => 'FF7F8C8D'], 'size' => 9],
        ];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Hoja 1 — Resumen General
// ─────────────────────────────────────────────────────────────────────────────

class DashboardResumenSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    use CerberusSheetStyle;

    public function __construct(
        private readonly User $user,
        private readonly ?int $empresaFiltroId,
        private readonly string $contexto,
    ) {}

    public function title(): string { return 'Resumen'; }

    public function array(): array
    {
        $u  = $this->user;
        $eid = $this->empresaFiltroId;

        $totalEquipos = Equipo::visiblePara($u)->where('activo', true)
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))->count();

        $equiposAsignados = AsignacionItem::whereHas('asignacion', function ($q) use ($u, $eid) {
            $q->visiblePara($u)->whereIn('estado', ['Activa', 'Parcial']);
            if ($eid) $q->where('empresa_id', $eid);
        })->where('devuelto', false)->count();

        $prestamosActivos = Prestamo::visiblePara($u)->where('estado', 'Activo')
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))->count();

        $prestamosVencidos = Prestamo::visiblePara($u)->where('estado', 'Vencido')
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))->count();

        $trasladosMes = Traslado::visiblePara($u)
            ->whereMonth('fecha_traslado', now()->month)
            ->whereYear('fecha_traslado', now()->year)
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))->count();

        $usuariosActivos = User::visiblePara($u)->where('estado', 'Activo')
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))->count();

        $fechas = Equipo::visiblePara($u)->where('activo', true)
            ->whereNotNull('fecha_adquisicion')
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))
            ->pluck('fecha_adquisicion');

        $edadPromedio = $fechas->isNotEmpty()
            ? round($fechas->avg(fn($f) => now()->diffInDays(Carbon::parse($f))) / 365, 1)
            : '—';

        return [
            ['CERBERUS — RESUMEN DE INDICADORES DEL DASHBOARD'],
            [],
            ['Empresa / Contexto', $this->contexto],
            ['Fecha de generación', now()->format('d/m/Y H:i')],
            ['Generado por', $this->user->name],
            [],
            ['INDICADOR', 'VALOR'],
            ['Total equipos activos',            $totalEquipos],
            ['Equipos en asignación activa',     $equiposAsignados],
            ['Préstamos activos',                $prestamosActivos],
            ['Préstamos vencidos',               $prestamosVencidos],
            ['Traslados en ' . now()->isoFormat('MMMM YYYY'), $trasladosMes],
            ['Usuarios activos',                 $usuariosActivos],
            ['Edad promedio de equipos (años)',  $edadPromedio],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');

        return [
            1  => ['font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1B3A5C']]],
            7  => $this->headerStyle(),
            3  => $this->metaStyle(),
            4  => $this->metaStyle(),
            5  => $this->metaStyle(),
        ];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Hoja 2 — Equipos por Estado
// ─────────────────────────────────────────────────────────────────────────────

class DashboardPorEstadoSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    use CerberusSheetStyle;

    public function __construct(
        private readonly User $user,
        private readonly ?int $empresaFiltroId,
    ) {}

    public function title(): string { return 'Por Estado'; }

    public function array(): array
    {
        $equipos = Equipo::visiblePara($this->user)
            ->where('activo', true)
            ->when($this->empresaFiltroId, fn($q) => $q->where('empresa_id', $this->empresaFiltroId))
            ->with('estado:id,nombre')
            ->get(['id', 'estado_id']);

        $total = $equipos->count();

        $rows = [['ESTADO', 'CANTIDAD', 'PORCENTAJE']];

        $equipos->groupBy(fn($e) => $e->estado?->nombre ?? 'Sin estado')
            ->map->count()
            ->sortDesc()
            ->each(function ($cant, $estado) use (&$rows, $total) {
                $pct = $total > 0 ? round($cant / $total * 100, 1) . '%' : '0%';
                $rows[] = [$estado, $cant, $pct];
            });

        $rows[] = [];
        $rows[] = ['TOTAL', $total, '100%'];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => $this->headerStyle()];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Hoja 3 — Equipos por Categoría
// ─────────────────────────────────────────────────────────────────────────────

class DashboardPorCategoriaSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    use CerberusSheetStyle;

    public function __construct(
        private readonly User $user,
        private readonly ?int $empresaFiltroId,
    ) {}

    public function title(): string { return 'Por Categoría'; }

    public function array(): array
    {
        $equipos = Equipo::visiblePara($this->user)
            ->where('activo', true)
            ->when($this->empresaFiltroId, fn($q) => $q->where('empresa_id', $this->empresaFiltroId))
            ->with('categoria:id,nombre')
            ->get(['id', 'categoria_id']);

        $total = $equipos->count();

        $rows = [['CATEGORÍA', 'CANTIDAD', 'PORCENTAJE']];

        $equipos->groupBy(fn($e) => $e->categoria?->nombre ?? 'Sin categoría')
            ->map->count()
            ->sortDesc()
            ->each(function ($cant, $cat) use (&$rows, $total) {
                $pct = $total > 0 ? round($cant / $total * 100, 1) . '%' : '0%';
                $rows[] = [$cat, $cant, $pct];
            });

        $rows[] = [];
        $rows[] = ['TOTAL', $total, '100%'];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => $this->headerStyle()];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Hoja 4 — Préstamos Vencidos
// ─────────────────────────────────────────────────────────────────────────────

class DashboardPrestamosVencidosSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    use CerberusSheetStyle;

    public function __construct(
        private readonly User $user,
        private readonly ?int $empresaFiltroId,
    ) {}

    public function title(): string { return 'Préstamos Vencidos'; }

    public function array(): array
    {
        $prestamos = Prestamo::visiblePara($this->user)
            ->where('estado', 'Vencido')
            ->when($this->empresaFiltroId, fn($q) => $q->where('empresa_id', $this->empresaFiltroId))
            ->with(['usuario:id,name', 'areaDepartamento:id,nombre', 'empresa:id,nombre'])
            ->withCount(['items as items_pendientes' => fn($q) => $q->where('devuelto', false)])
            ->orderBy('fecha_devolucion_esperada')
            ->get();

        $rows = [['RECEPTOR', 'EMPRESA', 'FECHA PRÉSTAMO', 'FECHA VENCIMIENTO', 'DÍAS VENCIDO', 'EQUIPOS PENDIENTES']];

        foreach ($prestamos as $p) {
            $rows[] = [
                $p->receptorNombre(),
                $p->empresa?->nombre ?? '—',
                $p->fecha_prestamo?->format('d/m/Y') ?? '—',
                $p->fecha_devolucion_esperada?->format('d/m/Y') ?? '—',
                now()->diffInDays($p->fecha_devolucion_esperada, false) * -1,
                $p->items_pendientes,
            ];
        }

        if ($prestamos->isEmpty()) {
            $rows[] = ['Sin préstamos vencidos', '', '', '', '', ''];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => $this->headerStyle()];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Hoja 5 — Garantías por Vencer (30 días)
// ─────────────────────────────────────────────────────────────────────────────

class DashboardGarantiasSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    use CerberusSheetStyle;

    public function __construct(
        private readonly User $user,
        private readonly ?int $empresaFiltroId,
    ) {}

    public function title(): string { return 'Garantías 30d'; }

    public function array(): array
    {
        $equipos = Equipo::visiblePara($this->user)
            ->where('activo', true)
            ->whereNotNull('fecha_garantia_fin')
            ->whereBetween('fecha_garantia_fin', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->when($this->empresaFiltroId, fn($q) => $q->where('empresa_id', $this->empresaFiltroId))
            ->with(['categoria:id,nombre', 'empresa:id,nombre', 'ubicacion:id,nombre'])
            ->orderBy('fecha_garantia_fin')
            ->get();

        $rows = [['CÓDIGO', 'CATEGORÍA', 'EMPRESA', 'UBICACIÓN', 'VENCE', 'DÍAS RESTANTES']];

        foreach ($equipos as $eq) {
            $rows[] = [
                $eq->codigo_interno,
                $eq->categoria?->nombre ?? '—',
                $eq->empresa?->nombre ?? '—',
                $eq->ubicacion?->nombre ?? '—',
                $eq->fecha_garantia_fin?->format('d/m/Y') ?? '—',
                now()->diffInDays($eq->fecha_garantia_fin, false),
            ];
        }

        if ($equipos->isEmpty()) {
            $rows[] = ['Sin garantías próximas a vencer', '', '', '', '', ''];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => $this->headerStyle()];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Hoja 6 — Detalle de Edades de Equipos
// ─────────────────────────────────────────────────────────────────────────────

class DashboardDetalleEdadesSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    use CerberusSheetStyle;

    public function __construct(
        private readonly User $user,
        private readonly ?int $empresaFiltroId,
    ) {}

    public function title(): string { return 'Detalle Edades'; }

    public function array(): array
    {
        $equipos = Equipo::visiblePara($this->user)
            ->where('activo', true)
            ->whereNotNull('fecha_adquisicion')
            ->when($this->empresaFiltroId, fn($q) => $q->where('empresa_id', $this->empresaFiltroId))
            ->with(['categoria:id,nombre', 'estado:id,nombre', 'empresa:id,nombre', 'ubicacion:id,nombre'])
            ->orderBy('fecha_adquisicion')
            ->get();

        $rows = [['CÓDIGO', 'EMPRESA', 'CATEGORÍA', 'ESTADO', 'UBICACIÓN', 'FECHA ADQUISICIÓN', 'EDAD (días)', 'EDAD (años)']];

        foreach ($equipos as $eq) {
            $dias  = now()->diffInDays(Carbon::parse($eq->fecha_adquisicion));
            $anios = round($dias / 365, 1);

            $rows[] = [
                $eq->codigo_interno,
                $eq->empresa?->nombre   ?? '—',
                $eq->categoria?->nombre ?? '—',
                $eq->estado?->nombre    ?? '—',
                $eq->ubicacion?->nombre ?? '—',
                Carbon::parse($eq->fecha_adquisicion)->format('d/m/Y'),
                $dias,
                $anios,
            ];
        }

        if ($equipos->isEmpty()) {
            $rows[] = ['Sin equipos con fecha de adquisición registrada'];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => $this->headerStyle()];
    }
}
