@php($u = $usuario ?? null)
@csrf
@if($u) @method('PUT') @endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Nombre</label>
        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $u?->name) }}" maxlength="100" autocomplete="name" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $u?->email) }}" maxlength="100" autocomplete="email" required>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="clave_empleado" class="form-label">N° Empleado (clave, opcional)</label>
        <input type="number" id="clave_empleado" name="clave_empleado" class="form-control" value="{{ old('clave_empleado', $u?->clave_empleado) }}" min="1" max="2147483647" step="1" inputmode="numeric">
        <div class="form-text">Opcional. Captura únicamente números enteros, sin espacios ni guiones.</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="perfil_id" class="form-label">Perfil</label>
        <select id="perfil_id" name="perfil_id" class="form-select" required>
            <option value="">Seleccione perfil</option>
            @foreach($perfiles as $p)
                <option value="{{ $p->id }}" @selected(old('perfil_id', $u?->perfil_id) == $p->id)>{{ $p->nombre }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="password" class="form-label">Contraseña {{ $u ? '(dejar vacío para no cambiar)' : '' }}</label>
        <input type="password" id="password" name="password" class="form-control" minlength="6" autocomplete="new-password" {{ $u ? '' : 'required' }}>
    </div>
    <div class="col-md-6 mb-3">
        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" minlength="6" autocomplete="new-password" {{ $u ? '' : 'required' }}>
    </div>
</div>

@if($u)
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" @checked(old('activo', $u->activo ?? true))>
    <label class="form-check-label" for="activo">Activo</label>
</div>
@endif

<button type="submit" class="btn btn-dark" id="guardar-usuario"><i class="bi bi-check-lg"></i> <span>Guardar</span></button>

@push('scripts')
<script>
const resetUserSubmit = () => {
    const button = document.getElementById('guardar-usuario');
    if (!button) return;
    button.disabled = false;
    button.querySelector('span').textContent = 'Guardar';
};
document.getElementById('usuario-form')?.addEventListener('submit', function () {
    const button = document.getElementById('guardar-usuario');
    if (!button || button.disabled) return;
    button.disabled = true;
    button.querySelector('span').textContent = 'Guardando...';
});
window.addEventListener('pageshow', resetUserSubmit);
</script>
@endpush
