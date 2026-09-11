@extends('layouts.app')

@section('title', 'Editar Rol')

@section('content')
<div class="mb-4">
    <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Editar rol de #{{ $rol->clave }}</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('roles.update', $rol) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Número de empleado</label>
                <input type="number" name="clave" class="form-control" value="{{ $rol->clave }}" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Días trabajados</label>
                    <input type="number" name="dias_trabajo" class="form-control" min="1" value="{{ $rol->dias_trabajo }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Días de descanso</label>
                    <input type="number" name="dias_descanso" class="form-control" min="0" value="{{ $rol->dias_descanso }}" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha inicio (opcional)</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ optional($rol->fecha_inicio)->format('Y-m-d') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha fin (opcional)</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ optional($rol->fecha_fin)->format('Y-m-d') }}">
                </div>
            </div>
            <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Guardar cambios</button>
        </form>
    </div>
</div>
@endsection