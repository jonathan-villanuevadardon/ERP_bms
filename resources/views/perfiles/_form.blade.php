@php($p = $perfil ?? null)
@csrf

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $p?->nombre) }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug', $p?->slug) }}" required>
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Descripción</label>
    <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion', $p?->descripcion) }}">
</div>

<h5 class="mt-4">Permisos de módulos</h5>
<div class="row">
    <div class="col-md-6">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="puede_asignar_rol" value="1" @checked(old('puede_asignar_rol', $p?->puede_asignar_rol ?? false))>
            <label class="form-check-label">Asignación de rol</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="puede_gestionar_descansos" value="1" @checked(old('puede_gestionar_descansos', $p?->puede_gestionar_descansos ?? false))>
            <label class="form-check-label">Gestionar descansos</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="puede_gestionar_vacaciones" value="1" @checked(old('puede_gestionar_vacaciones', $p?->puede_gestionar_vacaciones ?? false))>
            <label class="form-check-label">Gestionar vacaciones</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="puede_gestionar_permisos" value="1" @checked(old('puede_gestionar_permisos', $p?->puede_gestionar_permisos ?? false))>
            <label class="form-check-label">Gestionar permisos</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="puede_ver_visor" value="1" @checked(old('puede_ver_visor', $p?->puede_ver_visor ?? false))>
            <label class="form-check-label">Ver visor de cumplimiento</label>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="puede_aprobar" value="1" @checked(old('puede_aprobar', $p?->puede_aprobar ?? false))>
            <label class="form-check-label">Puede aprobar</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="es_admin" value="1" @checked(old('es_admin', $p?->es_admin ?? false))>
            <label class="form-check-label text-danger">Es administrador (acceso total)</label>
        </div>
    </div>
</div>

<h5 class="mt-4">Secciones permitidas</h5>
<p class="text-muted small">Dejar sin marcar para permitir todas las secciones.</p>
<div class="row">
    @foreach($secciones as $s)
    <div class="col-md-4">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="secciones[]" value="{{ $s }}"
                   @checked(in_array($s, old('secciones', $p?->secciones ?? [])))>
            <label class="form-check-label">{{ $s }}</label>
        </div>
    </div>
    @endforeach
</div>

<button type="submit" class="btn btn-dark mt-3"><i class="bi bi-check-lg"></i> Guardar</button>