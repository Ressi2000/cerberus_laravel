@extends('planillas.layout')

@php
    $empresaSede = $asignacion->empresa->nombre ?? '—';
    $ocultarMeta = true; // Paso 1 del rediseño: solo encabezado, el resto se reconstruirá luego.
@endphp

@section('contenido')

{{-- Pendiente: el resto del contenido se rediseñará en los siguientes pasos. --}}

@endsection
