@extends('layouts.app')

@section('title', 'Revisar viático')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><a href="{{ route('viaticos.index') }}" class="btn btn-sm btn-outline-secondary">Volver</a><h1 class="h3 mt-2 mb-0">Revisar {{ $viatico->folio_ingreso }}</h1></div>
    <span class="badge {{ $viatico->estatus_aprobacion === 'PENDIENTE_REVISION' ? 'text-bg-warning' : ($viatico->estatus_aprobacion === 'APROBADO' ? 'text-bg-success' : 'text-bg-danger') }}">{{ str_replace('_', ' ', $viatico->estatus_aprobacion) }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header">Boleto escaneado</div>
            <div class="card-body text-center">
                @if(str_starts_with((string) $viatico->imagen_mime, 'image/'))
                    <img src="{{ route('viaticos.imagen', $viatico) }}" alt="Boleto {{ $viatico->folio_ingreso }}" class="img-fluid rounded border">
                @else
                    <div class="ratio ratio-4x3"><iframe src="{{ route('viaticos.imagen', $viatico) }}" title="Boleto {{ $viatico->folio_ingreso }}"></iframe></div>
                @endif
                <a href="{{ route('viaticos.imagen', $viatico) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mt-3">Abrir archivo</a>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">Datos del viático</div>
            <div class="card-body">
                @if($viatico->estatus_aprobacion !== 'PENDIENTE_REVISION')
                    <div class="alert alert-secondary">Este registro ya fue procesado y no admite cambios.</div>
                @else
                <form method="POST" action="{{ route('viaticos.actualizar', $viatico) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="numero_empleado" class="form-label">Número de empleado</label><input id="numero_empleado" name="numero_empleado" class="form-control" value="{{ old('numero_empleado', $viatico->numero_empleado) }}" required></div>
                        <div class="col-md-6 mb-3"><label for="nombre_persona" class="form-label">Nombre de la persona</label><input id="nombre_persona" name="nombre_persona" class="form-control" value="{{ old('nombre_persona', $viatico->nombre_persona) }}" required></div>
                        <div class="col-md-4 mb-3"><label for="tipo_transporte" class="form-label">Transporte</label><select id="tipo_transporte" name="tipo_transporte" class="form-select" required><option value="autobus" @selected(old('tipo_transporte', $viatico->tipo_transporte) === 'autobus')>Autobús</option><option value="avion" @selected(old('tipo_transporte', $viatico->tipo_transporte) === 'avion')>Avión</option></select></div>
                        <div class="col-md-4 mb-3"><label for="costo" class="form-label">Costo</label><input type="number" id="costo" step="0.01" min="0.01" name="costo" class="form-control" value="{{ old('costo', $viatico->costo) }}" required></div>
                        <div class="col-md-4 mb-3"><label for="fecha_salida" class="form-label">Fecha de salida</label><input type="date" id="fecha_salida" name="fecha_salida" class="form-control" value="{{ old('fecha_salida', $viatico->fecha_salida?->format('Y-m-d')) }}" required></div>
                        <div class="col-md-6 mb-3"><label for="origen" class="form-label">Origen</label><input id="origen" name="origen" class="form-control" value="{{ old('origen', $viatico->origen) }}" required></div>
                        <div class="col-md-6 mb-3"><label for="destino" class="form-label">Destino</label><input id="destino" name="destino" class="form-control" value="{{ old('destino', $viatico->destino) }}" required></div>
                        <div class="col-md-6 mb-3"><label for="transportista" class="form-label">Transportista</label><input id="transportista" name="transportista" class="form-control" value="{{ old('transportista', $viatico->transportista) }}" required></div>
                        <div class="col-md-6 mb-3"><label for="referencia_boleto" class="form-label">Referencia del boleto</label><input id="referencia_boleto" name="referencia_boleto" class="form-control" value="{{ old('referencia_boleto', $viatico->referencia_boleto) }}"></div>
                        <div class="col-md-6 mb-3"><label for="semana_pagar" class="form-label">Semana a pagar</label><input id="semana_pagar" name="semana_pagar" class="form-control" maxlength="20" value="{{ old('semana_pagar', $viatico->semana_pagar) }}" placeholder="Ej. 2026-S40"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar datos</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
