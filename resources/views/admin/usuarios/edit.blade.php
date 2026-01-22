@php
    $actor = auth()->user();
    $actorIsAdmin = $actor->hasRole('Administrador');
    $actorIsAnalista = $actor->hasRole('Analista');
    $actorIsUsuario = $actor->hasRole('Usuario');
@endphp

@php
    $readonly = $actorIsUsuario ? 'readonly' : null;
    $disabled = $actorIsUsuario ? 'disabled' : null;
@endphp

<x-app-layout title="Editar Usuario">

    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios', 'url' => '#'],
        ['label' => 'Usuarios', 'url' => route('admin.usuarios.index')],
        ['label' => 'Editar'],
    ]" />

    <x-form.errors />

    <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST" enctype="multipart/form-data"
        class="max-w-7xl mx-auto bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-8">

        @csrf
        @method('PUT')

        <h1 class="text-2xl font-bold text-white mb-6">Editar Usuario</h1>

        {{-- GRID GENERAL --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- DATOS PERSONALES --}}
            <div>
                <h2 class="text-xl text-white font-semibold mb-4">Datos personales</h2>

                {{-- Avatar --}}
                <div class="mb-4 flex items-center gap-4">
                    <img id="previewFoto"
                        src="{{ $usuario->foto ? asset('storage/' . $usuario->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($usuario->name) }}"
                        class="w-20 h-20 rounded-full object-cover border border-cerberus-steel">

                    <label class="cursor-pointer text-cerberus-light hover:text-cerberus-accent">
                        <span class="material-icons mr-1">upload</span> Cambiar foto
                        <input type="file" name="foto" id="fotoInput" class="hidden" accept="image/*">
                    </label>
                </div>

                <x-form.input name="name" label="Nombre" :value="$usuario->name" required :readonly="$readonly" />
                <x-form.input name="username" label="Nombre de usuario" :value="$usuario->name" required :readonly="$readonly" />
                <x-form.input name="cedula" label="Cédula" :value="$usuario->cedula" :readonly="$readonly" />
                <x-form.input name="telefono" label="Teléfono" :value="$usuario->telefono" />
                <x-form.input name="email" label="Email" type="email" :value="$usuario->email" required
                    :readonly="$readonly" />
                <x-form.input name="ficha" label="Ficha" :value="$usuario->ficha" :readonly="$readonly" />
            </div>

            {{-- DATOS LABORALES --}}
            <div>
                <h2 class="text-xl text-white font-semibold mb-4">Datos laborales</h2>

                <x-select name="empresa_id" label="Empresa" :options="$empresas" :selected="$usuario->empresa_id" required
                    :disabled="$disabled" />

                @if ($actorIsAdmin && $usuario->hasRole('Analista'))
                    @php
                        $empresasUsuario = $usuario->empresasAsignadas->pluck('id')->toArray();
                    @endphp

                    <x-form.checkbox-group name="empresa_ids" label="Empresas Asignadas (rotación)" :options="$empresas"
                        :selected="$empresasUsuario" />
                @endif


                <x-select name="departamento_id" label="Departamento" :options="$departamentos" :selected="$usuario->departamento_id"
                    :disabled="$disabled" />

                <x-select name="cargo_id" label="Cargo" :options="$cargos" :selected="$usuario->cargo_id" :disabled="$disabled" />

                <x-select name="jefe_id" label="Jefe" :options="$jefes" :selected="$usuario->jefe_id" :disabled="$disabled" />

                <x-select name="ubicacion_id" label="Ubicación" :options="$ubicaciones" :selected="$usuario->ubicacion_id"
                    :disabled="$disabled" />
            </div>
        </div>

        {{-- ACCESO --}}
        <div class="mt-10">
            <h2 class="text-xl text-white font-semibold mb-4">Acceso al sistema</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                @if ($actorIsAdmin)
                    <x-select name="rol_id" label="Rol" :options="$roles" :selected="$userRoles" required />
                    <x-select name="estado" label="Estado" :options="['Activo' => 'Activo', 'Inactivo' => 'Inactivo']" :selected="$usuario->estado" />
                @else
                    {{-- Analista / Usuario --}}
                    <input type="hidden" name="rol_id" value="{{ $userRoles }}">
                    <input type="hidden" name="estado" value="{{ $usuario->estado }}">

                    <x-form.input label="Rol" :value="$usuario->getRoleNames()->first()" readonly />
                    <x-form.input label="Estado" :value="$usuario->estado" readonly />
                @endif

                @if (!$actorIsUsuario)
                    <x-form.input name="password" type="password" label="Nueva contraseña (opcional)" />

                    <x-form.input name="password_confirmation" type="password" label="Confirmar contraseña" />
                @endif
            </div>
        </div>

        {{-- BOTONES --}}
        <div class="mt-8 flex justify-end gap-4">
            <a href="{{ route('admin.usuarios.index') }}"
                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                Cancelar
            </a>

            <button class="px-6 py-2 bg-cerberus-primary hover:bg-cerberus-hover text-white font-semibold rounded-lg">
                Guardar cambios
            </button>
        </div>
    </form>

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
@section('header', 'Editar Usuario')
@section('content')
<div class="card">
  <div class="card-body">
    <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
      @method('PUT')
      @include('admin.usuarios._form')
      <button class="btn btn-primary">Actualizar</button>
      <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</div>
@endsection --}}
