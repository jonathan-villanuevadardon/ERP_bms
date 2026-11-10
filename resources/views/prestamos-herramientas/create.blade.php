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
                        <div class="col-12">
                            <label for="buscar_herramienta" class="form-label">Buscar herramienta</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                                <input type="search" id="buscar_herramienta" class="form-control" maxlength="100" autocomplete="off" placeholder="Código, nombre o número de serie" aria-describedby="ayuda-busqueda-herramienta estado-busqueda-herramienta">
                                <button type="button" id="limpiar_herramienta" class="btn btn-outline-secondary">Limpiar</button>
                            </div>
                            <div id="ayuda-busqueda-herramienta" class="form-text">Escribe al menos 2 caracteres. Se mostrarán hasta 20 coincidencias con existencia disponible.</div>
                            <div id="estado-busqueda-herramienta" class="small text-muted mt-2" aria-live="polite"></div>
                            <div id="resultados-herramienta" class="list-group mt-2 d-none" role="listbox" aria-label="Resultados de herramientas"></div>
                            <input type="hidden" id="existencia_id" name="existencia_id" value="{{ old('existencia_id', $existenciaSeleccionada?->id) }}">
                            <div id="herramienta-seleccionada" class="alert alert-success mt-3 mb-0 {{ $existenciaSeleccionada ? '' : 'd-none' }}">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                    <div>
                                        <strong id="seleccion-herramienta">@if($existenciaSeleccionada){{ $existenciaSeleccionada->articulo->sku }} · {{ $existenciaSeleccionada->articulo->nombre }}@endif</strong>
                                        <div id="seleccion-ubicacion" class="small">@if($existenciaSeleccionada){{ $existenciaSeleccionada->ubicacion->almacen->codigo }} / {{ $existenciaSeleccionada->ubicacion->codigo }} · Disponible {{ number_format((float) $existenciaSeleccionada->cantidad, 3) }} {{ $existenciaSeleccionada->articulo->unidad_medida }}@endif</div>
                                    </div>
                                    <button type="button" id="cambiar_herramienta" class="btn btn-sm btn-outline-success">Cambiar</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6"><label for="numero_empleado" class="form-label">Número de trabajador</label><input type="number" id="numero_empleado" name="numero_empleado" class="form-control" value="{{ old('numero_empleado', $empleadoSeleccionado) }}" required><div class="form-text">La identidad y sección se obtienen de HC; deben corresponder a la sección del almacén.</div></div>
                        <div class="col-md-3"><label for="tipo_asignacion" class="form-label">Tipo</label><select id="tipo_asignacion" name="tipo_asignacion" class="form-select" required><option value="PRESTAMO" @selected(old('tipo_asignacion', request('tipo_asignacion')) === 'PRESTAMO')>Préstamo</option><option value="RESGUARDO" @selected(old('tipo_asignacion', request('tipo_asignacion')) === 'RESGUARDO')>Resguardo</option></select></div>
                        <div class="col-md-3"><label for="cantidad" class="form-label">Cantidad</label><div class="input-group"><input type="number" step="0.001" min="0.001" @if($existenciaSeleccionada) max="{{ $existenciaSeleccionada->cantidad }}" @endif id="cantidad" name="cantidad" class="form-control" value="{{ old('cantidad', request('cantidad', 1)) }}" required><span id="unidad-cantidad-herramienta" class="input-group-text">{{ $existenciaSeleccionada?->articulo?->unidad_medida ?? 'unidad' }}</span></div></div>
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
const formularioPrestamo = document.getElementById('prestamo-form');
const buscarHerramienta = document.getElementById('buscar_herramienta');
const resultadosHerramienta = document.getElementById('resultados-herramienta');
const estadoBusqueda = document.getElementById('estado-busqueda-herramienta');
const existenciaId = document.getElementById('existencia_id');
const herramientaSeleccionada = document.getElementById('herramienta-seleccionada');
const seleccionHerramienta = document.getElementById('seleccion-herramienta');
const seleccionUbicacion = document.getElementById('seleccion-ubicacion');
const cantidad = document.getElementById('cantidad');
const unidadCantidadHerramienta = document.getElementById('unidad-cantidad-herramienta');
let temporizadorBusqueda;
let solicitudBusqueda;

const actualizarFechaRequerida = () => fechaCompromiso.required = tipoAsignacion.value === 'PRESTAMO';
tipoAsignacion.addEventListener('change', actualizarFechaRequerida);
actualizarFechaRequerida();

