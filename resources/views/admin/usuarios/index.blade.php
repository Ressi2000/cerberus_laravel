<x-app-layout title="Usuarios" header="Gestión de Usuarios">

    {{-- BREADCRUMB --}}
    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios', 'url' => '#'],
        ['label' => 'Usuarios', 'url' => route('admin.usuarios.index')],
    ]" />

    {{-- STAT CARDS --}}
    <x-stats-cards :items="[
        ['title' => 'Total Usuarios', 'value' => $usuarios->count(), 'icon' => 'group'],
        ['title' => 'Activos', 'value' => $usuariosActivos ?? 0, 'icon' => 'check_circle'],
        ['title' => 'Inactivos', 'value' => $usuariosInactivos ?? 0, 'icon' => 'cancel'],
        ['title' => 'Admins', 'value' => $admins ?? 0, 'icon' => 'admin_panel_settings'],
    ]" />
    
    @livewire('admin.usuarios-table')

    {{-- TITLE + BUTTON + TOP FILTERS + SEARCH --}}
    {{-- <x-crud-header title="Usuarios" subtitle="Gestión de usuarios del sistema" buttonLabel="Crear usuario"
        :buttonUrl="route('admin.usuarios.create')">
        <x-slot name="filters">

            <div x-data="userFilters"
                class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4 mb-6">

                <div class="mb-3" x-show="activeFiltersCount > 0">
                    <span class="px-3 py-1 text-xs rounded-md bg-cerberus-primary/60 text-cerberus-white">
                        Filtros activos: <span x-text="activeFiltersCount"></span>
                    </span>
                </div> --}}

                {{-- PRIMERA FILA: BUSCADOR + SELECTS + ACCIONES --}}
                {{-- <div class="flex flex-wrap items-center gap-4"> --}}

                    {{-- SEARCH --}}
                    {{-- <div class="flex items-center flex-grow min-w-[200px]">
                        <div class="relative w-full">
                            <input type="text" x-model="search" placeholder="Buscar usuarios..."
                                class="w-full bg-cerberus-dark border border-cerberus-steel rounded-lg px-4 py-2 text-white">
                            <span class="material-icons absolute right-3 top-2.5 text-gray-400">search</span>
                        </div>
                    </div> --}}

                    {{-- SELECTS PRIMARIOS --}}
                  {{--  <select x-model="rol_id"
                        class="bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2">
                        <option value="">Rol</option>
                        @foreach ($roles as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <select x-model="empresa_id"
                        class="bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2">
                        <option value="">Empresa</option>
                        @foreach ($empresas as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                     <select x-model="departamento_id"
                        class="bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2">
                        <option value="">Departamento</option>
                        @foreach ($departamentos as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <select x-model="cargo_id"
                        class="bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2">
                        <option value="">Cargo</option>
                        @foreach ($cargos as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <select x-model="ubicacion_id"
                        class="bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2">
                        <option value="">Ubicación</option>
                        @foreach ($ubicaciones as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select> --}}

                    {{-- ACTION BUTTON (VACÍO PERO SE MANTIENE) --}}
                    {{-- <div class="ml-auto">
                        <button
                            class="bg-cerberus-dark border border-cerberus-steel hover:bg-cerberus-steel text-white px-4 py-2 rounded-lg flex items-center gap-2">
                            Acciones
                            <span class="material-icons text-sm">expand_more</span>
                        </button>
                    </div>
                </div> --}}

                {{-- SEGUNDA FILA: SECUNDARIOS + RESET --}}
                {{-- <div class="flex items-center gap-4 mt-4 text-sm text-cerberus-light">

                    <span class="text-white">Mostrar:</span>

                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="estado" value="" x-model="estado">
                        <span>Todos</span>
                    </label>

                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="estado" value="Activo" x-model="estado">
                        <span>Activos</span>
                    </label>

                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="estado" value="Inactivo" x-model="estado">
                        <span>Inactivos</span>
                    </label> --}}

                    {{-- RESET BUTTON --}}
                    {{-- <button @click="resetFilters"
                        class="ml-auto bg-red-600/20 border border-red-700 text-red-300 px-3 py-2 rounded-lg hover:bg-red-700/40">
                        Limpiar filtros
                    </button>

                </div>

            </div>

        </x-slot>

    </x-crud-header> --}}

    {{-- TABLE --}}
    {{-- <x-crud-table :headers="['Nombre', 'Username', 'Email', 'Rol', 'Ficha', 'Estado', 'Acciones']" :paginated="$usuarios" export>

        @foreach ($usuarios as $u)
            <tr class="hover:bg-cerberus-darkest">

                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ $u->profile_photo_url ?? asset('images/default-avatar.png') }}"
                            alt="{{ $u->name }}" class="w-10 h-10 rounded-full object-cover">
                        <div class="text-white font-medium">{{ $u->name }}</div>
                    </div>
                </td>
                <td class="px-4 py-3 text-white">{{ $u->username }}</td>
                <td class="px-4 py-3 text-cerberus-light">{{ $u->email }}</td>
                <td class="px-4 py-3">
                    @foreach ($u->roles as $r)
                        <span class="px-2 py-1 text-xs rounded-md bg-blue-800 text-blue-300 mr-1">
                            {{ $r->name }}
                        </span>
                    @endforeach
                </td>
                <td class="px-4 py-3">{{ $u->ficha }}</td>
                <td class="px-4 py-3">
                    @if ($u->estado === 'Activo')
                        <span
                            class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1 text-xs font-medium text-green-400 inset-ring inset-ring-green-500/20">Activo</span>
                    @else
                        <span
                            class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-400 inset-ring inset-ring-red-400/20">Inactivo</span>
                    @endif
                </td>

                <td class="px-6 py-4 text-center">
                    <x-table-actions row-id="user-{{ $u->id }}" :viewModalId="'viewUser-' . $u->id" :editUrl="route('admin.usuarios.edit', $u)"
                        deleteModalId="deleteUser-{{ $u->id }}" />

                    <x-view-modal id="viewUser-{{ $u->id }}" title="Detalle del Usuario" :data="$u" />

                    <x-delete-modal id="deleteUser-{{ $u->id }}" title="Eliminar Usuario" :message="'¿Seguro que deseas eliminar a ' . $u->name . '?'"
                        :action="route('admin.usuarios.destroy', $u)" />
                </td>
            </tr>
        @endforeach

    </x-crud-table> --}}


</x-app-layout>



{{-- @extends('layouts.admin')
@section('header', 'Usuarios')
@section('content')
<div class="card">
  <div class="card-header">
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">Crear usuario</a>
  </div>
  <div class="card-body">
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <table class="table table-striped">
      <thead>
        <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Roles</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        @foreach ($usuarios as $u)
          <tr>
            <td>{{ $u->id }}</td>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->roles->pluck('name')->join(', ') }}</td>
            <td>
              <a href="{{ route('admin.usuarios.edit', $u) }}" class="btn btn-sm btn-warning">Editar</a>
              <form action="{{ route('admin.usuarios.destroy', $u) }}" method="POST" style="display:inline-block">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger" onclick="return confirm('Eliminar usuario?')">Eliminar</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    {{ $usuarios->links() }}
  </div>
</div>
@endsection --}}
