@extends('layouts.app')

@section('title', 'Editar Descanso')

@section('content')
<div class="mb-4">
    <a href="{{ route('descansos.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Editar descanso de #{{ $descanso->clave }}</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('descansos.update', $descanso) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Número de empleado</label>
                <input type="number" name="clave" class="form-control" value="{{ $descanso->clave }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" required>
                    <option value="por_periodo" @selected($descanso->tipo === 'por_periodo')>Por periodo</option>
                    <option value="fijo" @selected($descanso->tipo === 'fijo')>Fijo</option>
                </select>
                <div class="form-text">El tipo no puede cambiarse después del registro.</div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ optional($descanso->fecha_inicio)->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ optional($descanso->fecha_fin)->format('Y-m-d') }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2">{{ $descanso->observaciones }}</textarea>
            </div>
            <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Guardar</button>
        </form>
    </div>
</div>
@endsection
