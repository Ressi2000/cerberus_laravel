@extends('planillas.layout')

@php
    /**
     * Planilla de Egreso / Offboarding (DC-ST-FO-09)
     *
     * Documento para el proceso de salida de RRHH.
     * Muestra el historial completo de equipos del usuario:
     *   - Sección 1: Equipos actualmente asignados (pendientes de devolver)
     *   - Sección 2: Equipos ya devueltos (recibidos por TI)
     *
     * Este documento NO ejecuta ninguna devolución.
     * Solo es el soporte documental del proceso de egreso.
     */
    $codigoDoc   = 'DC-ST-FO-09';
    $empresaSede = $usuario->empresaNomina?->nombre ?? '—';
@endphp

@section('contenido')

{{-- ── TÍTULO ────────────────────────────────────────────────────────────── --}}
<div class="doc-title">
    Formato de Egreso — Control de Activos Tecnológicos
</div>
<div class="doc-subtitle">
    Documento de offboarding · No implica ejecución de devoluciones
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     DATOS DEL TRABAJADOR
══════════════════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title egreso">Datos del Trabajador</div>
    <div class="fields-grid">

        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Ficha</div>
                <div class="field-value">{{ $usuario->ficha ?? '—' }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Nombre completo</div>
                <div class="field-value">{{ strtoupper($usuario->name ?? '—') }}</div>
            </div>
        </div>

        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Cédula de identidad</div>
                <div class="field-value">{{ $usuario->cedula ?? '—' }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Correo electrónico</div>
                <div class="field-value">{{ $usuario->email ?? '—' }}</div>
            </div>
        </div>

        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Empresa (nómina)</div>
                <div class="field-value">{{ strtoupper($usuario->empresaNomina?->nombre ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Sede / Ubicación</div>
                <div class="field-value">{{ strtoupper($usuario->ubicacion?->nombre ?? '—') }}</div>
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
                <div class="field-label">Supervisor directo</div>
                <div class="field-value">{{ strtoupper($usuario->jefe?->name ?? '—') }}</div>
            </div>
            <div class="field-cell">
                <div class="field-label">Cargo del supervisor</div>
                <div class="field-value">{{ strtoupper($usuario->jefe?->cargo?->nombre ?? '—') }}</div>
            </div>
        </div>

        <div class="fields-row">
            <div class="field-cell">
                <div class="field-label">Fecha de generación</div>
                <div class="field-value">{{ $fecha }}</div>
            </div>
            <div class="field-cell">
                {{-- Resumen rápido de pendientes --}}
                @if ($pendientes->isNotEmpty())
                    <div class="field-label">Estado de equipos</div>
                    <div class="field-value" style="color:#DC2626;">
                        {{ $pendientes->count() }} equipo(s) PENDIENTE(S) DE ENTREGA
                    </div>
                @else
                    <div class="field-label">Estado de equipos</div>
                    <div class="field-value" style="color:#065F46;">
                        Todos los equipos entregados ✓
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN 1: EQUIPOS PENDIENTES DE ENTREGA (asignados actualmente)
     Coloreado en rojo cuando hay pendientes — llama la atención visualmente
══════════════════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title" style="background:{{ $pendientes->isNotEmpty() ? '#7F1D1D' : '#1E3A8A' }};">
        Equipos Pendientes de Entrega a TI
        @if ($pendientes->isNotEmpty())
            ({{ $pendientes->count() }})
        @else
            — Ninguno
        @endif
    </div>

    @if ($pendientes->isEmpty())
        <div style="padding: 12pt 10pt; text-align:center; color:#065F46; font-style:italic; font-size:8.5pt;">
            ✓ El trabajador no tiene equipos pendientes de devolver.
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:75pt">Código</th>
                    <th style="width:85pt">Nombre / Hostname</th>
                    <th style="width:65pt">Categoría</th>
                    <th style="width:75pt">Marca · Modelo</th>
                    <th style="width:70pt">Serial</th>
                    <th style="width:55pt">Fecha asignación</th>
                    <th style="width:55pt">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendientes as $item)
                    @php
                        $equipo    = $item->equipo;
                        $atributos = $equipo?->atributosActuales ?? collect();
                        $marca     = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor
                                     ?? null;
                        $modelo    = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor
                                     ?? null;
                        $eavExtra  = $atributos->filter(fn ($v) =>
                            $v->atributo?->visible_en_tabla &&
                            ! in_array(strtolower($v->atributo->nombre ?? ''), ['marca', 'modelo'])
                        );
                    @endphp
                    <tr class="pendiente">
                        <td class="cod-interno" style="color:#DC2626;">
                            {{ $equipo?->codigo_interno ?? '—' }}
                        </td>
                        <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                        <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>
                            @if ($marca || $modelo)
                                {{ $marca ?? '—' }}
                                @if ($modelo)
                                    <br><span style="font-size:7.5pt;color:#6B7280;">{{ $modelo }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $equipo?->serial ?? '—' }}</td>
                        <td>{{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge badge-pendiente">PENDIENTE</span>
                        </td>
                    </tr>

                    {{-- EAV extra --}}
                    @if ($eavExtra->isNotEmpty())
                        <tr class="eav-row">
                            <td colspan="7">
                                @foreach ($eavExtra as $av)
                                    <span class="eav-pill">{{ $av->atributo->nombre }}</span>
                                    {{ $av->valor }}
                                    &nbsp;&nbsp;
                                @endforeach
                            </td>
                        </tr>
                    @endif

                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN 2: EQUIPOS YA DEVUELTOS (recibidos por TI)
══════════════════════════════════════════════════════════════════════════ --}}
@if ($recibidos->isNotEmpty())
<div class="section">
    <div class="section-title" style="background:#065F46;">
        Equipos Recibidos por TI ({{ $recibidos->count() }})
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:75pt">Código</th>
                <th style="width:85pt">Nombre / Hostname</th>
                <th style="width:65pt">Categoría</th>
                <th style="width:75pt">Marca · Modelo</th>
                <th style="width:70pt">Serial</th>
                <th style="width:60pt">Fecha devolución</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($recibidos as $item)
                @php
                    $equipo    = $item->equipo;
                    $atributos = $equipo?->atributosActuales ?? collect();
                    $marca     = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor
                                 ?? null;
                    $modelo    = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor
                                 ?? null;
                    $eavExtra  = $atributos->filter(fn ($v) =>
                        $v->atributo?->visible_en_tabla &&
                        ! in_array(strtolower($v->atributo->nombre ?? ''), ['marca', 'modelo'])
                    );
                @endphp
                <tr class="devuelto">
                    <td class="cod-interno" style="color:#6B7280;">
                        {{ $equipo?->codigo_interno ?? '—' }}
                    </td>
                    <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                    <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                    <td>
                        @if ($marca || $modelo)
                            {{ $marca ?? '—' }}
                            @if ($modelo)
                                <br><span style="font-size:7.5pt;color:#6B7280;">{{ $modelo }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $equipo?->serial ?? '—' }}</td>
                    <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                </tr>

                {{-- EAV extra --}}
                @if ($eavExtra->isNotEmpty())
                    <tr class="eav-row">
                        <td colspan="6">
                            @foreach ($eavExtra as $av)
                                <span class="eav-pill">{{ $av->atributo->nombre }}</span>
                                {{ $av->valor }}
                                &nbsp;&nbsp;
                            @endforeach
                        </td>
                    </tr>
                @endif

            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Caja de observaciones si hay pendientes ─────────────────────────── --}}
@if ($pendientes->isNotEmpty())
    <div class="obs-box">
        <div class="obs-label">Nota importante</div>
        Los equipos marcados como PENDIENTE deben ser entregados a la
        Gerencia de Tecnología antes de completar el proceso de desvinculación.
        El presente documento no libera al trabajador de la responsabilidad
        sobre los activos no devueltos.
    </div>
@endif

@endsection


@section('firmas')
<div class="firmas">

    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($usuario->name ?? 'Trabajador') }}</div>
        <div class="firma-label">Trabajador que egresa</div>
    </div>

    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($usuario->jefe?->name ?? 'Supervisor') }}</div>
        <div class="firma-label">Supervisor / Jefe directo</div>
    </div>

    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">Gerencia de Tecnología</div>
        <div class="firma-label">Receptor de activos</div>
    </div>

</div>
@endsection