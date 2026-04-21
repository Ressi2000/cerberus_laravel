@extends('planillas.layout')

@php
    $esArea      = $asignacion->tipoReceptor() === 'area';
    $codigoDoc   = 'DC-ST-FO-08';
    $tituloDoc   = 'Formato de Asignación de Activos Tecnológicos';
    $empresaSede = $asignacion->empresa->nombre ?? '—';

    $receptor    = $asignacion->usuario;
    $areaEmpresa = $asignacion->areaEmpresa;
    $areaDpto    = $asignacion->areaDepartamento;
    $areaResp    = $asignacion->areaResponsable;

    $items = $asignacion->items->whereNull('equipo_padre_id')->values();
@endphp

@section('contenido')

<div class="doc-title">Formato de Asignación de Activos Tecnológicos</div>

{{-- ══ RECEPTOR ════════════════════════════════════════════════════════════ --}}

@if (! $esArea)
    <div class="section">
        <div class="section-title">Datos del Receptor</div>
        <div class="fields-grid">
            <div style="padding-bottom:5pt;">
                <span class="receptor-badge usuario">Asignación personal</span>
            </div>
            <table class="fields-table">
                <tr>
                    <td>
                        <div class="field-label">Ficha</div>
                        <div class="field-value">{{ $receptor?->ficha ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="field-label">Nombre completo</div>
                        <div class="field-value">{{ strtoupper($receptor?->name ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Cédula de identidad</div>
                        <div class="field-value">{{ $receptor?->cedula ?? '—' }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="field-label">Empresa (nómina)</div>
                        <div class="field-value">{{ strtoupper($receptor?->empresaNomina?->nombre ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Sede de asignación</div>
                        <div class="field-value">{{ strtoupper($asignacion->empresa?->nombre ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Correo electrónico</div>
                        <div class="field-value">{{ $receptor?->email ?? '—' }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="field-label">Departamento</div>
                        <div class="field-value">{{ strtoupper($receptor?->departamento?->nombre ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Cargo</div>
                        <div class="field-value">{{ strtoupper($receptor?->cargo?->nombre ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Supervisor directo</div>
                        <div class="field-value">{{ strtoupper($receptor?->jefe?->name ?? '—') }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="field-label">Cargo del supervisor</div>
                        <div class="field-value">{{ strtoupper($receptor?->jefe?->cargo?->nombre ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Fecha de entrega</div>
                        <div class="field-value">{{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="field-label">Analista responsable</div>
                        <div class="field-value">{{ strtoupper($asignacion->analista?->name ?? '—') }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
@else
    <div class="section">
        <div class="section-title area">Datos del Área Receptora</div>
        <div class="fields-grid">
            <div style="padding-bottom:5pt;">
                <span class="receptor-badge area">Asignación a área común</span>
            </div>
            <table class="fields-table">
                <tr>
                    <td>
                        <div class="field-label">Empresa del área</div>
                        <div class="field-value">{{ strtoupper($areaEmpresa?->nombre ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Departamento / Área</div>
                        <div class="field-value">{{ strtoupper($areaDpto?->nombre ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Responsable del área</div>
                        <div class="field-value">{{ strtoupper($areaResp?->name ?? '—') }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="field-label">Cargo del responsable</div>
                        <div class="field-value">{{ strtoupper($areaResp?->cargo?->nombre ?? '—') }}</div>
                    </td>
                    <td>
                        <div class="field-label">Correo del responsable</div>
                        <div class="field-value">{{ $areaResp?->email ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="field-label">Sede de asignación</div>
                        <div class="field-value">{{ strtoupper($asignacion->empresa?->nombre ?? '—') }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="field-label">Fecha de entrega</div>
                        <div class="field-value">{{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="field-label">Analista responsable</div>
                        <div class="field-value">{{ strtoupper($asignacion->analista?->name ?? '—') }}</div>
                    </td>
                    <td></td>
                </tr>
            </table>
        </div>
    </div>
@endif

{{-- ══ TABLA DE EQUIPOS ════════════════════════════════════════════════════ --}}

<div class="equipos-section">
    <table class="data-table">

        {{-- thead repetible: banner + columnas --}}
        <thead>
            <tr class="thead-banner">
                <td colspan="8">
                    Equipos y Periféricos Entregados
                    &nbsp;·&nbsp;
                    {{ $items->count() }} {{ $items->count() === 1 ? 'equipo principal' : 'equipos principales' }}
                </td>
            </tr>
            <tr class="thead-cols">
                <th style="width:10%">Código</th>
                <th style="width:14%">Hostname</th>
                <th style="width:12%">Categoría</th>
                <th style="width:12%">Marca</th>
                <th style="width:12%">Modelo</th>
                <th style="width:14%">Serial</th>
                <th style="width:13%">Adquisición</th>
                <th style="width:13%">Garantía</th>
            </tr>
        </thead>

        <tbody>
        @forelse ($items as $index => $item)
            @php
                $eq        = $item->equipo;
                $atribs    = $eq?->atributosActuales ?? collect();
                $marca     = $atribs->first(fn($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor ?? ($eq?->marca ?? '—');
                $modelo    = $atribs->first(fn($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor ?? ($eq?->modelo ?? '—');
                $eavPrinc  = $atribs->filter(fn($v) => ! in_array(strtolower($v->atributo?->nombre ?? ''), ['marca','modelo']));
                $rowClass  = $index % 2 === 0 ? 'tr-impar' : 'tr-par';
            @endphp

            {{-- Equipo principal --}}
            <tr class="equipo-group {{ $rowClass }}">
                <td class="cod-interno">{{ $eq?->codigo_interno ?? '—' }}</td>
                <td>{{ $eq?->nombre_maquina ?? '—' }}</td>
                <td>{{ strtoupper($eq?->categoria?->nombre ?? '—') }}</td>
                <td>{{ $marca }}</td>
                <td>{{ $modelo }}</td>
                <td>{{ $eq?->serial ?? '—' }}</td>
                <td>{{ $eq?->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</td>
                <td>
                    @if ($eq?->fecha_garantia_fin)
                        {{ $eq->fecha_garantia_fin->format('d/m/Y') }}
                        @if ($eq->fecha_garantia_fin->isPast())
                            <span class="garantia-vencida">VENCIDA</span>
                        @endif
                    @else —
                    @endif
                </td>
            </tr>

            {{-- EAV principal --}}
            @include('planillas._eav', ['atributos' => $eavPrinc, 'esPeriferico' => false, 'colspan' => 8])

            {{-- Periféricos --}}
            @foreach ($item->hijos as $hijo)
                @php
                    $heq     = $hijo->equipo;
                    $hAtribs = $heq?->atributosActuales ?? collect();
                    $hMarca  = $hAtribs->first(fn($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor ?? ($heq?->marca ?? '—');
                    $hModelo = $hAtribs->first(fn($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor ?? ($heq?->modelo ?? '—');
                    $hEav    = $hAtribs->filter(fn($v) => ! in_array(strtolower($v->atributo?->nombre ?? ''), ['marca','modelo']));
                @endphp

                <tr class="tr-periferico periferico-group">
                    <td>
                        <span class="periferico-prefix">↳</span>
                        <span class="periferico-cod">{{ $heq?->codigo_interno ?? '—' }}</span>
                    </td>
                    <td>{{ $heq?->nombre_maquina ?? '—' }}</td>
                    <td>{{ strtoupper($heq?->categoria?->nombre ?? '—') }}</td>
                    <td>{{ $hMarca }}</td>
                    <td>{{ $hModelo }}</td>
                    <td>{{ $heq?->serial ?? '—' }}</td>
                    <td>{{ $heq?->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        @if ($heq?->fecha_garantia_fin)
                            {{ $heq->fecha_garantia_fin->format('d/m/Y') }}
                            @if ($heq->fecha_garantia_fin->isPast())
                                <span class="garantia-vencida">VENCIDA</span>
                            @endif
                        @else —
                        @endif
                    </td>
                </tr>

                @include('planillas._eav', ['atributos' => $hEav, 'esPeriferico' => true, 'colspan' => 8])

            @endforeach

        @empty
            <tr>
                <td colspan="8" style="text-align:center;color:#6B7280;font-style:italic;padding:14pt;">
                    No hay equipos registrados en esta asignación.
                </td>
            </tr>
        @endforelse
        </tbody>

    </table>
</div>

@if ($asignacion->observaciones)
    <div class="obs-box">
        <div class="obs-label">Observaciones</div>
        {{ $asignacion->observaciones }}
    </div>
@endif

@endsection


@section('firmas')
    @if (! $esArea)
        <td class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($asignacion->analista?->name ?? 'Analista') }}</div>
            <div class="firma-cargo">Técnico / Analista</div>
        </td>
        <td class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($receptor?->name ?? 'Receptor') }}</div>
            <div class="firma-cargo">Trabajador receptor</div>
        </td>
        <td class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($receptor?->jefe?->name ?? 'Supervisor') }}</div>
            <div class="firma-cargo">Supervisor / Jefe directo</div>
        </td>
    @else
        <td class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($asignacion->analista?->name ?? 'Analista') }}</div>
            <div class="firma-cargo">Técnico / Analista</div>
        </td>
        <td class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($areaResp?->name ?? 'Responsable') }}</div>
            <div class="firma-cargo">Responsable del área</div>
        </td>
        <td class="firma-cell"></td>
    @endif
@endsection