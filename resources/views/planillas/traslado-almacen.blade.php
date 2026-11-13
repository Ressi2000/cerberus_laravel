@extends('planillas.layout')

@php
    $codigoDoc   = $traslado->folio();
    $tituloDoc   = 'Autorización de Traslado';
    $empresaSede = $traslado->empresaOrigen->nombre ?? '—';
    $piezas      = collect($traslado->detalle['piezas'] ?? []);
    $sinSerial   = (int) ($traslado->detalle['sin_serial'] ?? 0);
    $unidad      = $traslado->detalle['unidad'] ?? 'unidad';
    $plural      = fn (int $n) => $n === 1 ? $unidad : ($unidad === 'unidad' ? 'unidades' : $unidad . '(s)');
@endphp

@section('contenido')

<div class="doc-title">Autorización de Traslado</div>
<div class="doc-divider"></div>

<table class="doc-meta-table">
    <tr>
        <td class="doc-meta-left">N° de autorización: <strong>{{ $traslado->folio() }}</strong></td>
        <td class="doc-meta-right">Fecha del traslado: <strong>{{ $traslado->created_at->format('d/m/Y H:i') }}</strong></td>
    </tr>
</table>

{{-- ══ DE DÓNDE A DÓNDE ════════════════════════════════════════════════════ --}}

<div class="section-title-plain">Traslado entre almacenes</div>
<table class="fields-table">
    <tr>
        <td>
            <div class="field-label">Desde (almacén de origen)</div>
            <div class="field-value valor-clave">{{ $traslado->empresaOrigen->nombre ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Hacia (almacén de destino)</div>
            <div class="field-value valor-clave">{{ $traslado->empresaDestino->nombre ?? '—' }}</div>
        </td>
        <td>
            <div class="field-label">Registrado por</div>
            <div class="field-value">{{ $traslado->registradoPor?->name ?? '—' }}</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="field-label">Motivo del traslado</div>
            <div class="field-value">{!! nl2br(e($traslado->motivo)) !!}</div>
        </td>
    </tr>
</table>

{{-- ══ QUÉ SE TRASLADA ═════════════════════════════════════════════════════ --}}

<div class="section-title-plain">
    Qué se traslada
    &nbsp;·&nbsp;
    {{ $traslado->cantidad }} {{ $plural($traslado->cantidad) }}
</div>
<table class="fields-table">
    <tr>
        <td>
            <div class="field-label">Familia</div>
            <div class="field-value">{{ $traslado->familia ?? '—' }}</div>
        </td>
        <td colspan="2">
            <div class="field-label">Variante</div>
            <div class="field-value">{{ $traslado->variante }}</div>
        </td>
    </tr>
</table>

@if ($piezas->isNotEmpty())
    <table class="data-table" style="margin-top: 6pt;">
        <thead>
            <tr class="thead-cols">
                <th style="width: 8%;">#</th>
                <th>Serial / código</th>
                <th style="width: 25%;">Condición</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($piezas as $i => $p)
                <tr class="{{ $i % 2 ? 'tr-impar' : 'tr-par' }}">
                    <td>{{ $i + 1 }}</td>
                    <td class="cod-interno">{{ $p['codigo'] }}</td>
                    <td>{{ $p['condicion'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if ($sinSerial > 0)
    <p class="field-value" style="margin-top: 6pt;">
        {{ $piezas->isNotEmpty() ? 'Además, ' : '' }}{{ $sinSerial }} {{ $plural($sinSerial) }} {{ $piezas->isNotEmpty() ? 'sin serial' : '' }} contadas por cantidad.
    </p>
@endif

@endsection

@section('firmas')
    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ $traslado->registradoPor?->name ?? '—' }}</div>
        <div class="firma-cargo">Entrega · {{ $traslado->empresaOrigen->nombre ?? '' }}</div>
    </div>
    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">&nbsp;</div>
        <div class="firma-cargo">Recibe · {{ $traslado->empresaDestino->nombre ?? '' }}</div>
    </div>
    <div class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">&nbsp;</div>
        <div class="firma-cargo">Autoriza el traslado</div>
    </div>
@endsection