function limpiarResultados() {
    resultadosHerramienta.replaceChildren();
    resultadosHerramienta.classList.add('d-none');
}

function seleccionarHerramienta(resultado) {
    existenciaId.value = resultado.id;
    seleccionHerramienta.textContent = `${resultado.sku} · ${resultado.nombre}${resultado.numero_serie ? ` · Serie ${resultado.numero_serie}` : ''}`;
    seleccionUbicacion.textContent = `${resultado.almacen} / ${resultado.ubicacion} · Disponible ${Number(resultado.disponible).toFixed(3)} ${resultado.unidad}`;
    cantidad.max = resultado.disponible;
    unidadCantidadHerramienta.textContent = resultado.unidad;
    herramientaSeleccionada.classList.remove('d-none');
    estadoBusqueda.textContent = 'Herramienta seleccionada.';
    limpiarResultados();
}

function mostrarResultados(resultados) {
    limpiarResultados();
    if (resultados.length === 0) {
        estadoBusqueda.textContent = 'Sin coincidencias disponibles. Prueba con otro código, nombre o número de serie.';
        return;
    }

    resultados.forEach((resultado) => {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'list-group-item list-group-item-action';
        boton.setAttribute('role', 'option');

        const titulo = document.createElement('strong');
        titulo.textContent = `${resultado.sku} · ${resultado.nombre}`;
        const detalle = document.createElement('span');
        detalle.className = 'd-block small text-muted';
        detalle.textContent = `${resultado.almacen} / ${resultado.ubicacion} · ${resultado.seccion ?? 'Sin sección'} · Disponible ${Number(resultado.disponible).toFixed(3)} ${resultado.unidad}${resultado.numero_serie ? ` · Serie ${resultado.numero_serie}` : ''}`;
        boton.append(titulo, detalle);
        boton.addEventListener('click', () => seleccionarHerramienta(resultado));
        resultadosHerramienta.appendChild(boton);
    });

    resultadosHerramienta.classList.remove('d-none');
    estadoBusqueda.textContent = `${resultados.length} coincidencia${resultados.length === 1 ? '' : 's'}. Selecciona una herramienta.`;
}

async function consultarHerramientas() {
    const termino = buscarHerramienta.value.trim();
    if (termino.length < 2) {
        estadoBusqueda.textContent = termino.length === 0 ? '' : 'Escribe al menos 2 caracteres para buscar.';
        limpiarResultados();
        return;
    }

    solicitudBusqueda?.abort();
    solicitudBusqueda = new AbortController();
    estadoBusqueda.textContent = 'Buscando herramientas disponibles…';

    try {
        const url = new URL(@json(route('prestamos-herramientas.buscar-herramientas')), window.location.origin);
        url.searchParams.set('q', termino);
        const respuesta = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            signal: solicitudBusqueda.signal,
        });
        if (!respuesta.ok) throw new Error('No fue posible completar la búsqueda.');
        const datos = await respuesta.json();
        mostrarResultados(datos.resultados ?? []);
    } catch (error) {
        if (error.name === 'AbortError') return;
        limpiarResultados();
        estadoBusqueda.textContent = 'No fue posible buscar herramientas. Intenta nuevamente.';
    }
}

buscarHerramienta.addEventListener('input', function () {
    clearTimeout(temporizadorBusqueda);
    temporizadorBusqueda = setTimeout(consultarHerramientas, 250);
});

document.getElementById('limpiar_herramienta').addEventListener('click', function () {
    buscarHerramienta.value = '';
    estadoBusqueda.textContent = '';
    limpiarResultados();
    buscarHerramienta.focus();
});

document.getElementById('cambiar_herramienta').addEventListener('click', function () {
    existenciaId.value = '';
    cantidad.removeAttribute('max');
    unidadCantidadHerramienta.textContent = 'unidad';
    herramientaSeleccionada.classList.add('d-none');
    buscarHerramienta.focus();
});

formularioPrestamo.addEventListener('submit', function (event) {
    if (existenciaId.value) return;
    event.preventDefault();
    estadoBusqueda.textContent = 'Busca y selecciona una herramienta antes de registrar la salida.';
    buscarHerramienta.focus();
});

document.getElementById('buscar-empleado').addEventListener('submit', function () {
    ['existencia_id', 'numero_empleado', 'tipo_asignacion', 'cantidad', 'fecha_compromiso'].forEach((nombre) => {
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
