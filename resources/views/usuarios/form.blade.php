@php($u = $usuario ?? null)
@csrf
@if($u) @method('PUT') @endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $u?->name) }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $u?->email) }}" required>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">N° Empleado (clave, opcional)</label>
        <input type="number" name="clave_empleado" class="form-control" value="{{ old('clave_empleado', $u?->clave_empleado) }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Perfil</label>
        <select name="perfil_id" class="form-select" required>
            <option value="">Seleccione perfil</option>
            @foreach($perfiles as $p)
                <option value="{{ $p->id }}" @selected(old('perfil_id', $u?->perfil_id) == $p->id)>{{ $p->nombre }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Contraseña {{ $u ? '(dejar vacío para no cambiar)' : '' }}</label>
        <input type="password" name="password" class="form-control" {{ $u ? '' : 'required' }}>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Confirmar contraseña</label>
        <input type="password" name="password_confirmation" class="form-control" {{ $u ? '' : 'required' }}>
    </div>
</div>

@if($u)
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" @checked(old('activo', $u->activo ?? true))>
    <label class="form-check-label" for="activo">Activo</label>
</div>
@endif

<button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Guardar</button>