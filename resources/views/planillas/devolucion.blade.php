@extends('planillas.layout')

@php
    /**
     * Planilla de Devolución (DC-ST-FO-10)
     * Soporta dos tipos de receptor:
     *   - Personal  → $asignacion->usuario_id  presente
     *   - Área común → $asignacion->area_empresa_id presente
     */
    $esArea      = $asignacion->tipoReceptor() === 'area';
    $codigoDoc   = 'DC-ST-FO-10';
    $empresaSede = $asignacion->empresa->nombre ?? '—';

    // Receptor personal
    $receptor    = $asignacion->usuario;

    // Receptor área
    $areaEmpresa = $asignacion->areaEmpresa;
    $areaDpto    = $asignacion->areaDepartamento;
    $areaResp    = $asignacion->areaResponsable;

    // Items devueltos — SIN whereNull: incluye periféricos devueltos individualmente.
    // Ya vienen ordenados del servicio: principal primero, sus hijos justo después.
    $itemsDevueltos = $asignacion->itemsDevueltos;

    // Items activos (pendientes) — SIN whereNull: periféricos promovidos a principales
    // también son pendientes si aún no fueron devueltos.
    $itemsPendientes = $asignacion->items->filter(fn ($i) => ! $i->devuelto);
@endphp

@section('contenido')

{{-- ── TÍTULO ────────────────────────────────────────────────────────────── --}}
<div class="doc-title">
    Formato de Devolución de Activos Tecnológicos
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECCIÓN RECEPTOR  (adapta a usuario o área común)
══════════════════════════════════════════════════════════════════════════ --}}

@if (! $esArea)
    {{-- ──────────────── RECEPTOR PERSONAL ──────────────── --}}
    <div class="section">
        <div class="section-title devolucion">Datos del Usuario</div>
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
                    <div class="field-label">Sede</div>
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
                    <div class="field-label">Fecha de devolución</div>
                    <div class="field-value">{{ $fecha }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Analista que recibe</div>
                    <div class="field-value">{{ strtoupper($asignacion->analista?->name ?? '—') }}</div>
                </div>
            </div>

        </div>
    </div>

@else
    {{-- ──────────────── RECEPTOR ÁREA COMÚN ──────────────── --}}
    <div class="section">
        <div class="section-title area">Datos del Área</div>
        <div class="fields-grid">

            <div style="padding: 6pt 6pt 4pt;">
                <span class="receptor-badge area">Área común</span>
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
                    <div class="field-label">Fecha de devolución</div>
                    <div class="field-value">{{ $fecha }}</div>
                </div>
                <div class="field-cell">
                    <div class="field-label">Analista que recibe</div>
                    <div class="field-value">{{ strtoupper($asignacion->analista?->name ?? '—') }}</div>
                </div>
            </div>

        </div>
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════
     TABLA DE EQUIPOS DEVUELTOS
══════════════════════════════════════════════════════════════════════════ --}}

