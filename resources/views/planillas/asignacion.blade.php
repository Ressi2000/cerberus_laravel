@extends('planillas.layout')

@php
    /**
     * Planilla de Asignación (DC-ST-FO-08)
     * Soporta dos tipos de receptor:
     *   - Personal  → $asignacion->usuario_id  presente
     *   - Área común → $asignacion->area_empresa_id presente
     */
    $esArea      = $asignacion->tipoReceptor() === 'area';
    $codigoDoc   = 'DC-ST-FO-08';
    $empresaSede = $asignacion->empresa->nombre ?? '—';

    // Receptor personal
    $receptor    = $asignacion->usuario;

    // Receptor área
    $areaEmpresa  = $asignacion->areaEmpresa;
    $areaDpto     = $asignacion->areaDepartamento;
    $areaResp     = $asignacion->areaResponsable;

    // Items principales (periféricos van anidados en ->hijos)
    $items = $asignacion->items->whereNull('equipo_padre_id');
@endphp

@section('contenido')

{{-- ── TÍTULO ────────────────────────────────────────────────────────────── --}}
<div class="doc-title">
    Formato de Asignación de Activos Tecnológicos
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN RECEPTOR  (se adapta a usuario o área común)
══════════════════════════════════════════════════════════════════════════ --}}

