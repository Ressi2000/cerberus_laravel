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

<div class="section-title-plain" style="background:#7F1D1D;border-left-color:#FCA5A5;">
    Equipos Pendientes de Entrega a TI
    @if ($pendientes->isNotEmpty())
        &nbsp;·&nbsp; {{ $pendientes->count() }}
    @else
        — Ninguno
    @endif
</div>

@forelse ($pendientes as $item)
    @php
        $equipo    = $item->equipo;
        $atributos = ($equipo?->atributosActuales ?? collect())
            ->filter(fn ($v) => $v->atributo?->ver_en_reporte)
            ->sortBy(fn ($v) => $v->atributo?->orden ?? 99);
    @endphp

    <table class="fields-table" style="margin-bottom: 6pt;">
        <tr>
            <td>
                <div class="field-label">Código interno</div>
                <div class="field-value valor-codigo" style="background:#7F1D1D;">{{ $equipo?->codigo_interno ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Categoría</div>
                <div class="field-value">{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</div>
            </td>
            <td>
                <div class="field-label">Estado</div>
                <div class="field-value"><span class="badge badge-pendiente">Pendiente</span></div>
            </td>
        </tr>
        @foreach ($atributos->chunk(3) as $fila)
            <tr>
                @foreach ($fila as $valor)
                    <td>
                        <div class="field-label">{{ $valor->atributo?->nombre }}</div>
                        <div class="field-value">{{ $valor->valor }}</div>
                    </td>
                @endforeach
                @for ($i = $fila->count(); $i < 3; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
        <tr>
            <td colspan="3">
                <div class="field-label">Motivo de no entrega (a completar por RRHH / Analista)</div>
                <div class="field-value" style="min-height:14pt;border-bottom:0.5pt solid #CBD5E1;">&nbsp;</div>
            </td>
        </tr>
    </table>
@empty
    <p class="field-value" style="color:#065F46;">✓ El trabajador no tiene equipos pendientes de devolver.</p>
@endforelse

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN 2: EQUIPOS YA DEVUELTOS
══════════════════════════════════════════════════════════════════════════ --}}

@if ($recibidos->isNotEmpty())
    <div class="section-title-plain" style="background:#065F46;border-left-color:#6EE7B7;">
        Equipos Recibidos por TI &nbsp;·&nbsp; {{ $recibidos->count() }}
    </div>

    @foreach ($recibidos as $item)
        @php
            $equipo    = $item->equipo;
            $atributos = ($equipo?->atributosActuales ?? collect())
                ->filter(fn ($v) => $v->atributo?->ver_en_reporte)
                ->sortBy(fn ($v) => $v->atributo?->orden ?? 99);
        @endphp

        <table class="fields-table" style="margin-bottom: 6pt;">
            <tr>
                <td>
                    <div class="field-label">Código interno</div>
                    <div class="field-value valor-codigo">{{ $equipo?->codigo_interno ?? '—' }}</div>
                </td>
                <td>
                    <div class="field-label">Categoría</div>
                    <div class="field-value">{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</div>
                </td>
                <td>
                    <div class="field-label">Fecha de devolución</div>
                    <div class="field-value">{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</div>
                </td>
            </tr>
            @foreach ($atributos->chunk(3) as $fila)
                <tr>
                    @foreach ($fila as $valor)
                        <td>
                            <div class="field-label">{{ $valor->atributo?->nombre }}</div>
                            <div class="field-value">{{ $valor->valor }}</div>
                        </td>
                    @endforeach
                    @for ($i = $fila->count(); $i < 3; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @endforeach
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
        <div class="firma-nombre">Gerencia de Tecnología</div>
        <div class="firma-cargo">Receptor de activos</div>
    </div>

@endsection