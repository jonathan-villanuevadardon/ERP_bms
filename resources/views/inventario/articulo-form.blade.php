@extends('layouts.app')

@section('title', $articulo ? 'Detalle del artículo' : 'Nuevo artículo')

@section('content')
<div class="mb-3"><a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-secondary">Volver al inventario</a></div>
<div class="card shadow-sm mx-auto" style="max-width: 1080px">
    <div class="card-header bg-dark text-white"><h1 class="h5 mb-0">{{ $articulo ? 'Catálogo y existencias del artículo' : 'Registrar artículo y existencia inicial' }}</h1></div>
    <div class="card-body">
        @php($mostrarAjusteCosto = $articulo && ($ajustesCostoDisponible ?? false) && (request()->boolean('mostrar_costo') || $errors->has('costo_nuevo') || $errors->has('motivo')))
        <form method="POST" action="{{ $articulo ? route('inventario.articulos.actualizar', $articulo) : route('inventario.articulos.guardar') }}">
            @csrf
            @if($articulo) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-4"><label for="sku" class="form-label">SKU / código</label><input id="sku" name="sku" class="form-control" maxlength="80" value="{{ old('sku', $articulo?->sku) }}" required></div>
                <div class="col-md-8"><label for="nombre" class="form-label">Nombre</label><input id="nombre" name="nombre" class="form-control" maxlength="150" value="{{ old('nombre', $articulo?->nombre) }}" required></div>
                <div class="col-md-4"><label for="tipo" class="form-label">Tipo</label><select id="tipo" name="tipo" class="form-select" required><option value="MATERIAL" @selected(old('tipo', $articulo?->tipo) === 'MATERIAL')>Material / insumo</option><option value="HERRAMIENTA" @selected(old('tipo', $articulo?->tipo) === 'HERRAMIENTA')>Herramienta</option></select></div>
                @php($unidadActual = old('unidad_medida', $articulo?->unidad_medida ?? 'pieza'))
                <div class="col-md-4"><label for="unidad_medida" class="form-label">Unidad de medida</label><select id="unidad_medida" name="unidad_medida" class="form-select" required>@if(!array_key_exists($unidadActual, $unidadesMedida))<option value="{{ $unidadActual }}" selected>{{ ucfirst($unidadActual) }} (actual)</option>@endif @foreach($unidadesMedida as $valor => $etiqueta)<option value="{{ $valor }}" @selected($unidadActual === $valor)>{{ $etiqueta }}</option>@endforeach</select><div class="form-text">La existencia, entradas y salidas se registrarán en esta unidad. No se realizan conversiones automáticas.</div></div>
                <div class="col-md-4"><label for="numero_serie" class="form-label">Número de serie</label><input id="numero_serie" name="numero_serie" class="form-control" maxlength="100" value="{{ old('numero_serie', $articulo?->numero_serie) }}"></div>

                @if(!$articulo)
                <div class="col-12"><hr><h2 class="h6">Ubicación y abastecimiento inicial</h2></div>
                <div class="col-md-6"><label for="ubicacion_id" class="form-label">Almacén / ubicación</label><select id="ubicacion_id" name="ubicacion_id" class="form-select" required><option value="">Selecciona una ubicación</option>@foreach($ubicaciones->groupBy('almacen_id') as $grupo)<optgroup label="{{ $grupo->first()->almacen->codigo }} · {{ $grupo->first()->almacen->nombre }} ({{ $grupo->first()->almacen->seccion ?: 'Sin sección' }})">@foreach($grupo as $ubicacion)<option value="{{ $ubicacion->id }}" @selected((string) old('ubicacion_id') === (string) $ubicacion->id)>{{ $ubicacion->codigo }} · {{ $ubicacion->anaquel }} / {{ $ubicacion->nivel }}</option>@endforeach</optgroup>@endforeach</select><div class="form-text"><a href="{{ route('inventario.almacenes') }}">Agregar almacenes o ubicaciones</a> antes de registrar existencias.</div></div>
                <div class="col-md-3"><label for="stock_inicial" class="form-label">Existencia inicial</label><input type="number" step="0.001" min="0" id="stock_inicial" name="stock_inicial" class="form-control" value="{{ old('stock_inicial', 0) }}" required></div>
                <div class="col-md-3"><label for="costo_unitario_inicial" class="form-label">Costo unitario inicial</label><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.0001" min="0" id="costo_unitario_inicial" name="costo_unitario_inicial" class="form-control" value="{{ old('costo_unitario_inicial', 0) }}"></div></div>
                <div class="col-md-3"><label for="stock_minimo" class="form-label">Mínimo en ubicación</label><input type="number" step="0.001" min="0" id="stock_minimo" name="stock_minimo" class="form-control" value="{{ old('stock_minimo', 0) }}" required></div>
                <div class="col-md-3"><label for="stock_maximo" class="form-label">Máximo en ubicación</label><input type="number" step="0.001" min="0" id="stock_maximo" name="stock_maximo" class="form-control" value="{{ old('stock_maximo') }}"></div>
                @else
                <div class="col-md-4"><label class="form-label">Existencia total</label><input class="form-control" value="{{ number_format((float) $articulo->stock_actual, 3) }} {{ $articulo->unidad_medida }}" readonly><div class="form-text">Suma de todas las ubicaciones.</div></div>
                <div class="col-md-4"><label class="form-label">Costo promedio global</label><input class="form-control" value="${{ number_format((float) $articulo->costo_promedio, 4) }}" readonly></div>
                @endif

                <div class="col-12"><label for="descripcion" class="form-label">Descripción</label><textarea id="descripcion" name="descripcion" class="form-control" rows="3" maxlength="500">{{ old('descripcion', $articulo?->descripcion) }}</textarea></div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 bg-body-tertiary border rounded p-3 mt-4">
                <div><input type="hidden" name="activo" value="0"><div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" @checked(old('activo', $articulo?->activo ?? true))><label for="activo" class="form-check-label fw-semibold">Artículo activo</label></div></div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Guardar artículo</button>
                    @if($articulo && ($ajustesCostoDisponible ?? false))
                    <button type="button" class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#ajuste-costo" aria-expanded="{{ $mostrarAjusteCosto ? 'true' : 'false' }}" aria-controls="ajuste-costo"><i class="bi bi-currency-dollar" aria-hidden="true"></i> Corregir costo</button>
                    @endif
                </div>
            </div>
        </form>

        @if($articulo)
        @if($ajustesCostoDisponible)
        <section id="ajuste-costo" class="collapse {{ $mostrarAjusteCosto ? 'show' : '' }} mt-3">
            <div class="border rounded p-3 bg-body-tertiary">
                <div class="row g-3 align-items-start mt-2">
                    <div class="col-lg-5">
                        <h2 class="h5 mb-1">Corregir costo vigente</h2>
                        <p class="small text-muted mb-0">Esta corrección no agrega ni retira existencias. Las salidas anteriores conservan el costo registrado en su fecha; las nuevas salidas usarán el costo vigente.</p>
                    </div>
                    <div class="col-lg-7">
                        <form method="POST" action="{{ route('inventario.articulos.costo.actualizar', $articulo) }}" class="row g-2">
                            @csrf
                            <div class="col-sm-5"><label for="costo_nuevo" class="form-label">Nuevo costo unitario</label><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.0001" min="0.0001" id="costo_nuevo" name="costo_nuevo" class="form-control" value="{{ old('costo_nuevo', $articulo->costo_promedio) }}" required></div><div class="form-text">Costo actual: ${{ number_format((float) $articulo->costo_promedio, 4) }}</div></div>
                            <div class="col-sm-7"><label for="motivo" class="form-label">Motivo de la corrección</label><textarea id="motivo" name="motivo" class="form-control" maxlength="500" rows="2" required>{{ old('motivo') }}</textarea></div>
                            <div class="col-12 text-end"><button type="submit" class="btn btn-warning" onclick="return confirm('¿Confirmas la corrección del costo? No se modificarán las existencias ni las salidas anteriores.')"><i class="bi bi-check-lg" aria-hidden="true"></i> Guardar nuevo costo</button></div>
                        </form>
                    </div>
                </div>

                @if($ajustesCosto->isNotEmpty())
                <div class="table-responsive mt-4">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Fecha</th><th class="text-end">Costo anterior</th><th class="text-end">Costo nuevo</th><th class="text-end">Existencia</th><th>Usuario</th><th>Motivo</th></tr></thead>
                        <tbody>@foreach($ajustesCosto as $ajuste)<tr><td class="text-nowrap">{{ $ajuste->fecha_ajuste?->format('d/m/Y H:i') }}</td><td class="text-end">${{ number_format((float) $ajuste->costo_anterior, 4) }}</td><td class="text-end fw-semibold">${{ number_format((float) $ajuste->costo_nuevo, 4) }}</td><td class="text-end">{{ number_format((float) $ajuste->existencia_actual, 3) }} {{ $articulo->unidad_medida }}</td><td>{{ $ajuste->usuario?->name ?? 'Usuario no disponible' }}</td><td>{{ $ajuste->motivo }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
                @endif
            </div>
        </section>
        @endif

        <hr class="my-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 mb-1">Existencias por ubicación</h2><p class="small text-muted mb-0">Los mínimos y máximos se administran por ubicación. Usa un movimiento para agregar stock o transferirlo.</p></div><a href="{{ route('inventario.movimientos.crear') }}" class="btn btn-outline-primary">Registrar movimiento</a></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Almacén</th><th>Ubicación</th><th class="text-end">Cantidad</th><th style="min-width:300px">Reglas de abastecimiento</th></tr></thead>
                <tbody>
                @forelse($existencias as $existencia)
                <tr>
                    <td><strong>{{ $existencia->ubicacion->almacen->nombre }}</strong><br><span class="small text-muted">{{ $existencia->ubicacion->almacen->seccion ?: 'Sin sección' }}</span></td>
                    <td>{{ $existencia->ubicacion->codigo }}<br><span class="small text-muted">{{ $existencia->ubicacion->anaquel }} · {{ $existencia->ubicacion->nivel }}</span></td>
                    <td class="text-end fw-semibold">{{ number_format((float) $existencia->cantidad, 3) }} <span class="small text-muted">{{ $articulo->unidad_medida }}</span></td>
                    <td><form method="POST" action="{{ route('inventario.existencias.reglas', $existencia) }}" class="d-flex gap-2">@csrf @method('PUT')<div><label class="visually-hidden" for="min-{{ $existencia->id }}">Mínimo</label><input type="number" step="0.001" min="0" id="min-{{ $existencia->id }}" name="stock_minimo" class="form-control form-control-sm" value="{{ $existencia->stock_minimo }}" placeholder="Mínimo" required></div><div><label class="visually-hidden" for="max-{{ $existencia->id }}">Máximo</label><input type="number" step="0.001" min="0" id="max-{{ $existencia->id }}" name="stock_maximo" class="form-control form-control-sm" value="{{ $existencia->stock_maximo }}" placeholder="Máximo"></div><button type="submit" class="btn btn-sm btn-outline-dark">Guardar</button></form></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">Sin existencias visibles.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
