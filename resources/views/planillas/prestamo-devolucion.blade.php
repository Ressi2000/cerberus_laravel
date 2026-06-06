@extends('planillas.layout')

@php
    $esArea      = $prestamo->tipoReceptor() === 'area';
    $codigoDoc   = 'DC-ST-FO-PRE-DEV';
    $tituloDoc   = 'Constancia de Devolución de Préstamo';
    $empresaSede = $prestamo->empresa->nombre ?? '—';

    $receptor    = $prestamo->usuario;
    $areaEmpresa = $prestamo->areaEmpresa;
    $areaDpto    = $prestamo->areaDepartamento;
    $areaResp    = $prestamo->areaResponsable;

    $devueltos = $prestamo->itemsDevueltos;
    $pendientes = $prestamo->items->where('devuelto', false);
@endphp

@section('contenido')

<div class="doc-title">Constancia de Devolución de Préstamo</div>

{{-- ── RECEPTOR ─────────────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title {{ $esArea ? 'area' : '' }}">Datos del Receptor</div>
    <div class="fields-grid">
        <div style="padding-bottom:5pt;">
            <span class="receptor-badge {{ $esArea ? 'area' : 'usuario' }}">
                {{ $esArea ? 'Préstamo a área común' : 'Préstamo personal' }}
            </span>
        </div>
        <table class="fields-table">
            @if (! $esArea)
            <tr>
                <td><div class="field-label">Nombre completo</div><div class="field-value">{{ strtoupper($receptor?->name ?? '—') }}</div></td>
                <td><div class="field-label">Ficha</div><div class="field-value">{{ $receptor?->ficha ?? '—' }}</div></td>
                <td><div class="field-label">Empresa (nómina)</div><div class="field-value">{{ strtoupper($receptor?->empresaNomina?->nombre ?? '—') }}</div></td>
            </tr>
            @else
            <tr>
                <td><div class="field-label">Área / Departamento</div><div class="field-value">{{ strtoupper($areaDpto?->nombre ?? '—') }}</div></td>
                <td><div class="field-label">Empresa</div><div class="field-value">{{ strtoupper($areaEmpresa?->nombre ?? '—') }}</div></td>
                <td><div class="field-label">Responsable</div><div class="field-value">{{ strtoupper($areaResp?->name ?? '—') }}</div></td>
            </tr>
            @endif
            <tr>
                <td><div class="field-label">Fecha de préstamo</div><div class="field-value">{{ $prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}</div></td>
                <td><div class="field-label">Fecha devolución real</div><div class="field-value">{{ $prestamo->fecha_devolucion_real?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div></td>
                <td><div class="field-label">Analista</div><div class="field-value">{{ strtoupper($prestamo->analista?->name ?? '—') }}</div></td>
            </tr>
        </table>
    </div>
</div>

{{-- ── EQUIPOS DEVUELTOS ────────────────────────────────────────────────── --}}
@if ($devueltos->isNotEmpty())
<div class="equipos-section">
    <table class="data-table">
        <thead>
            <tr class="thead-banner"><td colspan="6">Equipos Devueltos ({{ $devueltos->count() }})</td></tr>
            <tr class="thead-cols">
                <th style="width:12%">Código</th>
                <th style="width:16%">Hostname</th>
                <th style="width:13%">Categoría</th>
                <th style="width:13%">Serial</th>
                <th style="width:14%">Fecha devolución</th>
                <th style="width:32%">Observaciones</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($devueltos as $index => $item)
            @php
                $eq       = $item->equipo;
                $rowClass = $index % 2 === 0 ? 'tr-impar' : 'tr-par';
                $esPerif  = $item->equipo_padre_id !== null;
            @endphp
            <tr class="{{ $rowClass }} {{ $esPerif ? 'tr-periferico' : 'equipo-group' }}">
                <td class="{{ $esPerif ? '' : 'cod-interno' }}">
                    @if ($esPerif)<span class="periferico-prefix">↳</span>@endif
                    {{ $eq?->codigo_interno ?? '—' }}
                </td>
                <td>{{ $eq?->nombre_maquina ?? '—' }}</td>
                <td>{{ strtoupper($eq?->categoria?->nombre ?? '—') }}</td>
                <td>{{ $eq?->serial ?? '—' }}</td>
                <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                <td style="font-size:8pt;">{{ $item->observaciones_devolucion ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── EQUIPOS PENDIENTES ───────────────────────────────────────────────── --}}
@if ($pendientes->isNotEmpty())
<div class="equipos-section" style="margin-top:10pt;">
    <table class="data-table">
        <thead>
            <tr class="thead-banner" style="background:#FEF3C7; color:#92400E;">
                <td colspan="4">Equipos Pendientes de Devolución ({{ $pendientes->count() }})</td>
            </tr>
            <tr class="thead-cols">
                <th style="width:15%">Código</th>
                <th style="width:20%">Hostname</th>
                <th style="width:15%">Categoría</th>
                <th style="width:15%">Serial</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($pendientes as $index => $item)
            @php $eq = $item->equipo; @endphp
            <tr class="{{ $index % 2 === 0 ? 'tr-impar' : 'tr-par' }}">
                <td>{{ $eq?->codigo_interno ?? '—' }}</td>
                <td>{{ $eq?->nombre_maquina ?? '—' }}</td>
                <td>{{ strtoupper($eq?->categoria?->nombre ?? '—') }}</td>
                <td>{{ $eq?->serial ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection

@section('firmas')
    <td class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($prestamo->analista?->name ?? 'Analista') }}</div>
        <div class="firma-cargo">Técnico / Analista</div>
    </td>
    <td class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($esArea ? ($areaResp?->name ?? 'Responsable') : ($receptor?->name ?? 'Receptor')) }}</div>
        <div class="firma-cargo">{{ $esArea ? 'Responsable del área' : 'Trabajador receptor' }}</div>
    </td>
    <td class="firma-cell"></td>
@endsection
