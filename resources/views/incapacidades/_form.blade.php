@php($i = $pendiente ?? null)
@csrf
@if(isset($pendiente)) @method('PUT') @endif

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Número de empleado</label>
        <input type="number" name="clave" id="clave" class="form-control" value="{{ old('clave', $i?->clave) }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Tipo de incapacidad</label>
        <select name="tipo" class="form-select" required>
            <option value="">Selecciona...</option>
            @foreach($tipos as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('tipo', $i?->tipo) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Folio IMSS</label>
        <input type="text" name="folio" class="form-control" maxlength="100" value="{{ old('folio', $i?->folio) }}" required>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Fecha inicio</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', $i?->fecha_inicio?->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Fecha fin</label>
        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ old('fecha_fin', $i?->fecha_fin?->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Días (cálculo automático)</label>
        <input type="number" id="dias" class="form-control" value="{{ $i?->dias }}" readonly>
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Observaciones</label>
    <textarea name="observaciones" class="form-control" rows="3" maxlength="500">{{ old('observaciones', $i?->observaciones) }}</textarea>
</div>
<button class="btn btn-dark"><i class="bi bi-check-lg"></i> {{ isset($pendiente) ? 'Guardar cambios' : 'Enviar a aprobación' }}</button>

@push('scripts')
<script>
function calcularDias() {
    const inicio = document.getElementById('fecha_inicio').value;
    const fin = document.getElementById('fecha_fin').value;
    document.getElementById('dias').value = inicio && fin && fin >= inicio
        ? Math.round((new Date(fin + 'T00:00:00') - new Date(inicio + 'T00:00:00')) / 86400000) + 1
        : '';
}
document.getElementById('fecha_inicio').addEventListener('change', calcularDias);
document.getElementById('fecha_fin').addEventListener('change', calcularDias);
calcularDias();
</script>
@endpush
