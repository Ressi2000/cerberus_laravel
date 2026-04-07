@extends('planillas.layout')

@php
    $receptor = $asignacion->usuario;
    $codigoDoc = 'DC-ST-FO-10';
    $empresaSede = $asignacion->empresa->nombre ?? '—';
    $itemsDevueltos = $asignacion->itemsDevueltos->whereNull('equipo_padre_id');
@endphp

@section('contenido')

<div class="doc-title">Formato de Devolución de Activos Tecnológicos</div>

<div style="display:table; width:100%; margin-bottom:12pt;">
    <div style="display:table-cell; font-size:8pt; color:#374151;">
        <span class="font-bold">Uso:</span> Actividades Inherentes al Cargo
    </div>
    <div style="display:table-cell; text-align:right; font-size:8pt; color:#374151;">
        <span class="font-bold">Fecha de Devolución:</span> {{ $fecha }}
    </div>
</div>

{{-- ── DATOS DEL USUARIO ──────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">Datos del Usuario</div>
    <div class="fields-grid">
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Ficha</div>
                <div class="field-value">{{ $receptor?->ficha ?? '—' }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Usuario</div>
                <div class="field-value">{{ strtoupper($receptor?->name ?? '—') }}</div>
            </div>
        </div>
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Nro Cédula</div>
                <div class="field-value">{{ $receptor?->cedula ?? '—' }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Correo</div>
                <div class="field-value">{{ $receptor?->email ?? '—' }}</div>
            </div>
        </div>
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Empresa</div>
                <div class="field-value">{{ strtoupper($receptor?->empresaNomina?->nombre ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Sede</div>
                <div class="field-value">{{ strtoupper($asignacion->empresa?->nombre ?? '—') }}</div>
            </div>
        </div>
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Departamento</div>
                <div class="field-value">{{ strtoupper($receptor?->departamento?->nombre ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Cargo</div>
                <div class="field-value">{{ strtoupper($receptor?->cargo?->nombre ?? '—') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── EQUIPOS DEVUELTOS ──────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">Equipos Devueltos</div>

    @if ($itemsDevueltos->isEmpty())
        <p style="font-size:8.5pt; color:#6B7280; font-style:italic;">
            No hay equipos devueltos en esta asignación.
        </p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID del Equipo</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Serial</th>
                    <th>Fecha Devolución</th>
                    <th>Recibido por</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemsDevueltos as $item)
                    <tr>
                        <td class="font-bold">{{ $item->equipo?->codigo_interno ?? '—' }}</td>
                        <td>{{ $item->equipo?->nombre_maquina ?? '—' }}</td>
                        <td>{{ strtoupper($item->equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>{{ $item->equipo?->serial ?? '—' }}</td>
                        <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $item->devueltoPor?->name ?? '—' }}</td>
                    </tr>

                    {{-- Periféricos devueltos del mismo equipo --}}
                    @foreach ($item->hijos->where('devuelto', true) as $hijo)
                        <tr style="background: #FFFBEB;">
                            <td style="padding-left:16pt; color:#92400E;">
                                ↳ {{ $hijo->equipo?->codigo_interno ?? '—' }}
                            </td>
                            <td style="color:#92400E;">{{ $hijo->equipo?->nombre_maquina ?? '—' }}</td>
                            <td style="color:#92400E;">{{ strtoupper($hijo->equipo?->categoria?->nombre ?? '—') }}</td>
                            <td style="color:#92400E;">{{ $hijo->equipo?->serial ?? '—' }}</td>
                            <td style="color:#92400E;">{{ $hijo->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                            <td style="color:#92400E;">{{ $hijo->devueltoPor?->name ?? '—' }}</td>
                        </tr>
                    @endforeach

                    {{-- Observaciones de la devolución --}}
                    @if ($item->observaciones_devolucion)
                        <tr style="background: #F9FAFB;">
                            <td colspan="6" style="font-size:7.5pt; color:#6B7280; padding-left:10pt; font-style:italic;">
                                Obs: {{ $item->observaciones_devolucion }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ── EQUIPOS AÚN ACTIVOS ────────────────────────────────────────────────── --}}
@php
    $itemsActivos = $asignacion->items->whereNull('equipo_padre_id')->where('devuelto', false);
@endphp

@if ($itemsActivos->isNotEmpty())
    <div class="section" style="margin-top:8pt;">
        <div class="section-title" style="background:#FEF3C7; border-left-color:#D97706; color:#92400E;">
            Equipos Pendientes de Devolución
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID del Equipo</th>
                    <th>Categoría</th>
                    <th>Serial</th>
                    <th>Fecha Asignación</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemsActivos as $item)
                    <tr class="pendiente">
                        <td>{{ $item->equipo?->codigo_interno ?? '—' }}</td>
                        <td>{{ strtoupper($item->equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>{{ $item->equipo?->serial ?? '—' }}</td>
                        <td>{{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection