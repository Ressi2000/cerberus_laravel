@extends('planillas.layout')

@php
    $esArea          = $asignacion->tipoReceptor() === 'area';
    $codigoDoc       = 'DC-ST-FO-10';
    $tituloDoc       = 'Formato de Devolución de Activos Tecnológicos';
    $empresaSede     = $asignacion->empresa->nombre ?? '—';

    $receptor        = $asignacion->usuario;
    $areaEmpresa     = $asignacion->areaEmpresa;
    $areaDpto        = $asignacion->areaDepartamento;
    $areaResp        = $asignacion->areaResponsable;
    $itemsDevueltos  = $asignacion->itemsDevueltos;
    $itemsPendientes = $asignacion->items->filter(fn($i) => ! $i->devuelto)->values();
@endphp

@section('contenido')

<div class="doc-title">Formato de Devolución de Activos Tecnológicos</div>
<div class="doc-divider"></div>

<table class="doc-meta-table">
    <tr>
        <td class="doc-meta-left">Uso: Actividades Inherentes al Cargo</td>
        <td class="doc-meta-right">
            Fecha de Devolución: <strong>{{ $fecha }}</strong>
        </td>
    </tr>
</table>

{{-- ══ DATOS DEL USUARIO / ÁREA ════════════════════════════════════════════ --}}

@if (! $esArea)
    <div class="section-title-plain devolucion">Datos del Usuario</div>
    <table class="fields-table">
        <tr>
            <td>
                <div class="field-label">Nombre completo</div>
                <div class="field-value valor-clave">{{ $receptor?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Ficha</div>
                <div class="field-value">{{ $receptor?->ficha ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Cédula de identidad</div>
                <div class="field-value">{{ $receptor?->cedula ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="field-label">Empresa (nómina)</div>
                <div class="field-value">{{ $receptor?->empresaNomina?->nombre ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Sede</div>
                <div class="field-value">{{ $asignacion->empresa?->nombre ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Correo electrónico</div>
                <div class="field-value">{{ $receptor?->email ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="field-label">Departamento</div>
                <div class="field-value">{{ $receptor?->departamento?->nombre ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Cargo</div>
                <div class="field-value">{{ $receptor?->cargo?->nombre ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Analista que recibe</div>
                <div class="field-value">{{ $asignacion->analista?->name ?? '—' }}</div>
            </td>
        </tr>
    </table>
@else
    <div class="section-title-plain area">Datos del Área</div>
    <table class="fields-table">
        <tr>
            <td>
                <div class="field-label">Empresa del área</div>
                <div class="field-value">{{ strtoupper($areaEmpresa?->nombre ?? '—') }}</div>
            </td>
            <td>
                <div class="field-label">Departamento / Área</div>
                <div class="field-value valor-clave">{{ strtoupper($areaDpto?->nombre ?? '—') }}</div>
            </td>
            <td>
                <div class="field-label">Responsable del área</div>
                <div class="field-value valor-clave">{{ strtoupper($areaResp?->name ?? '—') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="field-label">Cargo del responsable</div>
                <div class="field-value">{{ strtoupper($areaResp?->cargo?->nombre ?? '—') }}</div>
            </td>
            <td>
                <div class="field-label">Analista que recibe</div>
                <div class="field-value">{{ $asignacion->analista?->name ?? '—' }}</div>
            </td>
            <td></td>
        </tr>
    </table>
@endif

{{-- ══ EQUIPOS DEVUELTOS ════════════════════════════════════════════════════ --}}

<div class="section-title-plain devolucion">
    Equipos Devueltos
    &nbsp;·&nbsp;
    {{ $itemsDevueltos->count() }} {{ $itemsDevueltos->count() === 1 ? 'equipo' : 'equipos' }}
</div>

@forelse ($itemsDevueltos as $item)
    @php
        $eq        = $item->equipo;
        $esPerif   = $item->equipo_padre_id !== null;
        $atributos = ($eq?->atributosActuales ?? collect())
            ->filter(fn ($v) => $v->atributo?->ver_en_reporte)
            ->sortBy(fn ($v) => $v->atributo?->orden ?? 99);
    @endphp

    <table class="fields-table" style="margin-bottom: 6pt;">
        <tr>
            <td>
                <div class="field-label">Código interno</div>
                <div class="field-value valor-codigo">{{ $eq?->codigo_interno ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Categoría</div>
                <div class="field-value">{{ strtoupper($eq?->categoria?->nombre ?? '—') }}</div>
            </td>
            @if ($esPerif)
                <td>
                    <div class="field-label">Pertenece a</div>
                    <div class="field-value valor-referencia">↳ {{ $item->padre?->equipo?->codigo_interno ?? '—' }}</div>
                </td>
            @else
                <td></td>
            @endif
        </tr>
        <tr>
            <td>
                <div class="field-label">Fecha de devolución</div>
                <div class="field-value">{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Recibido por</div>
                <div class="field-value">{{ $item->devueltoPor?->name ?? '—' }}</div>
            </td>
            <td></td>
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
@empty
    <p class="field-value">No hay equipos devueltos registrados.</p>
@endforelse

{{-- ══ PENDIENTES (si quedan) ══════════════════════════════════════════════ --}}

@if ($itemsPendientes->isNotEmpty())
    <div class="section-title-plain" style="background:#7F1D1D;border-left-color:#FCA5A5;">
        Equipos Pendientes de Devolución &nbsp;·&nbsp; {{ $itemsPendientes->count() }}
    </div>

    @foreach ($itemsPendientes as $item)
        @php
            $eqPend       = $item->equipo;
            $atributosPend = ($eqPend?->atributosActuales ?? collect())
                ->filter(fn ($v) => $v->atributo?->ver_en_reporte)
                ->sortBy(fn ($v) => $v->atributo?->orden ?? 99);
        @endphp
        <table class="fields-table" style="margin-bottom: 6pt;">
            <tr>
                <td>
                    <div class="field-label">Código interno</div>
                    <div class="field-value valor-codigo" style="background:#7F1D1D;">{{ $eqPend?->codigo_interno ?? '—' }}</div>
                </td>
                <td>
                    <div class="field-label">Categoría</div>
                    <div class="field-value">{{ strtoupper($eqPend?->categoria?->nombre ?? '—') }}</div>
                </td>
                <td>
                    <div class="field-label">Estado</div>
                    <div class="field-value"><span class="badge badge-pendiente">Pendiente</span></div>
                </td>
            </tr>
            @foreach ($atributosPend->chunk(3) as $fila)
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

@if ($asignacion->observaciones)
    <div class="obs-box">
        <div class="obs-label">Observaciones</div>
        {{ $asignacion->observaciones }}
    </div>
@endif

@endsection


@section('firmas')
    @if (! $esArea)
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ $asignacion->analista?->name ?? 'Analista' }}</div>
            <div class="firma-cargo">Técnico que recibe</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ $receptor?->name ?? 'Trabajador' }}</div>
            <div class="firma-cargo">Trabajador que entrega</div>
        </div>
    @else
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ $asignacion->analista?->name ?? 'Analista' }}</div>
            <div class="firma-cargo">Técnico que recibe</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($areaResp?->name ?? 'Responsable') }}</div>
            <div class="firma-cargo">Responsable del área</div>
        </div>
        <div class="firma-cell"></div>
    @endif
@endsection