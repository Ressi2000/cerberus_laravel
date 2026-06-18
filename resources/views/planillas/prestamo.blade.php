@extends('planillas.layout')

@php
    $esArea      = $prestamo->tipoReceptor() === 'area';
    $codigoDoc   = 'DC-ST-FO-PRE';
    $tituloDoc   = 'Formato de Préstamo de Activos Tecnológicos';
    $empresaSede = $prestamo->empresa->nombre ?? '—';

    $receptor    = $prestamo->usuario;
    $areaEmpresa = $prestamo->areaEmpresa;
    $areaDpto    = $prestamo->areaDepartamento;
    $areaResp    = $prestamo->areaResponsable;

    $itemsActivos = $prestamo->itemsActivos;
    $principales  = $itemsActivos->filter(fn ($i) => $i->equipo_padre_id === null)->values();
    $perifericos  = $itemsActivos->filter(fn ($i) => $i->equipo_padre_id !== null)->values();
@endphp

@section('contenido')

<div class="doc-title">Formato de Préstamo de Activos Tecnológicos</div>
<div class="doc-divider"></div>

@if ($prestamo->fecha_devolucion_esperada)
<table class="doc-meta-table">
    <tr>
        <td class="doc-meta-left">
            Fecha de préstamo: <strong>{{ $prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}</strong>
        </td>
        <td class="doc-meta-right" style="{{ $prestamo->estaVencido() ? 'color:#DC2626;font-weight:bold;' : '' }}">
            Fecha de devolución esperada: <strong>{{ $prestamo->fecha_devolucion_esperada->format('d/m/Y') }}</strong>
        </td>
    </tr>
</table>
@endif

{{-- ══ DATOS DEL RECEPTOR / ÁREA ════════════════════════════════════════════ --}}

@if (! $esArea)
    <div class="section-title-plain">Datos del Receptor</div>
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
                <div class="field-label">Sede del préstamo</div>
                <div class="field-value">{{ $prestamo->empresa?->nombre ?? '—' }}</div>
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
                <div class="field-label">Analista responsable</div>
                <div class="field-value">{{ $prestamo->analista?->name ?? '—' }}</div>
            </td>
        </tr>
    </table>
@else
    <div class="section-title-plain area">Datos del Área Receptora</div>
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
                <div class="field-label">Sede del préstamo</div>
                <div class="field-value">{{ strtoupper($prestamo->empresa?->nombre ?? '—') }}</div>
            </td>
            <td>
                <div class="field-label">Analista responsable</div>
                <div class="field-value">{{ strtoupper($prestamo->analista?->name ?? '—') }}</div>
            </td>
        </tr>
    </table>
@endif

{{-- ══ EQUIPOS EN PRÉSTAMO ══════════════════════════════════════════════════ --}}

<div class="section-title-plain">
    Equipos Entregados en Préstamo
    &nbsp;·&nbsp;
    {{ $principales->count() }} {{ $principales->count() === 1 ? 'equipo' : 'equipos' }}
</div>

@forelse ($principales as $item)
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
    <p class="field-value">No hay equipos activos en este préstamo.</p>
@endforelse

{{-- ══ PERIFÉRICOS ══════════════════════════════════════════════════════════ --}}

@if ($perifericos->isNotEmpty())
    <div class="section-title-plain">
        Periféricos en Préstamo &nbsp;·&nbsp; {{ $perifericos->count() }}
    </div>

    @foreach ($perifericos as $item)
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
                    <div class="field-value valor-referencia">» {{ $item->padre?->equipo?->codigo_interno ?? '—' }}</div>
                </td>
            </tr>
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
            <div class="firma-cargo">Técnico / Analista</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ $receptor?->name ?? 'Receptor' }}</div>
            <div class="firma-cargo">Trabajador receptor</div>
        </div>
    @else
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ $prestamo->analista?->name ?? 'Analista' }}</div>
            <div class="firma-cargo">Técnico / Analista</div>
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
