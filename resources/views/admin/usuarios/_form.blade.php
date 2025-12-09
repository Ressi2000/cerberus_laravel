@csrf
<div class="form-group">
    <label>Nombre</label>
    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $usuario->name ?? '') }}" required>
</div>
<div class="form-group">
    <label>Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $usuario->email ?? '') }}" required>
</div>
<div class="form-group">
    <label>Departamento</label>
    <select name="departamento_id" class="form-control">
        <option value="">--</option>
        @foreach($departamentos as $d)
            <option value="{{ $d->id }}" {{ (old('departamento_id', $usuario->departamento_id ?? '') == $d->id) ? 'selected' : '' }}>{{ $d->nombre }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>Cargo</label>
    <select name="cargo_id" class="form-control">
        <option value="">--</option>
        @foreach($cargos as $c)
            <option value="{{ $c->id }}" {{ (old('cargo_id', $usuario->cargo_id ?? '') == $c->id) ? 'selected' : '' }}>{{ $c->nombre }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>Roles</label>
    <select name="roles[]" class="form-control" multiple>
        @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ (in_array($role->name, old('roles', $userRoles ?? [])) ? 'selected' : '') }}>{{ $role->name }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>Contraseña</label>
    <input type="password" name="password" class="form-control" {{ isset($usuario) ? '' : 'required' }}>
</div>
<div class="form-group">
    <label>Confirmar contraseña</label>
    <input type="password" name="password_confirmation" class="form-control" {{ isset($usuario) ? '' : 'required' }}>
</div>
