@extends('planillas.layout')

@php
    $codigoDoc   = 'DC-ST-FO-09';
    $empresaSede = $usuario->ubicacion?->nombre ?? '—';
@endphp

@section('contenido')

<div class="doc-title">Recepción de Activos Tecnológicos por Egreso</div>

<div style="display:table; width:100%; margin-bottom:12pt;">
    <div style="display:table-cell; font-size:8pt; color:#374151;">
        <span class="font-bold">Ubicación:</span> {{ $usuario->ubicacion?->nombre ?? '—' }}
    </div>
    <div style="display:table-cell; text-align:right; font-size:8pt; color:#374151;">
        <span class="font-bold">Fecha:</span> {{ $fecha }}
    </div>
</div>

{{-- ── DATOS DEL USUARIO ──────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">Datos del Usuario</div>
    <div class="fields-grid">
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Ficha</div>
                <div class="field-value">{{ $usuario->ficha ?? '—' }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Usuario</div>
                <div class="field-value">{{ strtoupper($usuario->name) }}</div>
            </div>
        </div>
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Cédula</div>
                <div class="field-value">{{ $usuario->cedula ?? '—' }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Empresa</div>
                <div class="field-value">{{ strtoupper($usuario->empresaNomina?->nombre ?? '—') }}</div>
            </div>
        </div>
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Departamento</div>
                <div class="field-value">{{ strtoupper($usuario->departamento?->nombre ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Cargo</div>
                <div class="field-value">{{ strtoupper($usuario->cargo?->nombre ?? '—') }}</div>
            </div>
        </div>
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Supervisor</div>
                <div class="field-value">{{ strtoupper($usuario->jefe?->name ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Cargo del Supervisor</div>
                <div class="field-value">{{ strtoupper($usuario->jefe?->cargo?->nombre ?? '—') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── TODOS LOS EQUIPOS ASIGNADOS ────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">Equipos Asignados</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre del Equipo</th>
                <th>Categoría</th>
                <th>Serial</th>
                <th>Fecha de Asignación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($asignados as $item)
                <tr>
                    <td class="font-bold">{{ $item->equipo?->codigo_interno ?? '—' }}</td>
                    <td>{{ strtoupper($item->equipo?->categoria?->nombre ?? '—') }}</td>
                    <td>{{ $item->equipo?->serial ?? '—' }}</td>
                    <td>{{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        <span class="badge badge-activa">Activo</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#6B7280; font-style:italic;">
                        Sin equipos asignados
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── EQUIPOS RECIBIDOS ──────────────────────────────────────────────────── --}}
@if ($recibidos->isNotEmpty())
    <div class="section">
        <div class="section-title" style="background:#D1FAE5; border-left-color:#065F46; color:#065F46;">
            Equipos Recibidos (Devueltos)
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre del Equipo</th>
                    <th>Categoría</th>
                    <th>Serial</th>
                    <th>Fecha de Devolución</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recibidos as $item)
                    <tr class="devuelto">
                        <td>{{ $item->equipo?->codigo_interno ?? '—' }}</td>
                        <td>{{ strtoupper($item->equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>{{ $item->equipo?->serial ?? '—' }}</td>
                        <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ── EQUIPOS NO RECEPCIONADOS ────────────────────────────────────────────── --}}
@if ($pendientes->isNotEmpty())
    <div class="section">
        <div class="section-title" style="background:#FEE2E2; border-left-color:#DC2626; color:#DC2626;">
            Equipos No Recepcionados (Pendientes)
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre del Equipo</th>
                    <th>Categoría</th>
                    <th>Serial</th>
                    <th>Fecha de Asignación</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendientes as $item)
                    <tr class="pendiente">
                        <td>{{ $item->equipo?->codigo_interno ?? '—' }}</td>
                        <td>{{ strtoupper($item->equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>{{ $item->equipo?->serial ?? '—' }}</td>
                        <td>{{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection