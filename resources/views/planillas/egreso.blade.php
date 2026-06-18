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
<div class="doc-divider"></div>

<table class="doc-meta-table">
    <tr>
        <td class="doc-meta-left">Documento de offboarding · No implica ejecución de devoluciones</td>
        <td class="doc-meta-right">
            Fecha de generación: <strong>{{ $fecha }}</strong>
        </td>
    </tr>
</table>

{{-- ══ DATOS DEL TRABAJADOR ═════════════════════════════════════════════════ --}}

<div class="section-title-plain egreso">Datos del Trabajador</div>
<table class="fields-table">
    <tr>
        <td>
            <div class="field-label">Nombre completo</div>
            <div class="field-value valor-clave">{{ $usuario->name ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Ficha</div>
            <div class="field-value">{{ $usuario->ficha ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Cédula de identidad</div>
            <div class="field-value">{{ $usuario->cedula ?? '—' }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="field-label">Empresa (nómina)</div>
            <div class="field-value">{{ $usuario->empresaNomina?->nombre ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Sede / Ubicación</div>
            <div class="field-value">{{ $usuario->ubicacion?->nombre ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Correo electrónico</div>
            <div class="field-value">{{ $usuario->email ?? '—' }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="field-label">Departamento</div>
            <div class="field-value">{{ $usuario->departamento?->nombre ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Cargo</div>
            <div class="field-value">{{ $usuario->cargo?->nombre ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Supervisor directo</div>
            <div class="field-value">{{ $usuario->jefe?->name ?? '—' }}</div>
        </td>
    </tr>
</table>

<table class="doc-meta-table" style="margin-top:6pt;">
    <tr>
        <td class="doc-meta-left">
            Estado de equipos:
            @if ($pendientes->isNotEmpty())
                <strong style="color:#DC2626;">⚠ {{ $pendientes->count() }} equipo(s) PENDIENTE(S) DE ENTREGA</strong>
            @else
                <strong style="color:#065F46;">✓ Todos los equipos entregados</strong>
            @endif
        </td>
        <td class="doc-meta-right"></td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN 1: EQUIPOS PENDIENTES DE ENTREGA
══════════════════════════════════════════════════════════════════════════ --}}

<div class="equipos-section">
    <table class="data-table">
        <thead>
            <tr class="thead-banner {{ $pendientes->isNotEmpty() ? 'peligro' : 'verde' }}">
                <td colspan="6">
                    Equipos Pendientes de Entrega a TI
                    @if ($pendientes->isNotEmpty())
                        &nbsp;·&nbsp; {{ $pendientes->count() }}
                    @else
                        — Ninguno
                    @endif
                </td>
            </tr>
            @if ($pendientes->isNotEmpty())
                <tr class="thead-cols">
                    <th style="width:13%">Código</th>
                    <th style="width:16%">Hostname</th>
                    <th style="width:13%">Categoría</th>
                    <th style="width:17%">Marca / Modelo</th>
                    <th style="width:14%">Serial</th>
                    <th style="width:13%">F. Asignación</th>
                </tr>
            @endif
        </thead>
        <tbody>
        @if ($pendientes->isEmpty())
            <tr>
                <td colspan="6" style="text-align:center;color:#065F46;font-style:italic;padding:12pt;">
                    ✓ El trabajador no tiene equipos pendientes de devolver.
                </td>
            </tr>
        @else
            @foreach ($pendientes as $item)
                @php
                    $equipo    = $item->equipo;
                    $atributos = $equipo?->atributosActuales ?? collect();
                    $marca     = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor ?? null;
                    $modelo    = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor ?? null;
                    $eavExtra  = $atributos->filter(fn ($v) =>
                        $v->atributo?->ver_en_reporte &&
                        ! in_array(strtolower($v->atributo->nombre ?? ''), ['marca', 'modelo'])
                    );
                @endphp
                <tr class="tr-pendiente">
                    <td class="cod-interno" style="color:#DC2626;">{{ $equipo?->codigo_interno ?? '—' }}</td>
                    <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                    <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                    <td>
                        {{ $marca ?? '—' }}
                        @if ($modelo) <span class="cell-sub">{{ $modelo }}</span> @endif
                    </td>
                    <td>{{ $equipo?->serial ?? '—' }}</td>
                    <td>{{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @include('planillas._eav', ['atributos' => $eavExtra, 'esPeriferico' => false, 'colspan' => 6])
                <tr class="tr-pendiente">
                    <td colspan="6" style="padding-top:2pt;">
                        <div class="field-label">Motivo de no entrega (a completar por RRHH / Analista)</div>
                        <div class="field-value" style="min-height:14pt;border-bottom:0.5pt solid #CBD5E1;">&nbsp;</div>
                    </td>
                </tr>
            @endforeach
        @endif
        </tbody>
    </table>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN 2: EQUIPOS YA DEVUELTOS — columnas reducidas para compactar
══════════════════════════════════════════════════════════════════════════ --}}

@if ($recibidos->isNotEmpty())
    <div class="equipos-section">
        <table class="data-table">
            <thead>
                <tr class="thead-banner verde">
                    <td colspan="6">Equipos Recibidos por TI &nbsp;·&nbsp; {{ $recibidos->count() }}</td>
                </tr>
                <tr class="thead-cols">
                    <th style="width:13%">Código</th>
                    <th style="width:17%">Hostname</th>
                    <th style="width:13%">Categoría</th>
                    <th style="width:17%">Marca / Modelo</th>
                    <th style="width:15%">Serial</th>
                    <th style="width:13%">F. Devolución</th>
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
                            $v->atributo?->ver_en_reporte &&
                            ! in_array(strtolower($v->atributo->nombre ?? ''), ['marca', 'modelo'])
                        );
                    @endphp
                    <tr class="tr-impar">
                        <td class="cod-interno" style="color:#9CA3AF;">{{ $equipo?->codigo_interno ?? '—' }}</td>
                        <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                        <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>
                            {{ $marca ?? '—' }}
                            @if ($modelo) <span class="cell-sub">{{ $modelo }}</span> @endif
                        </td>
                        <td>{{ $equipo?->serial ?? '—' }}</td>
                        <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    @include('planillas._eav', ['atributos' => $eavExtra, 'esPeriferico' => false, 'colspan' => 6])
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
        <div class="firma-nombre">{{ $usuario->name ?? 'Trabajador' }}</div>
        <div class="firma-cargo">Trabajador que egresa</div>
    </div>

    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ $usuario->jefe?->name ?? 'Supervisor' }}</div>
        <div class="firma-cargo">Supervisor / Jefe directo</div>
    </div>

    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">Gerencia de Tecnología</div>
        <div class="firma-cargo">Receptor de activos</div>
    </div>

@endsection