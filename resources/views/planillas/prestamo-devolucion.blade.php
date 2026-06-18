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

    $itemsDevueltos  = $prestamo->itemsDevueltos;
    $itemsPendientes = $prestamo->items->filter(fn ($i) => ! $i->devuelto)->values();
@endphp

@section('contenido')

<div class="doc-title">Constancia de Devolución de Préstamo</div>
<div class="doc-divider"></div>

<table class="doc-meta-table">
    <tr>
        <td class="doc-meta-left">Uso: Préstamo de Activos Tecnológicos</td>
        <td class="doc-meta-right">
            Fecha de Devolución: <strong>{{ $prestamo->fecha_devolucion_real?->format('d/m/Y') ?? $fecha }}</strong>
        </td>
    </tr>
</table>

{{-- ══ DATOS DEL USUARIO / ÁREA ════════════════════════════════════════════ --}}

@if (! $esArea)
    <div class="section-title-plain devolucion">Datos del Receptor</div>
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
                <div class="field-label">Empresa (nómina)</div>
                <div class="field-value">{{ $receptor?->empresaNomina?->nombre ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="field-label">Fecha de préstamo</div>
                <div class="field-value">{{ $prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Fecha devolución real</div>
                <div class="field-value">{{ $prestamo->fecha_devolucion_real?->format('d/m/Y') ?? $fecha }}</div>
            </td>
            <td>
                <div class="field-label">Analista que recibe</div>
                <div class="field-value">{{ $prestamo->analista?->name ?? '—' }}</div>
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
                <div class="field-label">Fecha de préstamo</div>
                <div class="field-value">{{ $prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Fecha devolución real</div>
                <div class="field-value">{{ $prestamo->fecha_devolucion_real?->format('d/m/Y') ?? $fecha }}</div>
            </td>
            <td>
                <div class="field-label">Analista que recibe</div>
                <div class="field-value">{{ $prestamo->analista?->name ?? '—' }}</div>
            </td>
        </tr>
    </table>
@endif

{{-- ══ EQUIPOS DEVUELTOS ════════════════════════════════════════════════════ --}}

@php
    $principalesDevueltos = $itemsDevueltos->filter(fn ($i) => $i->equipo_padre_id === null)->values();
    $perifericosDevueltos = $itemsDevueltos->filter(fn ($i) => $i->equipo_padre_id !== null)->values();
@endphp

<div class="section-title-plain devolucion">
    Equipos Devueltos
    &nbsp;·&nbsp;
    {{ $principalesDevueltos->count() }} {{ $principalesDevueltos->count() === 1 ? 'equipo' : 'equipos' }}
</div>

@forelse ($principalesDevueltos as $item)
    @php
        $eq        = $item->equipo;
        $atributos = $eq?->atributosParaReporte() ?? collect();
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
            <td></td>
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
        @if ($item->observaciones_devolucion)
            <tr>
                <td colspan="3">
                    <div class="field-label">Observaciones de devolución</div>
                    <div class="field-value">{{ $item->observaciones_devolucion }}</div>
                </td>
            </tr>
        @endif
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

{{-- ══ PERIFÉRICOS DEVUELTOS ═══════════════════════════════════════════════ --}}

@if ($perifericosDevueltos->isNotEmpty())
    <div class="section-title-plain">
        Periféricos Devueltos &nbsp;·&nbsp; {{ $perifericosDevueltos->count() }}
    </div>

    @foreach ($perifericosDevueltos as $item)
        @php
            $eqPer        = $item->equipo;
            $atributosPer = $eqPer?->atributosParaReporte() ?? collect();
        @endphp

        <table class="fields-table" style="margin-bottom: 6pt;">
            <tr>
                <td>
                    <div class="field-label">Código interno</div>
                    <div class="field-value valor-codigo">{{ $eqPer?->codigo_interno ?? '—' }}</div>
                </td>
                <td>
                    <div class="field-label">Categoría</div>
                    <div class="field-value">{{ strtoupper($eqPer?->categoria?->nombre ?? '—') }}</div>
                </td>
                <td>
                    <div class="field-label">Pertenece a</div>
                    <div class="field-value valor-referencia">↳ {{ $item->padre?->equipo?->codigo_interno ?? '—' }}</div>
                </td>
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
            @if ($item->observaciones_devolucion)
                <tr>
                    <td colspan="3">
                        <div class="field-label">Observaciones de devolución</div>
                        <div class="field-value">{{ $item->observaciones_devolucion }}</div>
                    </td>
                </tr>
            @endif
            @foreach ($atributosPer->chunk(3) as $fila)
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

{{-- ══ PENDIENTES (si quedan) ══════════════════════════════════════════════ --}}

@php
    $principalesPendientes = $itemsPendientes->filter(fn ($i) => $i->equipo_padre_id === null)->values();
    $perifericosPendientes = $itemsPendientes->filter(fn ($i) => $i->equipo_padre_id !== null)->values();
@endphp

@if ($principalesPendientes->isNotEmpty())
    <div class="section-title-plain" style="background:#7F1D1D;border-left-color:#FCA5A5;">
        Equipos Pendientes de Devolución &nbsp;·&nbsp; {{ $principalesPendientes->count() }}
    </div>

    @foreach ($principalesPendientes as $item)
        @php
            $eqPend        = $item->equipo;
            $atributosPend = $eqPend?->atributosParaReporte() ?? collect();
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

{{-- ══ PERIFÉRICOS PENDIENTES ═══════════════════════════════════════════════ --}}

@if ($perifericosPendientes->isNotEmpty())
    <div class="section-title-plain" style="background:#7F1D1D;border-left-color:#FCA5A5;">
        Periféricos Pendientes de Devolución &nbsp;·&nbsp; {{ $perifericosPendientes->count() }}
    </div>

    @foreach ($perifericosPendientes as $item)
        @php
            $eqPerPend        = $item->equipo;
            $atributosPerPend = $eqPerPend?->atributosParaReporte() ?? collect();
        @endphp
        <table class="fields-table" style="margin-bottom: 6pt;">
            <tr>
                <td>
                    <div class="field-label">Código interno</div>
                    <div class="field-value valor-codigo" style="background:#7F1D1D;">{{ $eqPerPend?->codigo_interno ?? '—' }}</div>
                </td>
                <td>
                    <div class="field-label">Categoría</div>
                    <div class="field-value">{{ strtoupper($eqPerPend?->categoria?->nombre ?? '—') }}</div>
                </td>
                <td>
                    <div class="field-label">Pertenece a</div>
                    <div class="field-value valor-referencia">↳ {{ $item->padre?->equipo?->codigo_interno ?? '—' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="field-label">Estado</div>
                    <div class="field-value"><span class="badge badge-pendiente">Pendiente</span></div>
                </td>
                <td colspan="2"></td>
            </tr>
            @foreach ($atributosPerPend->chunk(3) as $fila)
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

@if ($prestamo->observaciones)
    <div class="obs-box">
        <div class="obs-label">Observaciones</div>
        {{ $prestamo->observaciones }}
    </div>
@endif

@endsection

@section('firmas')
    @if (! $esArea)
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ $prestamo->analista?->name ?? 'Analista' }}</div>
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
            <div class="firma-nombre">{{ $prestamo->analista?->name ?? 'Analista' }}</div>
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
