@extends('layouts.app')

@section('title', 'Agregar Descanso')

@section('content')
<div class="mb-4">
    <a href="{{ route('descansos.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Agregar descanso</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('descansos.create') }}" class="row g-2">
            <div class="col-md-6">
                <label for="termino" class="visually-hidden">Nombre, clave o cargo</label>
                <input type="search" id="termino" name="termino" class="form-control" placeholder="Buscar por nombre, clave o cargo" value="{{ request('termino') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-dark w-100">Buscar</button>
            </div>
        </form>
        @if(!empty($empleados))
        <div class="list-group mt-3">
            @foreach($empleados as $e)
            <button type="button" class="list-group-item list-group-item-action" onclick="seleccionar({{ $e['clave'] }})">
                <strong>#{{ $e['clave'] }}</strong> — {{ $e['nombre_completo'] }}
                <span class="text-muted small">({{ $e['area'] }} · {{ $e['seccion'] }})</span>
            </button>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('descansos.store') }}">
            @csrf
            <div class="mb-3">
                <label for="clave" class="form-label">Número de empleado (clave)</label>
                <input type="number" name="clave" id="clave" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo de descanso</label>
                <select id="tipo" name="tipo" class="form-select" required>
                    <option value="por_periodo">Por periodo (se registra directo)</option>
                    <option value="fijo">Fijo (requiere aprobación del Admin)</option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_fin" class="form-label">Fecha fin</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Guardar</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function seleccionar(clave, nombre) {
    document.getElementById('clave').value = clave;
}
</script>
@endpush
