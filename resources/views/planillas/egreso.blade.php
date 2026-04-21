@extends('planillas.layout')

@php
    /**
     * Planilla de Egreso / Offboarding (DC-ST-FO-09)
     * Historial completo de equipos del usuario.
     * NO ejecuta devoluciones — solo es soporte documental.
     */
    $codigoDoc   = 'DC-ST-FO-09';
    $empresaSede = $usuario->empresaNomina?->nombre ?? '—';
@endphp

@section('contenido')

<div class="doc-title">Formato de Egreso — Control de Activos Tecnológicos</div>
<div class="doc-subtitle">Documento de offboarding · No implica ejecución de devoluciones</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     DATOS DEL TRABAJADOR — 3 columnas, 2 filas
══════════════════════════════════════════════════════════════════════════ --}}

<div class="section">
    <div class="section-title egreso">Datos del Trabajador</div>
    <div class="fields-grid">

        {{-- Fila 1: Ficha · Nombre · Cédula --}}
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Ficha</div>
                <div class="field-value">{{ $usuario->ficha ?? '—' }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Nombre completo</div>
                <div class="field-value">{{ strtoupper($usuario->name ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Cédula de identidad</div>
                <div class="field-value">{{ $usuario->cedula ?? '—' }}</div>
            </div>
        </div>

        {{-- Fila 2: Empresa · Sede · Correo --}}
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Empresa (nómina)</div>
                <div class="field-value">{{ strtoupper($usuario->empresaNomina?->nombre ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Sede / Ubicación</div>
                <div class="field-value">{{ strtoupper($usuario->ubicacion?->nombre ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Correo electrónico</div>
                <div class="field-value">{{ $usuario->email ?? '—' }}</div>
            </div>
        </div>

        {{-- Fila 3: Departamento · Cargo · Supervisor --}}
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Departamento</div>
                <div class="field-value">{{ strtoupper($usuario->departamento?->nombre ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Cargo</div>
                <div class="field-value">{{ strtoupper($usuario->cargo?->nombre ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Supervisor directo</div>
                <div class="field-value">{{ strtoupper($usuario->jefe?->name ?? '—') }}</div>
            </div>
        </div>

        {{-- Fila 4: Fecha · Estado general --}}
        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Fecha de generación</div>
                <div class="field-value">{{ $fecha }}</div>
            </div>
            <div class="field-cell" style="colspan:2">
                <div class="field-label">Estado de equipos</div>
                @if ($pendientes->isNotEmpty())
                    <div class="field-value" style="color:#DC2626;">
                        ⚠ {{ $pendientes->count() }} equipo(s) PENDIENTE(S) DE ENTREGA
                    </div>
                @else
                    <div class="field-value" style="color:#065F46;">
                        ✓ Todos los equipos entregados
                    </div>
                @endif
            </div>
            <div class="field-cell"></div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN 1: EQUIPOS PENDIENTES DE ENTREGA
     Coloreado en rojo cuando hay pendientes
══════════════════════════════════════════════════════════════════════════ --}}

<div class="section">
    <div class="section-title {{ $pendientes->isNotEmpty() ? 'peligro' : '' }}"
         @if ($pendientes->isEmpty()) style="background:#065F46;" @endif>
        Equipos Pendientes de Entrega a TI
        @if ($pendientes->isNotEmpty())
            ({{ $pendientes->count() }})
        @else
            — Ninguno
        @endif
    </div>

    @if ($pendientes->isEmpty())
        <div style="padding:8pt 10pt; text-align:center; color:#065F46; font-style:italic; font-size:7.5pt;">
            ✓ El trabajador no tiene equipos pendientes de devolver.
        </div>
    @else
        <table class="data-table">
            <colgroup>
                <col style="width:11%">
                <col style="width:14%">
                <col style="width:11%">
                <col style="width:15%">
                <col style="width:13%">
                <col style="width:10%">
                <col style="width:13%">
                <col style="width:13%">
            </colgroup>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Hostname</th>
                    <th>Categoría</th>
                    <th>Marca / Modelo</th>
                    <th>Serial</th>
                    <th>F. Asignación</th>
                    <th>Características</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendientes as $item)
                    @php
                        $equipo    = $item->equipo;
                        $atributos = $equipo?->atributosActuales ?? collect();
                        $marca     = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor ?? null;
                        $modelo    = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor ?? null;
                        $eavExtra  = $atributos->filter(fn ($v) =>
                            $v->atributo?->visible_en_tabla &&
                            ! in_array(strtolower($v->atributo->nombre ?? ''), ['marca', 'modelo'])
                        );
                    @endphp
                    <tr class="pendiente">
                        <td class="cod-interno" style="color:#DC2626;">{{ $equipo?->codigo_interno ?? '—' }}</td>
                        <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                        <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>
                            {{ $marca ?? '—' }}
                            @if ($modelo) <span class="cell-sub">{{ $modelo }}</span> @endif
                        </td>
                        <td>{{ $equipo?->serial ?? '—' }}</td>
                        <td>{{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if ($eavExtra->isNotEmpty())
                                <span class="eav-inline">
                                    @foreach ($eavExtra as $av)
                                        <span class="eav-pill">{{ $av->atributo->nombre }}</span>{{ $av->valor }}&nbsp;
                                    @endforeach
                                </span>
                            @else —
                            @endif
                        </td>
                        <td><span class="badge badge-pendiente">PENDIENTE</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN 2: EQUIPOS YA DEVUELTOS — columnas reducidas para compactar
══════════════════════════════════════════════════════════════════════════ --}}

@if ($recibidos->isNotEmpty())
    <div class="section">
        <div class="section-title verde">
            Equipos Recibidos por TI ({{ $recibidos->count() }})
        </div>
        <table class="data-table">
            <colgroup>
                <col style="width:13%">
                <col style="width:17%">
                <col style="width:13%">
                <col style="width:17%">
                <col style="width:15%">
                <col style="width:13%">
                <col style="width:12%">
            </colgroup>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Hostname</th>
                    <th>Categoría</th>
                    <th>Marca / Modelo</th>
                    <th>Serial</th>
                    <th>Características</th>
                    <th>F. Devolución</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recibidos as $item)
                    @php
                        $equipo    = $item->equipo;
                        $atributos = $equipo?->atributosActuales ?? collect();
                        $marca     = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor ?? null;
                        $modelo    = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor ?? null;
                        $eavExtra  = $atributos->filter(fn ($v) =>
                            $v->atributo?->visible_en_tabla &&
                            ! in_array(strtolower($v->atributo->nombre ?? ''), ['marca', 'modelo'])
                        );
                    @endphp
                    <tr class="devuelto">
                        <td class="cod-interno" style="color:#9CA3AF;">{{ $equipo?->codigo_interno ?? '—' }}</td>
                        <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                        <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>
                            {{ $marca ?? '—' }}
                            @if ($modelo) <span class="cell-sub">{{ $modelo }}</span> @endif
                        </td>
                        <td>{{ $equipo?->serial ?? '—' }}</td>
                        <td>
                            @if ($eavExtra->isNotEmpty())
                                <span class="eav-inline">
                                    @foreach ($eavExtra as $av)
                                        <span class="eav-pill">{{ $av->atributo->nombre }}</span>{{ $av->valor }}&nbsp;
                                    @endforeach
                                </span>
                            @else —
                            @endif
                        </td>
                        <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Nota de pendientes --}}
@if ($pendientes->isNotEmpty())
    <div class="obs-box">
        <div class="obs-label">Nota importante</div>
        Los equipos marcados como PENDIENTE deben ser entregados a la Gerencia de Tecnología
        antes de completar el proceso de desvinculación. Este documento no libera al trabajador
        de la responsabilidad sobre los activos no devueltos.
    </div>
@endif

@endsection


@section('firmas')

    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($usuario->name ?? 'Trabajador') }}</div>
        <div class="firma-cargo">Trabajador que egresa</div>
    </div>

    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($usuario->jefe?->name ?? 'Supervisor') }}</div>
        <div class="firma-cargo">Supervisor / Jefe directo</div>
    </div>

    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">Gerencia de Tecnología</div>
        <div class="firma-cargo">Receptor de activos</div>
    </div>

@endsection