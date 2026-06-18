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

{{-- ══ TABLA EQUIPOS DEVUELTOS ══════════════════════════════════════════════ --}}

<div class="equipos-section">
    <table class="data-table">
        <thead>
            <tr class="thead-banner devolucion">
                <td colspan="8">
                    Equipos Devueltos
                    &nbsp;·&nbsp;
                    {{ $itemsDevueltos->count() }} {{ $itemsDevueltos->count() === 1 ? 'equipo' : 'equipos' }}
                </td>
            </tr>
            <tr class="thead-cols">
                <th style="width:11%">Código</th>
                <th style="width:13%">Hostname</th>
                <th style="width:12%">Categoría</th>
                <th style="width:12%">Marca</th>
                <th style="width:12%">Modelo</th>
                <th style="width:13%">Serial</th>
                <th style="width:11%">F. Devolución</th>
                <th style="width:16%">Recibido por</th>
            </tr>
        </thead>
        <tbody>
        @if ($itemsDevueltos->isEmpty())
            <tr>
                <td colspan="8" style="text-align:center;color:#6B7280;font-style:italic;padding:12pt;">
                    No hay equipos devueltos registrados.
                </td>
            </tr>
        @else
            @foreach ($itemsDevueltos as $item)
                @php
                    $eq           = $item->equipo;
                    $esPeriférico = $item->equipo_padre_id !== null;
                    $atribs       = $eq?->atributosActuales ?? collect();
                    $marca        = $atribs->first(fn($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor ?? ($eq?->marca ?? '—');
                    $modelo       = $atribs->first(fn($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor ?? ($eq?->modelo ?? '—');
                    $eav          = $atribs->filter(fn($v) => ! in_array(strtolower($v->atributo?->nombre ?? ''), ['marca','modelo']));
                @endphp

                @if ($esPeriférico)
                    <tr class="tr-periferico periferico-group">
                        <td>
                            <span class="periferico-prefix">↳</span>
                            <span class="periferico-cod">{{ $eq?->codigo_interno ?? '—' }}</span>
                        </td>
                        <td>{{ $eq?->nombre_maquina ?? '—' }}</td>
                        <td>{{ strtoupper($eq?->categoria?->nombre ?? '—') }}</td>
                        <td>{{ $marca }}</td>
                        <td>{{ $modelo }}</td>
                        <td>{{ $eq?->serial ?? '—' }}</td>
                        <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            {{ $item->devueltoPor?->name ?? '—' }}
                            @if ($item->padre?->equipo)
                                <span class="cell-sub">De: {{ $item->padre->equipo->codigo_interno }}</span>
                            @endif
                        </td>
                    </tr>
                    @include('planillas._eav', ['atributos' => $eav, 'esPeriferico' => true, 'colspan' => 8])
                @else
                    <tr class="equipo-group tr-impar">
                        <td class="cod-interno">{{ $eq?->codigo_interno ?? '—' }}</td>
                        <td>{{ $eq?->nombre_maquina ?? '—' }}</td>
                        <td>{{ strtoupper($eq?->categoria?->nombre ?? '—') }}</td>
                        <td>{{ $marca }}</td>
                        <td>{{ $modelo }}</td>
                        <td>{{ $eq?->serial ?? '—' }}</td>
                        <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $item->devueltoPor?->name ?? '—' }}</td>
                    </tr>
                    @include('planillas._eav', ['atributos' => $eav, 'esPeriferico' => false, 'colspan' => 8])
                @endif
            @endforeach
        @endif
        </tbody>
    </table>
</div>

{{-- ══ PENDIENTES (si quedan) ══════════════════════════════════════════════ --}}

@if ($itemsPendientes->isNotEmpty())
    <div class="equipos-section">
        <table class="data-table">
            <thead>
                <tr class="thead-banner peligro">
                    <td colspan="5">
                        Equipos Pendientes de Devolución &nbsp;·&nbsp; {{ $itemsPendientes->count() }}
                    </td>
                </tr>
                <tr class="thead-cols">
                    <th style="width:16%">Código</th>
                    <th style="width:22%">Hostname</th>
                    <th style="width:20%">Categoría</th>
                    <th style="width:22%">Serial</th>
                    <th style="width:20%">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemsPendientes as $item)
                    <tr class="tr-pendiente">
                        <td class="cod-interno" style="color:#DC2626;">{{ $item->equipo?->codigo_interno ?? '—' }}</td>
                        <td>{{ $item->equipo?->nombre_maquina ?? '—' }}</td>
                        <td>{{ strtoupper($item->equipo?->categoria?->nombre ?? '—') }}</td>
                        <td>{{ $item->equipo?->serial ?? '—' }}</td>
                        <td><span class="badge badge-pendiente">Pendiente</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
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
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ $receptor?->jefe?->name ?? 'Supervisor' }}</div>
            <div class="firma-cargo">Supervisor / Testigo</div>
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