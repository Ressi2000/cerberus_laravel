@extends('planillas.layout')

@php
    $esArea      = $asignacion->tipoReceptor() === 'area';
    $tituloDoc   = 'Formato de Asignación de Activos Tecnológicos';
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
                <div class="field-value">{{ strtoupper($receptor?->name ?? '—') }}</div>
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
                <div class="field-value">{{ strtoupper($receptor?->empresaNomina?->nombre ?? '—') }}</div>
            </td>
            <td>
                <div class="field-label">Sede de la asignación</div>
                <div class="field-value">{{ strtoupper($asignacion->empresa?->nombre ?? '—') }}</div>
            </td>
            <td>
                <div class="field-label">Correo</div>
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
    </table>
@else
    <div class="section-title-plain">Datos del Área Receptora</div>
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
                <div class="field-label">Sede de la asignación</div>
                <div class="field-value">{{ strtoupper($asignacion->empresa?->nombre ?? '—') }}</div>
            </td>
        </tr>
    </table>
@endif

@endsection
