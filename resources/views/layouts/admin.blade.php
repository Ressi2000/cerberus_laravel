@extends('adminlte::page')

@section('title', config('adminlte.title', 'Cerberus'))

@section('content_header')
    <h1>@yield('header', 'Panel')</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
        </div>
    @endif
    
    @yield('content')
@stop
