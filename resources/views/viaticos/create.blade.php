@extends('layouts.app')

@section('title', 'Capturar viático')

@section('content')
<div class="mb-3"><a href="{{ route('viaticos.index') }}" class="btn btn-sm btn-outline-secondary">Volver</a><h1 class="h3 mt-2 mb-0">Capturar viático</h1></div>

<div class="card shadow-sm">
    <div class="card-header">Captura manual de RH</div>
    <div class="card-body">
        <div class="alert alert-info small">El registro quedará como pendiente de revisión. Adjunta el boleto para que RH pueda validarlo posteriormente.</div>
        <form method="POST" action="{{ route('viaticos.guardar') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3"><label for="boleto" class="form-label">Boleto escaneado</label><input type="file" id="boleto" name="boleto" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" required><div class="form-text">PDF o imagen, máximo 10 MB.</div></div>
                <div class="col-md-3 mb-3"><label for="numero_empleado" class="form-label">Número de empleado</label><input id="numero_empleado" name="numero_empleado" class="form-control" value="{{ old('numero_empleado') }}" required></div>
                <div class="col-md-3 mb-3"><label for="nombre_persona" class="form-label">Nombre de la persona</label><input id="nombre_persona" name="nombre_persona" class="form-control" value="{{ old('nombre_persona') }}" required></div>
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
