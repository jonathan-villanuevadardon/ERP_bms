@extends('layouts.app')

@section('title', 'Asignar Rol Individual')

@section('content')
<div class="mb-4">
    <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Asignar rol de manera individual</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('roles.create') }}" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="termino" class="form-control" placeholder="Buscar por nombre, clave o cargo" value="{{ request('termino') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-dark w-100">Buscar</button>
            </div>
        </form>
    </div>
</div>

@if(!empty($empleados))
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title">Resultados de búsqueda</h5>
        <div class="list-group">
            @foreach($empleados as $e)
            <a href="#" class="list-group-item list-group-item-action" onclick="seleccionar({{ $e['clave'] }}, '{{ addslashes($e['nombre_completo']) }}', '{{ addslashes($e['area']) }}', '{{ addslashes($e['cargo']) }}', '{{ addslashes($e['seccion']) }}'); return false;">
                <strong>#{{ $e['clave'] }}</strong> — {{ $e['nombre_completo'] }}
                <span class="text-muted small">({{ $e['area'] }} · {{ $e['cargo'] }} · {{ $e['seccion'] }})</span>
                <span class="badge bg-info text-dark">Días trabajados: {{ $e['dias_trabajados'] }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="card-title">Datos de asignación</h5>
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Número de empleado (clave)</label>
                <input type="number" name="clave" id="clave" class="form-control" required>
            </div>
            <div class="mb-3" id="infoEmpleado" style="display:none;">
                <div class="alert alert-info">
                    <strong id="infoNombre"></strong><br>
                    <span id="infoDetalle" class="small"></span>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Días trabajados</label>
                    <input type="number" name="dias_trabajo" class="form-control" min="1" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Días de descanso</label>
                    <input type="number" name="dias_descanso" class="form-control" min="0" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha inicio (opcional)</label>
                    <input type="date" name="fecha_inicio" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha fin (opcional)</label>
                    <input type="date" name="fecha_fin" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Asignar rol</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function seleccionar(clave, nombre, area, cargo, seccion) {
    document.getElementById('clave').value = clave;
    document.getElementById('infoNombre').textContent = nombre;
    document.getElementById('infoDetalle').textContent = 'Área: ' + area + ' · Cargo: ' + cargo + ' · Sección: ' + seccion;
    document.getElementById('infoEmpleado').style.display = 'block';
}
</script>
@endpush