@if (! $esArea)
    {{-- ──────────────── RECEPTOR PERSONAL ──────────────── --}}
    <div class="section">
        <div class="section-title">Datos del Receptor</div>
        <div class="fields-grid">

            <div style="padding: 6pt 6pt 4pt;">
                <span class="receptor-badge usuario">Asignación personal</span>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Ficha</div>
                    <div class="field-value">{{ $receptor?->ficha ?? '—' }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Nombre completo</div>
                    <div class="field-value">{{ strtoupper($receptor?->name ?? '—') }}</div>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Cédula de identidad</div>
                    <div class="field-value">{{ $receptor?->cedula ?? '—' }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Correo electrónico</div>
                    <div class="field-value">{{ $receptor?->email ?? '—' }}</div>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Empresa (nómina)</div>
                    <div class="field-value">{{ strtoupper($receptor?->empresaNomina?->nombre ?? '—') }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Sede de asignación</div>
                    <div class="field-value">{{ strtoupper($asignacion->empresa?->nombre ?? '—') }}</div>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Departamento</div>
                    <div class="field-value">{{ strtoupper($receptor?->departamento?->nombre ?? '—') }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Cargo</div>
                    <div class="field-value">{{ strtoupper($receptor?->cargo?->nombre ?? '—') }}</div>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Supervisor directo</div>
                    <div class="field-value">{{ strtoupper($receptor?->jefe?->name ?? '—') }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Cargo del supervisor</div>
                    <div class="field-value">{{ strtoupper($receptor?->jefe?->cargo?->nombre ?? '—') }}</div>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Fecha de entrega</div>
                    <div class="field-value">{{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Analista responsable</div>
                    <div class="field-value">{{ strtoupper($asignacion->analista?->name ?? '—') }}</div>
                </div>
            </div>

        </div>
    </div>

@else
    {{-- ──────────────── RECEPTOR ÁREA COMÚN ──────────────── --}}
    <div class="section">
        <div class="section-title area">Datos del Área Receptora</div>
        <div class="fields-grid">

            <div style="padding: 6pt 6pt 4pt;">
                <span class="receptor-badge area">Asignación a área común</span>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Empresa del área</div>
                    <div class="field-value">{{ strtoupper($areaEmpresa?->nombre ?? '—') }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Departamento / Área</div>
                    <div class="field-value">{{ strtoupper($areaDpto?->nombre ?? '—') }}</div>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Responsable del área</div>
                    <div class="field-value">{{ strtoupper($areaResp?->name ?? '—') }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Cargo del responsable</div>
                    <div class="field-value">{{ strtoupper($areaResp?->cargo?->nombre ?? '—') }}</div>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Sede de asignación</div>
                    <div class="field-value">{{ strtoupper($asignacion->empresa?->nombre ?? '—') }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Correo del responsable</div>
                    <div class="field-value">{{ $areaResp?->email ?? '—' }}</div>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-cell">
                    <div class="field-label">Fecha de entrega</div>
                    <div class="field-value">{{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Analista responsable</div>
                    <div class="field-value">{{ strtoupper($asignacion->analista?->name ?? '—') }}</div>
                </div>
            </div>

        </div>
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════
     TABLA DE EQUIPOS ENTREGADOS
══════════════════════════════════════════════════════════════════════════ --}}

<div class="section">
    <div class="section-title">
        Equipos y Periféricos Entregados
        ({{ $items->count() }} {{ $items->count() === 1 ? 'equipo principal' : 'equipos principales' }})
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:80pt">Código</th>
                <th style="width:90pt">Nombre / Hostname</th>
                <th style="width:70pt">Categoría</th>
                <th style="width:80pt">Marca · Modelo</th>
                <th style="width:70pt">Serial</th>
                <th style="width:55pt">Adquisición</th>
                <th style="width:55pt">Garantía hasta</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @php
                    $equipo     = $item->equipo;
                    $atributos  = $equipo?->atributosActuales ?? collect();
                    $marca      = $atributos->first(fn($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor
                                  ?? $equipo?->marca ?? null;
                    $modelo     = $atributos->first(fn($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor
                                  ?? $equipo?->modelo ?? null;
                    $eavExtra   = $atributos->filter(fn($v) =>
                        $v->atributo?->visible_en_tabla &&
                        ! in_array(strtolower($v->atributo->nombre ?? ''), ['marca','modelo'])
                    );
                @endphp

                {{-- Fila principal del equipo --}}
                <tr>
                    <td class="cod-interno">{{ $equipo?->codigo_interno ?? '—' }}</td>
                    <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                    <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                    <td>
                        @if($marca || $modelo)
                            {{ $marca ?? '—' }}
                            @if($modelo) <br><span style="font-size:7.5pt;color:#6B7280;">{{ $modelo }}</span> @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $equipo?->serial ?? '—' }}</td>
                    <td>{{ $equipo?->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        @if($equipo?->fecha_garantia_fin)
                            {{ $equipo->fecha_garantia_fin->format('d/m/Y') }}
                            @if($equipo->fecha_garantia_fin->isPast())
                                <br><span style="color:#DC2626;font-size:7pt;font-weight:bold;">VENCIDA</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                </tr>

                {{-- Fila de atributos EAV extra --}}
                @if ($eavExtra->isNotEmpty())
                    <tr class="eav-row">
                        <td colspan="7">
                            @foreach ($eavExtra as $av)
                                <span class="eav-pill">{{ $av->atributo->nombre }}</span>
                                {{ $av->valor }}
                                &nbsp;&nbsp;
                            @endforeach
                        </td>
                    </tr>
                @endif

                {{-- Periféricos vinculados --}}
                @foreach ($item->hijos as $hijo)
                    @php $heq = $hijo->equipo; @endphp
                    <tr class="periferico-row">
                        <td colspan="7">
                            <span class="periferico-prefix">↳</span>
                            <strong>{{ $heq?->codigo_interno ?? '—' }}</strong>
                            &nbsp;·&nbsp;
                            {{ strtoupper($heq?->categoria?->nombre ?? '—') }}
                            &nbsp;·&nbsp;
                            {{ $heq?->nombre_maquina ?? '—' }}
                            @if($heq?->serial) &nbsp;·&nbsp; S/N: {{ $heq->serial }} @endif
                        </td>
                    </tr>
                @endforeach

            @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#6B7280; font-style:italic; padding:14pt;">
                        No hay equipos registrados en esta asignación.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Observaciones --}}
@if ($asignacion->observaciones)
    <div class="obs-box">
        <div class="obs-label">Observaciones</div>
        {{ $asignacion->observaciones }}
    </div>
@endif

@endsection


@section('firmas')
<div class="firmas">

    @if (! $esArea)
        {{-- FIRMAS para asignación personal: Técnico · Trabajador · Supervisor --}}
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($asignacion->analista?->name ?? 'Analista') }}</div>
            <div class="firma-label">Técnico / Analista</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($receptor?->name ?? 'Receptor') }}</div>
            <div class="firma-label">Trabajador receptor</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($receptor?->jefe?->name ?? 'Supervisor') }}</div>
            <div class="firma-label">Supervisor / Jefe directo</div>
        </div>

    @else
        {{-- FIRMAS para área común: Técnico · Responsable del área --}}
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($asignacion->analista?->name ?? 'Analista') }}</div>
            <div class="firma-label">Técnico / Analista</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($areaResp?->name ?? 'Responsable') }}</div>
            <div class="firma-label">Responsable del área</div>
        </div>
        <div class="firma-cell">
            {{-- Tercera celda vacía para mantener proporciones --}}
        </div>
    @endif

</div>
@endsection