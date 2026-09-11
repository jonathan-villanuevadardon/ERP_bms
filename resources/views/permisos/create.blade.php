@extends('layouts.app')

@section('title', 'Agregar Permiso')

@section('content')
<div class="mb-4">
    <a href="{{ route('permisos.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Agregar permiso</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('permisos.create') }}" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="termino" class="form-control" placeholder="Buscar por nombre, clave o cargo" value="{{ request('termino') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-dark w-100">Buscar</button>
            </div>
        </form>
        @if(!empty($empleados))
        <div class="list-group mt-3">
            @foreach($empleados as $e)
            <a href="#" class="list-group-item list-group-item-action" onclick="seleccionar({{ $e['clave'] }}, '{{ addslashes($e['nombre_completo']) }}'); return false;">
                <strong>#{{ $e['clave'] }}</strong> — {{ $e['nombre_completo'] }}
                <span class="text-muted small">({{ $e['area'] }} · {{ $e['seccion'] }})</span>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('permisos.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Número de empleado (clave)</label>
                <input type="number" name="clave" id="clave" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tipo de permiso</label>
                <select name="tipo" class="form-select" required>
                    <option value="sin_goce">Sin goce de sueldo (se registra directo)</option>
                    <option value="con_goce">Con goce de sueldo (requiere aprobación)</option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha fin (opcional)</label>
                    <input type="date" name="fecha_fin" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Motivo</label>
                <textarea name="motivo" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Guardar</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function seleccionar(clave) {
    document.getElementById('clave').value = clave;
}
</script>
@endpush