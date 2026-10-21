@extends('layouts.app')

@section('title', 'Capturar viático')

@section('content')
<div class="mb-3"><a href="{{ route('viaticos.index') }}" class="btn btn-sm btn-outline-secondary">Volver</a><h1 class="h3 mt-2 mb-0">Capturar viático</h1></div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('viaticos.crear') }}" class="row g-2">
            <div class="col-md-6">
                <label for="termino" class="visually-hidden">Nombre, número, área o cargo</label>
                <input type="search" id="termino" name="termino" class="form-control" placeholder="Buscar empleado por nombre, número, área o cargo" value="{{ request('termino') }}">
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-outline-dark w-100">Buscar</button></div>
        </form>
        @if(!empty($empleados))
        <div class="list-group mt-3">
            @foreach($empleados as $empleado)
            <button type="button" class="list-group-item list-group-item-action" data-clave="{{ $empleado['clave'] }}" data-nombre="{{ $empleado['nombre_completo'] }}" onclick="seleccionarEmpleado(this)">
                <strong>#{{ $empleado['clave'] }}</strong> — {{ $empleado['nombre_completo'] }}
                <span class="text-muted small">({{ $empleado['area'] }} · {{ $empleado['seccion'] }})</span>
            </button>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Captura manual de RH</div>
    <div class="card-body">
        <div class="alert alert-info small">El registro quedará como pendiente de revisión. Adjunta el boleto para que RH pueda validarlo posteriormente.</div>
        <form method="POST" action="{{ route('viaticos.guardar') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3"><label for="boleto" class="form-label">Boleto escaneado</label><input type="file" id="boleto" name="boleto" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" required><div class="form-text">PDF o imagen, máximo 10 MB.</div></div>
                <div class="col-md-6 mb-3"><label for="numero_empleado" class="form-label">Número de empleado</label><input type="number" min="1" id="numero_empleado" name="numero_empleado" class="form-control" value="{{ old('numero_empleado') }}" required><div id="empleado-seleccionado" class="form-text" aria-live="polite">El nombre y la sección se validarán con el catálogo de empleados.</div></div>
                <div class="col-md-3 mb-3"><label for="tipo_transporte" class="form-label">Transporte</label><select id="tipo_transporte" name="tipo_transporte" class="form-select" required><option value="">Selecciona</option><option value="autobus" @selected(old('tipo_transporte') === 'autobus')>Autobús</option><option value="avion" @selected(old('tipo_transporte') === 'avion')>Avión</option></select></div>
                <div class="col-md-3 mb-3"><label for="costo" class="form-label">Costo</label><input type="number" id="costo" step="0.01" min="0.01" name="costo" class="form-control" value="{{ old('costo') }}" required></div>
                <div class="col-md-3 mb-3"><label for="fecha_salida" class="form-label">Fecha de salida</label><input type="date" id="fecha_salida" name="fecha_salida" class="form-control" value="{{ old('fecha_salida') }}" required></div>
                <div class="col-md-3 mb-3"><label for="semana_pagar" class="form-label">Semana a pagar</label><input id="semana_pagar" name="semana_pagar" class="form-control" maxlength="20" value="{{ old('semana_pagar') }}" placeholder="Ej. 2026-S40"></div>
                <div class="col-md-6 mb-3"><label for="origen" class="form-label">Origen</label><input id="origen" name="origen" class="form-control" value="{{ old('origen') }}" required></div>
                <div class="col-md-6 mb-3"><label for="destino" class="form-label">Destino</label><input id="destino" name="destino" class="form-control" value="{{ old('destino') }}" required></div>
                <div class="col-md-6 mb-3"><label for="transportista" class="form-label">Transportista</label><input id="transportista" name="transportista" class="form-control" value="{{ old('transportista') }}" required></div>
                <div class="col-md-6 mb-3"><label for="referencia_boleto" class="form-label">Referencia del boleto</label><input id="referencia_boleto" name="referencia_boleto" class="form-control" value="{{ old('referencia_boleto') }}"></div>
            </div>
            <button type="submit" class="btn btn-primary">Guardar y enviar a revisión</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function seleccionarEmpleado(boton) {
    document.getElementById('numero_empleado').value = boton.dataset.clave;
    document.getElementById('empleado-seleccionado').textContent = boton.dataset.nombre;
}
</script>
@endpush
