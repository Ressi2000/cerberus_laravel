@extends('planillas.layout')

@php
    $codigoDoc   = 'DC-ST-FO-11';
    $tituloDoc   = 'Formato de Traslado de Activos Tecnológicos';
    $empresaSede = $traslado->empresa->nombre ?? '—';
@endphp

@section('contenido')

<div class="doc-title">Formato de Traslado de Activos Tecnológicos</div>
<div class="doc-subtitle">N° {{ $traslado->numero }}</div>

{{-- ══ DATOS DEL TRASLADO ══════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Datos del Traslado</div>
    <div class="fields-grid">
        <table class="fields-table">
            <tr>
                <td>
                    <div class="field-label">Número de traslado</div>
                    <div class="field-value">{{ $traslado->numero }}</div>
                </td>
                <td>
                    <div class="field-label">Fecha del traslado</div>
                    <div class="field-value">{{ $traslado->fecha_traslado?->format('d/m/Y') ?? '—' }}</div>
                </td>
                <td>
                    <div class="field-label">Empresa</div>
                    <div class="field-value">{{ strtoupper($traslado->empresa?->nombre ?? '—') }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="field-label">Ubicación de origen</div>
                    <div class="field-value">{{ strtoupper($traslado->ubicacionOrigen?->nombre ?? '—') }}</div>
                </td>
                <td colspan="2">
                    <div class="field-label">Ubicación de destino</div>
                    <div class="field-value">{{ strtoupper($traslado->ubicacionDestino?->nombre ?? '—') }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ══ MOTIVO ══════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Motivo del Traslado</div>
    <div class="fields-grid">
        <p style="font-size: 8.5pt; color: #1a1a2e; line-height: 1.5;">{{ $traslado->motivo }}</p>
        @if ($traslado->observaciones)
            <div style="margin-top: 6pt;">
                <div class="field-label">Observaciones</div>
                <p style="font-size: 8pt; color: #374151; margin-top: 2pt;">{{ $traslado->observaciones }}</p>
            </div>
        @endif
    </div>
</div>

{{-- ══ RESPONSABLES ════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Responsables</div>
    <div class="fields-grid">
        <table class="fields-table">
            <tr>
                <td>
                    <div class="field-label">Realizado por</div>
                    <div class="field-value">{{ strtoupper($traslado->realizadoPor?->name ?? '—') }}</div>
                </td>
                <td>
                    <div class="field-label">Quien recibe</div>
                    <div class="field-value">{{ strtoupper($traslado->recibe?->name ?? '—') }}</div>
                </td>
                <td>
                    <div class="field-label">Quien autoriza</div>
                    <div class="field-value">{{ strtoupper($traslado->autoriza?->name ?? '—') }}</div>
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
    </div>
</div>

{{-- ══ EQUIPOS TRASLADADOS ══════════════════════════════════════════════════ --}}
<div class="equipos-section">
    <table class="data-table">
        <thead>
            <tr class="thead-banner">
                <td colspan="5">Equipos Trasladados — {{ $traslado->items->count() }} equipo(s)</td>
            </tr>
            <tr class="thead-cols">
                <th style="width:15%">Código interno</th>
                <th style="width:20%">Categoría</th>
                <th style="width:20%">Serial</th>
                <th style="width:25%">Hostname / Nombre</th>
                <th style="width:20%">Estado actual</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($traslado->items as $index => $item)
                @php
                    $equipo = $item->equipo;
                    $par    = ($index % 2 === 0);
                @endphp
                <tr class="{{ $par ? 'tr-par' : 'tr-impar' }}">
                    <td>
                        <span class="cod-interno">{{ $equipo?->codigo_interno ?? '—' }}</span>
                    </td>
                    <td>{{ $equipo?->categoria?->nombre ?? '—' }}</td>
                    <td>{{ $equipo?->serial ?? '—' }}</td>
                    <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                    <td>{{ $equipo?->estado?->nombre ?? '—' }}</td>
                </tr>

                {{-- Fila de atributos EAV --}}
                @if ($equipo && $equipo->atributosActuales->isNotEmpty())
                    <tr class="tr-eav">
                        <td colspan="5">
                            @include('planillas._eav', [
                                'atributos' => $equipo->atributosActuales,
                                'clase_titulo' => 'eav-titulo',
                                'clase_fila' => 'tr-eav',
                            ])
                        </td>
                    </tr>
                @endif
            @empty
                <tr class="tr-par">
                    <td colspan="5" style="text-align:center; color:#9CA3AF; padding: 10pt;">
                        Sin equipos registrados en este traslado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@section('firmas')
    <td class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($traslado->realizadoPor?->name ?? '—') }}</div>
        <div class="firma-cargo">Analista responsable</div>
    </td>
    <td class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($traslado->recibe?->name ?? '—') }}</div>
        <div class="firma-cargo">Recibe conforme</div>
    </td>
    <td class="firma-cell">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($traslado->autoriza?->name ?? '—') }}</div>
        <div class="firma-cargo">Autoriza el traslado</div>
    </td>
@endsection
