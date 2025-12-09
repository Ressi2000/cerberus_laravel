@extends('layouts.admin')

@section('title', 'Mi Perfil')

@section('content_header')
    <h1>Mi Perfil</h1>
@stop

@section('content')

<div class="row">
    <div class="col-md-6">

        {{-- Información del perfil --}}
        <div class="card card-primary card-outline mb-4">
            <div class="card-header">
                <h3 class="card-title">Información del perfil</h3>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')

                    <div class="form-group mb-3">
                        <label for="name">Nombre</label>
                        <input id="name" name="name" type="text" class="form-control" 
                               value="{{ old('name', $user->name) }}" required autofocus>
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="email">Correo electrónico</label>
                        <input id="email" name="email" type="email" class="form-control" 
                               value="{{ old('email', $user->email) }}" required>
                        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </form>
            </div>
        </div>

        {{-- Cambiar contraseña --}}
        <div class="card card-secondary card-outline mb-4">
            <div class="card-header">
                <h3 class="card-title">Actualizar contraseña</h3>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')

                    <div class="form-group mb-3">
                        <label for="current_password">Contraseña actual</label>
                        <input id="current_password" name="current_password" type="password" class="form-control">
                        @error('current_password') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="password">Nueva contraseña</label>
                        <input id="password" name="password" type="password" class="form-control">
                        @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="password_confirmation">Confirmar contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-secondary">Actualizar contraseña</button>
                </form>
            </div>
        </div>

        {{-- Eliminar cuenta --}}
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title">Eliminar cuenta</h3>
            </div>
            <div class="card-body">
                <p>Una vez eliminada, todos los datos serán borrados permanentemente.</p>

                <form method="post" action="{{ route('profile.destroy') }}" 
                      onsubmit="return confirm('¿Seguro que deseas eliminar tu cuenta? Esta acción no se puede deshacer.')">
                    @csrf
                    @method('delete')

                    <div class="form-group mb-3">
                        <label for="password_delete">Contraseña</label>
                        <input id="password_delete" name="password" type="password" class="form-control" placeholder="Introduce tu contraseña">
                        @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <button type="submit" class="btn btn-danger">Eliminar cuenta</button>
                </form>
            </div>
        </div>

    </div>
</div>

@stop
