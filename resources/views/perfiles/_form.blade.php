@php($p = $perfil ?? null)
@php($permisosDisponibles = $permisosDisponibles ?? [])
@csrf

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" id="nombre" name="nombre" class="form-control" value="{{ old('nombre', $p?->nombre) }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="slug" class="form-label">Identificador interno</label>
        <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug', $p?->slug) }}" required>
        <div class="form-text">Nombre corto sin espacios, por ejemplo: recursos-humanos.</div>
    </div>
</div>
<div class="mb-3">
    <label for="descripcion" class="form-label">Descripción</label>
    <input type="text" id="descripcion" name="descripcion" class="form-control" value="{{ old('descripcion', $p?->descripcion) }}">
</div>

<h5 class="mt-4">Permisos de módulos</h5>
<div class="row">
    <div class="col-md-6">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_asignar_rol" name="puede_asignar_rol" value="1" @checked(old('puede_asignar_rol', $p?->puede_asignar_rol ?? false))>
            <label for="puede_asignar_rol" class="form-check-label">Asignación de rol</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_gestionar_descansos" name="puede_gestionar_descansos" value="1" @checked(old('puede_gestionar_descansos', $p?->puede_gestionar_descansos ?? false))>
            <label for="puede_gestionar_descansos" class="form-check-label">Gestionar descansos</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_gestionar_vacaciones" name="puede_gestionar_vacaciones" value="1" @checked(old('puede_gestionar_vacaciones', $p?->puede_gestionar_vacaciones ?? false))>
            <label for="puede_gestionar_vacaciones" class="form-check-label">Gestionar vacaciones</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_gestionar_permisos" name="puede_gestionar_permisos" value="1" @checked(old('puede_gestionar_permisos', $p?->puede_gestionar_permisos ?? false))>
            <label for="puede_gestionar_permisos" class="form-check-label">Gestionar permisos</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_ver_visor" name="puede_ver_visor" value="1" @checked(old('puede_ver_visor', $p?->puede_ver_visor ?? false))>
            <label for="puede_ver_visor" class="form-check-label">Ver visor de cumplimiento</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_gestionar_incapacidades" name="puede_gestionar_incapacidades" value="1" @checked(old('puede_gestionar_incapacidades', $p?->puede_gestionar_incapacidades ?? false))>
            <label for="puede_gestionar_incapacidades" class="form-check-label">Gestionar incapacidades</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_ver_lista_asistencia" name="puede_ver_lista_asistencia" value="1" @checked(old('puede_ver_lista_asistencia', $p?->puede_ver_lista_asistencia ?? false))>
            <label for="puede_ver_lista_asistencia" class="form-check-label">Ver lista de asistencia</label>
        </div>
        <div class="form-check mb-2">
            @if(in_array('puede_gestionar_viaticos', $permisosDisponibles, true))
                <input class="form-check-input" type="checkbox" id="puede_gestionar_viaticos" name="puede_gestionar_viaticos" value="1" @checked(old('puede_gestionar_viaticos', $p?->puede_gestionar_viaticos ?? false))>
                <label for="puede_gestionar_viaticos" class="form-check-label">Gestionar viáticos</label>
            @else
                <span class="text-warning small"><i class="bi bi-exclamation-triangle"></i> Gestionar viáticos requiere ejecutar la migración pendiente.</span>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_aprobar" name="puede_aprobar" value="1" @checked(old('puede_aprobar', $p?->puede_aprobar ?? false))>
            <label for="puede_aprobar" class="form-check-label">Puede aprobar</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="puede_cargas_masivas" name="puede_cargas_masivas" value="1" @checked(old('puede_cargas_masivas', $p?->puede_cargas_masivas ?? false))>
            <label for="puede_cargas_masivas" class="form-check-label">Puede realizar cargas masivas</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="es_admin" name="es_admin" value="1" @checked(old('es_admin', $p?->es_admin ?? false))>
            <label for="es_admin" class="form-check-label text-danger">Es administrador (acceso total)</label>
        </div>
    </div>
</div>

<h5 class="mt-4">Secciones permitidas</h5>
<p class="text-muted small">Dejar sin marcar para permitir todas las secciones.</p>
<div class="row">
    @foreach($secciones as $s)
    <div class="col-md-4">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="seccion-{{ $loop->index }}" name="secciones[]" value="{{ $s }}"
                   @checked(in_array($s, old('secciones', $p?->secciones ?? [])))>
            <label for="seccion-{{ $loop->index }}" class="form-check-label">{{ $s }}</label>
        </div>
    </div>
    @endforeach
</div>

<button type="submit" class="btn btn-dark mt-3"><i class="bi bi-check-lg"></i> Guardar</button>
