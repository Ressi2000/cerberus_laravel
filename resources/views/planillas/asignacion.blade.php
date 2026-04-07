@extends('planillas.layout')

@php
    $receptor = $asignacion->usuario;
    $codigoDoc = 'DC-ST-FO-08';
    $empresaSede = $asignacion->empresa->nombre ?? '—';
@endphp

@section('contenido')

<div class="doc-title">Formato de Asignación de Activos Tecnológicos</div>

{{-- Meta del documento --}}
<div style="display:table; width:100%; margin-bottom:12pt;">
    <div style="display:table-cell; font-size:8pt; color:#374151;">
        <span class="font-bold">Uso:</span> Actividades Inherentes al Cargo
    </div>
    <div style="display:table-cell; text-align:right; font-size:8pt; color:#374151;">
        <span class="font-bold">Fecha de Entrega:</span> {{ $fecha }}
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
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Supervisor</div>
                <div class="field-value">{{ strtoupper($receptor?->jefe?->name ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Cargo del Supervisor</div>
                <div class="field-value">{{ strtoupper($receptor?->jefe?->cargo?->nombre ?? '—') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── EQUIPOS (uno por uno) ──────────────────────────────────────────────── --}}
@foreach ($asignacion->items as $item)
    @php
        $equipo = $item->equipo;
        $atributos = $equipo?->atributosActuales ?? collect();
        $garantiaDesde = $equipo?->fecha_adquisicion
            ? \Carbon\Carbon::parse($equipo->fecha_adquisicion)->format('Y-m-d')
            : null;
        $garantiaHasta = $equipo?->fecha_garantia_fin
            ? \Carbon\Carbon::parse($equipo->fecha_garantia_fin)->format('Y-m-d')
            : null;
    @endphp

    <div class="section">
        <div class="section-title">
            Datos del Equipo
            @if ($asignacion->items->count() > 1)
                — {{ $loop->iteration }} de {{ $asignacion->items->count() }}
            @endif
        </div>

        <div class="fields-grid">
            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">ID del Equipo</div>
                    <div class="field-value">{{ $equipo?->codigo_interno ?? '—' }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Nombre del Equipo</div>
                    <div class="field-value">{{ $equipo?->nombre_maquina ?? '—' }}</div>
                </div>
            </div>
            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Categoría</div>
                    <div class="field-value">{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Serial</div>
                    <div class="field-value">{{ $equipo?->serial ?? '—' }}</div>
                </div>
            </div>

            {{-- Atributos EAV visibles --}}
            @if ($atributos->isNotEmpty())
                @foreach ($atributos->chunk(2) as $chunk)
                    <div class="fields-row">
                        @foreach ($chunk as $av)
                            <div class="field-cell">
                                <div class="field-label">{{ $av->atributo?->nombre ?? '—' }}</div>
                                <div class="field-value">{{ $av->valor ?? '—' }}</div>
                            </div>
                        @endforeach
                        @if ($chunk->count() === 1)
                            <div class="field-cell"></div>
                        @endif
                    </div>
                @endforeach
            @endif

            @if ($garantiaDesde || $garantiaHasta)
                <div class="fields-row">
                    <div class="field-cell full" style="width:100%; display:table-cell;">
                        <div class="field-label">Garantía</div>
                        <div class="field-value">
                            @if ($garantiaDesde)Desde: {{ $garantiaDesde }}@endif
                            @if ($garantiaDesde && $garantiaHasta) &nbsp;·&nbsp; @endif
                            @if ($garantiaHasta)Hasta: {{ $garantiaHasta }}@endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Periféricos vinculados --}}
        @if ($item->hijos->isNotEmpty())
            <div class="mb-8" style="margin-top:10pt;">
                <div style="font-size:8pt; font-weight:bold; color:#374151; margin-bottom:4pt; text-transform:uppercase; letter-spacing:0.5pt;">
                    Periféricos asociados
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Categoría</th>
                            <th>Serial</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($item->hijos as $hijo)
                            <tr>
                                <td>{{ $hijo->equipo?->codigo_interno ?? '—' }}</td>
                                <td>{{ strtoupper($hijo->equipo?->categoria?->nombre ?? '—') }}</td>
                                <td>{{ $hijo->equipo?->serial ?? '—' }}</td>
                                <td>{{ $hijo->equipo?->nombre_maquina ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($item->asignacion?->observaciones)
            <div class="mb-4" style="margin-top:6pt;">
                <span class="field-label">Observaciones: </span>
                <span style="font-size:8.5pt; color:#374151;">{{ $item->asignacion->observaciones }}</span>
            </div>
        @endif
    </div>

    @if (!$loop->last)
        <hr class="divider" style="page-break-after: always;">
    @endif
@endforeach

@endsection