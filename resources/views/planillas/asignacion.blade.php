@extends('planillas.layout')

@php
    $esArea      = $asignacion->tipoReceptor() === 'area';
    $tituloDoc   = 'Formato de Asignación de Activos Tecnológicos';
    $codigoDoc   = 'DC-ST-FO-08';
    $empresaSede = $asignacion->empresa->nombre ?? '—';
    $ocultarMeta = true; // El meta genérico del layout se reemplaza por el de abajo.

    $receptor    = $asignacion->usuario;
    $areaEmpresa = $asignacion->areaEmpresa;
    $areaDpto    = $asignacion->areaDepartamento;
    $areaResp    = $asignacion->areaResponsable;
@endphp

@section('contenido')

<div class="doc-title">{{ $tituloDoc }}</div>
<div class="doc-divider"></div>

<table class="doc-meta-table">
    <tr>
        <td class="doc-meta-left">Uso: Actividades Inherentes al Cargo</td>
        <td class="doc-meta-right">
            Fecha de Entrega: <strong>{{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}</strong>
        </td>
    </tr>
</table>

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
                <div class="field-label">Cédula</div>
                <div class="field-value">{{ $receptor?->cedula ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="field-label">Empresa (nómina)</div>
                <div class="field-value">{{ $receptor?->empresaNomina?->nombre ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Sede de la asignación</div>
                <div class="field-value">{{ $asignacion->empresa?->nombre ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Correo</div>
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
                <div class="field-label">Supervisor directo</div>
                <div class="field-value">{{ $receptor?->jefe?->name ?? '—' }}</div>
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
                <div class="field-label">Correo del responsable</div>
                <div class="field-value">{{ $areaResp?->email ?? '—' }}</div>
            </td>
            <td>
                <div class="field-label">Sede de la asignación</div>
                <div class="field-value">{{ strtoupper($asignacion->empresa?->nombre ?? '—') }}</div>
            </td>
        </tr>
    </table>
@endif

<div class="section-title-plain">Datos del Equipo</div>

@forelse ($asignacion->items as $item)
    @php
        $equipo    = $item->equipo;
        $atributos = $equipo?->atributosParaReporte() ?? collect();
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
    <p class="field-value">Sin equipos asignados.</p>
@endforelse

@php
    $perifericos = $asignacion->items->flatMap(
        fn ($item) => $item->hijos->each(fn ($hijo) => $hijo->setRelation('padre', $item))
    );
@endphp

@if ($perifericos->isNotEmpty())
    <div class="section-title-plain">Datos de Periféricos</div>

    @foreach ($perifericos as $periferico)
        @php
            $equipoPer    = $periferico->equipo;
            $equipoPadre  = $periferico->padre?->equipo;
            $atributosPer = $equipoPer?->atributosParaReporte() ?? collect();
        @endphp

        <table class="fields-table" style="margin-bottom: 6pt;">
            <tr>
                <td>
                    <div class="field-label">Código interno</div>
                    <div class="field-value valor-codigo">{{ $equipoPer?->codigo_interno ?? '—' }}</div>
                </td>
                <td>
                    <div class="field-label">Categoría</div>
                    <div class="field-value">{{ strtoupper($equipoPer?->categoria?->nombre ?? '—') }}</div>
                </td>
                <td>
                    <div class="field-label">Pertenece a</div>
                    <div class="field-value valor-referencia">» {{ $equipoPadre?->codigo_interno ?? '—' }}</div>
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

@endsection

@section('firmas')
    <div class="firma-cell" style="width: 50%;">
        <div class="firma-espacio"></div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">{{ strtoupper($asignacion->analista?->name ?? '—') }}</div>
        <div class="firma-cargo">Técnico / Analista</div>
    </div>

    <div class="firma-cell" style="width: 50%;">
        <div class="firma-espacio">
            @if ($firmaReceptor = $asignacion->firmas->firstWhere('rol', 'receptor'))
                @if ($firmaReceptor->estaFirmada())
                    <img src="{{ $firmaReceptor->imagen }}" style="max-height: 30pt;">
                @endif
            @endif
        </div>
        <div class="firma-linea"></div>
        <div class="firma-nombre">
            {{ strtoupper(($esArea ? $areaResp?->name : $receptor?->name) ?? '—') }}
        </div>
        <div class="firma-cargo">{{ $esArea ? 'Responsable del área receptora' : 'Trabajador receptor' }}</div>
    </div>
@endsection
