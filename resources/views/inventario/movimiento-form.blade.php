@extends('layouts.app')

@section('title', 'Registrar movimiento')

@section('content')
<div class="mb-3"><a href="{{ route('inventario.movimientos') }}" class="btn btn-sm btn-outline-secondary">Volver al historial</a></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white"><h1 class="h5 mb-0">Entrada, salida, ajuste o transferencia</h1></div>
            <div class="card-body">
                <form id="movimiento-form" method="POST" action="{{ route('inventario.movimientos.guardar') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12"><label for="articulo_id" class="form-label">Artículo</label><select id="articulo_id" name="articulo_id" class="form-select" required><option value="" data-unidad="">Selecciona</option>@foreach($articulos as $articulo)<option value="{{ $articulo->id }}" data-unidad="{{ $articulo->unidad_medida }}" @selected((string) old('articulo_id', request('articulo_id')) === (string) $articulo->id)>{{ $articulo->sku }} · {{ $articulo->nombre }} · Disponible {{ number_format((float) ($articulo->stock_visible ?? 0), 3) }} {{ $articulo->unidad_medida }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label for="tipo" class="form-label">Tipo</label><select id="tipo" name="tipo" class="form-select" required><option value="ENTRADA" @selected(old('tipo', request('tipo')) === 'ENTRADA')>Entrada de compra</option><option value="SALIDA" @selected(old('tipo', request('tipo')) === 'SALIDA')>Salida de material</option><option value="TRANSFERENCIA" @selected(old('tipo', request('tipo')) === 'TRANSFERENCIA')>Transferencia interna</option><option value="AJUSTE_ENTRADA" @selected(old('tipo', request('tipo')) === 'AJUSTE_ENTRADA')>Ajuste de entrada</option><option value="AJUSTE_SALIDA" @selected(old('tipo', request('tipo')) === 'AJUSTE_SALIDA')>Ajuste de salida</option></select></div>
                        <div class="col-md-4"><label for="cantidad" class="form-label">Cantidad</label><div class="input-group"><input type="number" step="0.001" min="0.001" id="cantidad" name="cantidad" class="form-control" value="{{ old('cantidad', request('cantidad')) }}" required><span id="unidad-cantidad" class="input-group-text">unidad</span></div></div>
                        <div class="col-md-4" id="costo-group"><label for="costo_unitario" class="form-label">Costo unitario de entrada</label><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.0001" min="0" id="costo_unitario" name="costo_unitario" class="form-control" value="{{ old('costo_unitario', request('costo_unitario')) }}"></div></div>
                        <div class="col-md-6" id="origen-group"><label for="ubicacion_origen_id" class="form-label">Origen</label><select id="ubicacion_origen_id" name="ubicacion_origen_id" class="form-select"><option value="">Selecciona origen</option>@foreach($ubicaciones->groupBy('almacen_id') as $grupo)<optgroup label="{{ $grupo->first()->almacen->codigo }} · {{ $grupo->first()->almacen->nombre }}">@foreach($grupo as $ubicacion)<option value="{{ $ubicacion->id }}" @selected((string) old('ubicacion_origen_id', request('ubicacion_origen_id')) === (string) $ubicacion->id)>{{ $ubicacion->codigo }} · {{ $ubicacion->anaquel }} / {{ $ubicacion->nivel }}</option>@endforeach</optgroup>@endforeach</select></div>
                        <div class="col-md-6" id="destino-group"><label for="ubicacion_destino_id" class="form-label">Destino</label><select id="ubicacion_destino_id" name="ubicacion_destino_id" class="form-select"><option value="">Selecciona destino</option>@foreach($ubicaciones->groupBy('almacen_id') as $grupo)<optgroup label="{{ $grupo->first()->almacen->codigo }} · {{ $grupo->first()->almacen->nombre }}">@foreach($grupo as $ubicacion)<option value="{{ $ubicacion->id }}" @selected((string) old('ubicacion_destino_id', request('ubicacion_destino_id')) === (string) $ubicacion->id)>{{ $ubicacion->codigo }} · {{ $ubicacion->anaquel }} / {{ $ubicacion->nivel }}</option>@endforeach</optgroup>@endforeach</select></div>
                        <div class="col-md-6" id="empleado-group"><label for="numero_empleado" class="form-label">Número de trabajador</label><input type="number" id="numero_empleado" name="numero_empleado" class="form-control" value="{{ old('numero_empleado', $empleadoSeleccionado) }}"><div class="form-text">Obligatorio en salidas de material; se valida contra HC.</div></div>
                        <div class="col-md-6"><label for="referencia" class="form-label">Referencia</label><input id="referencia" name="referencia" class="form-control" maxlength="100" value="{{ old('referencia', request('referencia')) }}" placeholder="Factura, vale o motivo"></div>
                        <div class="col-12"><label for="observaciones" class="form-label">Observaciones</label><textarea id="observaciones" name="observaciones" class="form-control" maxlength="500">{{ old('observaciones') }}</textarea></div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4">Registrar movimiento</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">Buscar trabajador en HC</div>
            <div class="card-body">
                <form id="buscar-empleado" method="GET" class="d-flex gap-2"><input name="termino" class="form-control" value="{{ request('termino') }}" placeholder="Nombre o número"><button type="submit" class="btn btn-outline-dark">Buscar</button></form>
                @if($empleados->isNotEmpty())
                <div class="list-group mt-3">
                    @foreach($empleados as $empleado)
                    <a class="list-group-item list-group-item-action" href="{{ route('inventario.movimientos.crear', array_merge(request()->only(['termino', 'articulo_id', 'tipo', 'cantidad', 'costo_unitario', 'ubicacion_origen_id', 'ubicacion_destino_id', 'referencia']), ['numero_empleado' => $empleado['clave']])) }}"><strong>#{{ $empleado['clave'] }} {{ $empleado['nombre_completo'] }}</strong><br><span class="small text-muted">{{ $empleado['area'] }} · {{ $empleado['seccion'] }}</span></a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const tipoMovimiento = document.getElementById('tipo');
const origenGrupo = document.getElementById('origen-group');
const destinoGrupo = document.getElementById('destino-group');
const costoGrupo = document.getElementById('costo-group');
const empleadoGrupo = document.getElementById('empleado-group');
const origen = document.getElementById('ubicacion_origen_id');
const destino = document.getElementById('ubicacion_destino_id');
const costo = document.getElementById('costo_unitario');
const numeroEmpleado = document.getElementById('numero_empleado');
const articulo = document.getElementById('articulo_id');
const unidadCantidad = document.getElementById('unidad-cantidad');

function actualizarUnidad() {
    unidadCantidad.textContent = articulo.selectedOptions[0]?.dataset.unidad || 'unidad';
}

function actualizarMovimiento() {
    const tipo = tipoMovimiento.value;
    const requiereOrigen = ['SALIDA', 'AJUSTE_SALIDA', 'TRANSFERENCIA'].includes(tipo);
    const requiereDestino = ['ENTRADA', 'AJUSTE_ENTRADA', 'TRANSFERENCIA'].includes(tipo);
    origenGrupo.classList.toggle('d-none', !requiereOrigen);
    destinoGrupo.classList.toggle('d-none', !requiereDestino);
    costoGrupo.classList.toggle('d-none', !['ENTRADA', 'AJUSTE_ENTRADA'].includes(tipo));
    empleadoGrupo.classList.toggle('d-none', tipo !== 'SALIDA');
    origen.required = requiereOrigen;
    destino.required = requiereDestino;
    origen.disabled = !requiereOrigen;
    destino.disabled = !requiereDestino;
    if (!requiereOrigen) origen.value = '';
    if (!requiereDestino) destino.value = '';
    costo.required = tipo === 'ENTRADA';
    numeroEmpleado.required = tipo === 'SALIDA';
}

document.getElementById('buscar-empleado').addEventListener('submit', function () {
    ['articulo_id', 'tipo', 'cantidad', 'costo_unitario', 'ubicacion_origen_id', 'ubicacion_destino_id', 'referencia'].forEach((nombre) => {
        const campo = document.getElementById(nombre);
        if (!campo || !campo.value) return;
        const oculto = document.createElement('input');
        oculto.type = 'hidden';
        oculto.name = nombre;
        oculto.value = campo.value;
        this.appendChild(oculto);
    });
});

tipoMovimiento.addEventListener('change', actualizarMovimiento);
articulo.addEventListener('change', actualizarUnidad);
actualizarMovimiento();
actualizarUnidad();
</script>
@endpush
