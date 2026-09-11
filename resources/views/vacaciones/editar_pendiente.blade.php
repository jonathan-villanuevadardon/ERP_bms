@extends('layouts.app')

@section('title', 'Editar Vacaciones Pendientes')

@section('content')
<div class="mb-4">
    <a href="{{ route('vacaciones.pendientes') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Editar vacaciones de #{{ $pendiente->clave }}</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('vacaciones.actualizar_pendiente', $pendiente) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ optional($pendiente->fecha_inicio)->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ optional($pendiente->fecha_fin)->format('Y-m-d') }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2">{{ $pendiente->observaciones }}</textarea>
            </div>
            <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Guardar</button>

            <button type="button" class="btn btn-outline-danger" onclick="eliminar()">
                <i class="bi bi-trash"></i> Eliminar
            </button>
        </form>

        <form id="formEliminar" method="POST" action="{{ route('vacaciones.eliminar_pendiente', $pendiente) }}" class="d-none">
            @csrf @method('DELETE')
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function eliminar() {
    if (confirm('¿Eliminar esta solicitud pendiente?')) {
        document.getElementById('formEliminar').submit();
    }
}
</script>
@endpush