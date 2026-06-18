@extends('planillas.layout')

@php
    $codigoDoc   = 'DC-ST-FO-11';
    $tituloDoc   = 'Formato de Traslado de Activos Tecnológicos';
    $empresaSede = $traslado->empresa->nombre ?? '—';
@endphp

@section('contenido')

<div class="doc-title">Formato de Traslado de Activos Tecnológicos</div>
<div class="doc-divider"></div>

<table class="doc-meta-table">
    <tr>
        <td class="doc-meta-left">N° de traslado: <strong>{{ $traslado->numero }}</strong></td>
        <td class="doc-meta-right">
            Fecha del traslado: <strong>{{ $traslado->fecha_traslado?->format('d/m/Y') ?? '—' }}</strong>
        </td>
    </tr>
</table>

{{-- ══ DATOS DEL TRASLADO ══════════════════════════════════════════════════ --}}

<div class="section-title-plain">Datos del Traslado</div>
<table class="fields-table">
    <tr>
        <td>
            <div class="field-label">Empresa</div>
            <div class="field-value">{{ $traslado->empresa?->nombre ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Ubicación de origen</div>
            <div class="field-value valor-clave">{{ $traslado->ubicacionOrigen?->nombre ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Ubicación de destino</div>
            <div class="field-value valor-clave">{{ $traslado->ubicacionDestino?->nombre ?? '—' }}</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="field-label">Motivo del traslado</div>
            <div class="field-value">{{ $traslado->motivo }}</div>
        </td>
    </tr>
</table>

{{-- ══ RESPONSABLES ════════════════════════════════════════════════════════ --}}

<div class="section-title-plain">Responsables</div>
<table class="fields-table">
    <tr>
        <td>
            <div class="field-label">Realizado por</div>
            <div class="field-value">{{ $traslado->realizadoPor?->name ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Quien recibe</div>
            <div class="field-value">{{ $traslado->recibe?->name ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Quien autoriza</div>
            <div class="field-value">{{ $traslado->autoriza?->name ?? '—' }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="field-label">Cédula (realiza)</div>
            <div class="field-value">{{ $traslado->realizadoPor?->cedula ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Cédula (recibe)</div>
            <div class="field-value">{{ $traslado->recibe?->cedula ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Cédula (autoriza)</div>
            <div class="field-value">{{ $traslado->autoriza?->cedula ?? '—' }}</div>
        </td>
    </tr>
</table>

{{-- ══ EQUIPOS TRASLADADOS ══════════════════════════════════════════════════ --}}

<div class="section-title-plain">
    Equipos Trasladados
    &nbsp;·&nbsp;
    {{ $traslado->items->count() }} {{ $traslado->items->count() === 1 ? 'equipo' : 'equipos' }}
</div>

@forelse ($traslado->items as $item)
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
                <div class="field-label">Estado actual</div>
                <div class="field-value">{{ $equipo?->estado?->nombre ?? '—' }}</div>
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
@empty
    <p class="field-value">Sin equipos registrados en este traslado.</p>
@endforelse

@if ($traslado->observaciones)
    <div class="obs-box">
        <div class="obs-label">Observaciones</div>
        {{ $traslado->observaciones }}
    </div>
@endif

@endsection

@section('firmas')
    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ $traslado->realizadoPor?->name ?? '—' }}</div>
        <div class="firma-cargo">Analista responsable</div>
    </div>
    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ $traslado->recibe?->name ?? '—' }}</div>
        <div class="firma-cargo">Recibe conforme</div>
    </div>
    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ $traslado->autoriza?->name ?? '—' }}</div>
        <div class="firma-cargo">Autoriza el traslado</div>
    </div>
@endsection
