@extends('layouts.app')

@section('title', 'Editar Descanso Pendiente')

@section('content')
<div class="mb-4">
    <a href="{{ route('descansos.pendientes') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Editar descanso fijo de #{{ $pendiente->clave }}</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('descansos.actualizar_pendiente', $pendiente) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', optional($pendiente->fecha_inicio)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_fin" class="form-label">Fecha fin</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="{{ old('fecha_fin', optional($pendiente->fecha_fin)->format('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="2" maxlength="500">{{ old('observaciones', $pendiente->observaciones) }}</textarea>
            </div>
            <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Guardar</button>
        </form>

        <form method="POST" action="{{ route('descansos.eliminar_pendiente', $pendiente) }}" class="mt-2" onsubmit="return confirm('¿Eliminar este descanso pendiente?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
        </form>
    </div>
</div>
@endsection