<div class="section">
    <div class="section-title devolucion">
        Equipos Devueltos
        ({{ $itemsDevueltos->count() }} {{ $itemsDevueltos->count() === 1 ? 'equipo' : 'equipos' }})
    </div>

    @if ($itemsDevueltos->isEmpty())
        <div style="padding: 14pt 10pt; text-align:center; color:#6B7280; font-style:italic; font-size:8.5pt;">
            No hay equipos devueltos registrados en esta asignación.
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:80pt">Código</th>
                    <th style="width:90pt">Nombre / Hostname</th>
                    <th style="width:70pt">Categoría</th>
                    <th style="width:80pt">Marca · Modelo</th>
                    <th style="width:70pt">Serial</th>
                    <th style="width:60pt">Fecha devolución</th>
                    <th style="width:65pt">Recibido por</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemsDevueltos as $item)
                    @php
                        $equipo      = $item->equipo;
                        $esPeriférico = $item->equipo_padre_id !== null;
                        $atributos   = $equipo?->atributosActuales ?? collect();
                        $marca       = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'marca')?->valor ?? null;
                        $modelo      = $atributos->first(fn ($v) => strtolower($v->atributo?->nombre ?? '') === 'modelo')?->valor ?? null;
                        $eavExtra    = $atributos->filter(fn ($v) =>
                            $v->atributo?->visible_en_tabla &&
                            ! in_array(strtolower($v->atributo->nombre ?? ''), ['marca', 'modelo'])
                        );
                    @endphp

                    @if ($esPeriférico)
                        {{-- ── Periférico devuelto individualmente ── --}}
                        <tr class="periferico-row">
                            <td>
                                <span class="periferico-prefix">↳</span>
                                <strong>{{ $equipo?->codigo_interno ?? '—' }}</strong>
                            </td>
                            <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                            <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                            <td>
                                @if ($marca || $modelo)
                                    {{ $marca ?? '—' }}
                                    @if ($modelo) <br><span style="font-size:7.5pt;color:#92400E;">{{ $modelo }}</span> @endif
                                @else —
                                @endif
                            </td>
                            <td>{{ $equipo?->serial ?? '—' }}</td>
                            <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $item->devueltoPor?->name ?? '—' }}</td>
                        </tr>

                        {{-- Nota: de qué equipo era periférico --}}
                        @if ($item->padre?->equipo)
                            <tr class="eav-row">
                                <td colspan="7">
                                    <span class="eav-pill">Periférico de</span>
                                    {{ $item->padre->equipo->codigo_interno }}
                                    · {{ $item->padre->equipo->categoria?->nombre ?? '—' }}
                                </td>
                            </tr>
                        @endif

                    @else
                        {{-- ── Equipo principal devuelto ── --}}
                        <tr>
                            <td class="cod-interno">{{ $equipo?->codigo_interno ?? '—' }}</td>
                            <td>{{ $equipo?->nombre_maquina ?? '—' }}</td>
                            <td>{{ strtoupper($equipo?->categoria?->nombre ?? '—') }}</td>
                            <td>
                                @if ($marca || $modelo)
                                    {{ $marca ?? '—' }}
                                    @if ($modelo) <br><span style="font-size:7.5pt;color:#6B7280;">{{ $modelo }}</span> @endif
                                @else —
                                @endif
                            </td>
                            <td>{{ $equipo?->serial ?? '—' }}</td>
                            <td>{{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $item->devueltoPor?->name ?? '—' }}</td>
                        </tr>

                        {{-- EAV extra --}}
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
                    @endif

                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     EQUIPOS PENDIENTES DE DEVOLUCIÓN (si quedan)
══════════════════════════════════════════════════════════════════════════ --}}

@if ($itemsPendientes->isNotEmpty())
    <div class="section">
        <div class="section-title" style="background:#7F1D1D;">
            Equipos Pendientes de Devolución
            ({{ $itemsPendientes->count() }})
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre / Hostname</th>
                    <th>Categoría</th>
                    <th>Serial</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemsPendientes as $item)
                    <tr class="pendiente">
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
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($asignacion->analista?->name ?? 'Analista') }}</div>
            <div class="firma-label">Técnico que recibe</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($receptor?->name ?? 'Trabajador') }}</div>
            <div class="firma-label">Trabajador que entrega</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($receptor?->jefe?->name ?? 'Supervisor') }}</div>
            <div class="firma-label">Supervisor / Testigo</div>
        </div>
    @else
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($asignacion->analista?->name ?? 'Analista') }}</div>
            <div class="firma-label">Técnico que recibe</div>
        </div>
        <div class="firma-cell">
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{{ strtoupper($areaResp?->name ?? 'Responsable') }}</div>
            <div class="firma-label">Responsable del área</div>
        </div>
        <div class="firma-cell"></div>
    @endif

</div>
@endsection