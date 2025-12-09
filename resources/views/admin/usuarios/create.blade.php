<x-app-layout title="Crear Usuario" header="Gestión de Usuarios">
    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios', 'url' => '#'],
        ['label' => 'Usuarios', 'url' => route('admin.usuarios.index')],
        ['label' => 'Crear'],
    ]" />

    {{-- Título de la página
    <x-slot name="title">
        Formulario de creación de usuario
    </x-slot> --}}

    {{-- Estadísticas opcionales arriba --}}
    {{-- <x-stats-cards :items="[...]"/> --}}

    <form action="{{ route('admin.usuarios.store') }}" method="POST" enctype="multipart/form-data"
        class="max-w-7xl mx-auto bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-8">
        @csrf

        <h1 class="text-2xl font-bold text-white mb-6">Crear nuevo usuario</h1>

        {{-- GRID GENERAL --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- DATOS PERSONALES --}}
            <div>
                <h2 class="text-xl text-white font-semibold mb-4">Datos personales</h2>

                {{-- Avatar --}}
                <div class="mb-4 flex items-center gap-4">
                    <img id="previewFoto" 
                        src="https://ui-avatars.com/api/?name=Usuario&background=1B263B&color=A9D6E5&size=128"
                        class="w-20 h-20 rounded-full object-cover border border-cerberus-steel">
                    
                    <label class="cursor-pointer text-cerberus-light hover:text-cerberus-accent">
                        <span class="material-icons mr-1">upload</span> Subir foto
                        <input type="file" name="foto" id="fotoInput" class="hidden" accept="image/*">
                    </label>
                </div>

                {{-- Nombre --}}
                <x-form.input name="name" label="Nombre completo" required />

                {{-- Cédula --}}
                <x-form.input name="cedula" label="Cédula" />

                {{-- Teléfono --}}
                <x-form.input name="telefono" label="Teléfono" />

                {{-- Email --}}
                <x-form.input type="email" name="email" label="Email" required />

                {{-- Ficha --}}
                <x-form.input name="ficha" label="Ficha (código interno)" />

            </div>

            {{-- DATOS LABORALES --}}
            <div>
                <h2 class="text-xl text-white font-semibold mb-4">Datos laborales</h2>

                {{-- Empresa --}}
                <x-form.select name="empresa_id" label="Empresa" :options="$empresas" required />

                {{-- Departamento --}}
                <x-form.select name="departamento_id" label="Departamento" :options="$departamentos" />

                {{-- Cargo --}}
                <x-form.select name="cargo_id" label="Cargo" :options="$cargos" />

                {{-- Jefe --}}
                <x-form.select name="jefe_id" label="Jefe" :options="$jefes" />

                {{-- Ubicación --}}
                <x-form.select name="ubicacion_id" label="Ubicación principal" :options="$ubicaciones" />
            </div>
        </div>

        {{-- ACCESO DEL SISTEMA --}}
        <div class="mt-10">
            <h2 class="text-xl text-white font-semibold mb-4">Acceso al sistema</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- Rol --}}
                <x-form.select name="rol_id" label="Rol del sistema" :options="$roles" required />

                {{-- Estado --}}
                <x-form.select name="estado" label="Estado" :options="['Activo'=>'Activo','Inactivo'=>'Inactivo']" />

                {{-- Password --}}
                <x-form.input type="password" name="password" label="Contraseña" required />

                {{-- Confirmación --}}
                <x-form.input type="password" name="password_confirmation" label="Confirmar contraseña" required />
            </div>
        </div>

        {{-- BOTONES --}}
        <div class="mt-8 flex justify-end gap-4">
            <a href="{{ route('admin.usuarios.index') }}"
                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                Cancelar
            </a>

            <button
                class="px-6 py-2 bg-cerberus-primary hover:bg-cerberus-hover text-white font-semibold rounded-lg">
                Crear Usuario
            </button>
        </div>
    </form>


    {{-- JS para previsualizar foto --}}
    <script>
        document.getElementById('fotoInput')?.addEventListener('change', e => {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('previewFoto').src = URL.createObjectURL(file);
            }
        });
    </script>

</x-app-layout>


{{-- @extends('layouts.admin')
@section('header','Crear Usuario')
@section('content')
<div class="card">
  <div class="card-body">
    <form action="{{ route('admin.usuarios.store') }}" method="POST">
      @include('admin.usuarios._form')
      <button class="btn btn-primary">Guardar</button>
      <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</div>
@endsection --}}
