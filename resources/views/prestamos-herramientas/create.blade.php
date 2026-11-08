@extends('layouts.app')

@section('title', 'Nueva salida de herramienta')

@section('content')
<div class="mb-3"><a href="{{ route('prestamos-herramientas.index') }}" class="btn btn-sm btn-outline-secondary">Volver</a></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h1 class="h5 mb-0">Entregar herramienta desde un almacén</h1></div>
            <div class="card-body">
                <form id="prestamo-form" method="POST" action="{{ route('prestamos-herramientas.guardar') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12"><label for="existencia_id" class="form-label">Herramienta y ubicación de salida</label><select id="existencia_id" name="existencia_id" class="form-select" required><option value="">Selecciona</option>@foreach($existencias->groupBy(fn ($existencia) => $existencia->ubicacion->almacen_id) as $grupo)<optgroup label="{{ $grupo->first()->ubicacion->almacen->codigo }} · {{ $grupo->first()->ubicacion->almacen->nombre }} / {{ $grupo->first()->ubicacion->almacen->seccion }}">@foreach($grupo as $existencia)<option value="{{ $existencia->id }}" @selected((string) old('existencia_id', request('existencia_id')) === (string) $existencia->id)>{{ $existencia->articulo->sku }} · {{ $existencia->articulo->nombre }} · {{ $existencia->ubicacion->codigo }} · Disponible {{ number_format((float) $existencia->cantidad, 3) }}@if($existencia->articulo->numero_serie) · Serie {{ $existencia->articulo->numero_serie }}@endif</option>@endforeach</optgroup>@endforeach</select></div>
                        <div class="col-md-6"><label for="numero_empleado" class="form-label">Número de trabajador</label><input type="number" id="numero_empleado" name="numero_empleado" class="form-control" value="{{ old('numero_empleado', $empleadoSeleccionado) }}" required><div class="form-text">La identidad y sección se obtienen de HC; deben corresponder a la sección del almacén.</div></div>
                        <div class="col-md-3"><label for="tipo_asignacion" class="form-label">Tipo</label><select id="tipo_asignacion" name="tipo_asignacion" class="form-select" required><option value="PRESTAMO" @selected(old('tipo_asignacion', request('tipo_asignacion')) === 'PRESTAMO')>Préstamo</option><option value="RESGUARDO" @selected(old('tipo_asignacion', request('tipo_asignacion')) === 'RESGUARDO')>Resguardo</option></select></div>
                        <div class="col-md-3"><label for="cantidad" class="form-label">Cantidad</label><input type="number" step="0.001" min="0.001" id="cantidad" name="cantidad" class="form-control" value="{{ old('cantidad', request('cantidad', 1)) }}" required></div>
                        <div class="col-md-6"><label for="fecha_compromiso" class="form-label">Fecha compromiso</label><input type="date" id="fecha_compromiso" name="fecha_compromiso" class="form-control" value="{{ old('fecha_compromiso', request('fecha_compromiso')) }}"><div class="form-text">Obligatoria para préstamos; opcional para resguardos.</div></div>
                        <div class="col-12"><label for="observaciones" class="form-label">Condición de salida / observaciones</label><textarea id="observaciones" name="observaciones" class="form-control" maxlength="500" rows="3">{{ old('observaciones') }}</textarea></div>
                    </div>
                    <button type="submit" class="btn btn-warning mt-4" onclick="return confirm('¿Confirmar la entrega de esta herramienta?')"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Registrar salida</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">Buscar trabajador en HC</div>
            <div class="card-body">
                <form id="buscar-empleado" method="GET" class="d-flex gap-2"><input name="termino" class="form-control" value="{{ request('termino') }}" placeholder="Nombre o número"><button type="submit" class="btn btn-outline-dark">Buscar</button></form>
                @if($empleados->isNotEmpty())<div class="list-group mt-3">@foreach($empleados as $empleado)<a class="list-group-item list-group-item-action" href="{{ route('prestamos-herramientas.crear', array_merge(request()->only(['termino', 'existencia_id', 'tipo_asignacion', 'cantidad', 'fecha_compromiso']), ['numero_empleado' => $empleado['clave']])) }}"><strong>#{{ $empleado['clave'] }} {{ $empleado['nombre_completo'] }}</strong><br><span class="small text-muted">{{ $empleado['area'] }} · {{ $empleado['seccion'] }}</span></a>@endforeach</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const tipoAsignacion = document.getElementById('tipo_asignacion');
const fechaCompromiso = document.getElementById('fecha_compromiso');
const actualizarFechaRequerida = () => fechaCompromiso.required = tipoAsignacion.value === 'PRESTAMO';
tipoAsignacion.addEventListener('change', actualizarFechaRequerida);
actualizarFechaRequerida();

document.getElementById('buscar-empleado').addEventListener('submit', function () {
    ['existencia_id', 'tipo_asignacion', 'cantidad', 'fecha_compromiso'].forEach((nombre) => {
        const campo = document.getElementById(nombre);
        if (!campo || !campo.value) return;
        const oculto = document.createElement('input');
        oculto.type = 'hidden';
        oculto.name = nombre;
        oculto.value = campo.value;
        this.appendChild(oculto);
    });
});
</script>
@endpush
