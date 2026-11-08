@extends('layouts.app')

@section('title', 'Inventario por almacén')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-box-seam text-primary" aria-hidden="true"></i> Inventario por almacén</h1>
        <p class="text-muted mb-0">Existencias por ubicación, costos promedio, transferencias y alertas.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('inventario.almacenes') }}" class="btn btn-primary"><i class="bi bi-buildings" aria-hidden="true"></i> Almacenes y ubicaciones</a>
        <a href="{{ route('inventario.movimientos') }}" class="btn btn-outline-dark"><i class="bi bi-clock-history" aria-hidden="true"></i> Historial</a>
        <a href="{{ route('inventario.movimientos.crear') }}" class="btn btn-outline-primary"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Movimiento</a>
        <a href="{{ route('inventario.articulos.crear') }}" class="btn btn-outline-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Artículo</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Inventario físico</div><div class="fs-3 fw-semibold">{{ number_format($totalArticulos) }} <span class="fs-6 fw-normal text-muted">artículos</span></div>@foreach($totalesPorUnidad as $total)<div class="small text-muted">{{ number_format((float) $total->existencia_total, 3) }} {{ $total->unidad_medida }}</div>@endforeach</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Valor del inventario</div><div class="fs-3 fw-semibold">${{ number_format($valorTotal, 2) }}</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><a href="{{ route('inventario.index', ['alerta' => 'MINIMO']) }}" class="card shadow-sm h-100 text-decoration-none border-danger"><div class="card-body"><div class="text-danger small">Ubicaciones en mínimo</div><div class="fs-3 fw-semibold text-dark">{{ $alertasMinimo }}</div></div></a></div>
    <div class="col-sm-6 col-xl-3"><a href="{{ route('inventario.index', ['alerta' => 'MAXIMO']) }}" class="card shadow-sm h-100 text-decoration-none border-warning"><div class="card-body"><div class="text-warning-emphasis small">Ubicaciones en máximo</div><div class="fs-3 fw-semibold text-dark">{{ $alertasMaximo }}</div></div></a></div>
</div>

<form method="GET" action="{{ route('inventario.index') }}" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-lg-3"><label for="q" class="form-label small">Buscar</label><input id="q" name="q" class="form-control" value="{{ $filtros['q'] ?? '' }}" placeholder="SKU, nombre o número de serie"></div>
        <div class="col-sm-6 col-lg-2"><label for="tipo" class="form-label small">Tipo</label><select id="tipo" name="tipo" class="form-select"><option value="">Todos</option><option value="MATERIAL" @selected(($filtros['tipo'] ?? '') === 'MATERIAL')>Material</option><option value="HERRAMIENTA" @selected(($filtros['tipo'] ?? '') === 'HERRAMIENTA')>Herramienta</option></select></div>
        <div class="col-sm-6 col-lg-2"><label for="almacen" class="form-label small">Almacén</label><select id="almacen" name="almacen" class="form-select"><option value="">Todos</option>@foreach($almacenes as $almacen)<option value="{{ $almacen->id }}" @selected((string) ($filtros['almacen'] ?? '') === (string) $almacen->id)>{{ $almacen->codigo }} · {{ $almacen->nombre }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-2"><label for="ubicacion" class="form-label small">Ubicación</label><select id="ubicacion" name="ubicacion" class="form-select"><option value="">Todas</option>@foreach($almacenes as $almacen)@foreach($almacen->ubicaciones as $ubicacion)<option value="{{ $ubicacion->id }}" @selected((string) ($filtros['ubicacion'] ?? '') === (string) $ubicacion->id)>{{ $almacen->codigo }} / {{ $ubicacion->codigo }}</option>@endforeach @endforeach</select></div>
        <div class="col-sm-6 col-lg-1"><label for="alerta" class="form-label small">Alerta</label><select id="alerta" name="alerta" class="form-select"><option value="">Todas</option><option value="MINIMO" @selected(($filtros['alerta'] ?? '') === 'MINIMO')>Mínimo</option><option value="MAXIMO" @selected(($filtros['alerta'] ?? '') === 'MAXIMO')>Máximo</option></select></div>
        <div class="col-lg-2 d-flex gap-2"><button type="submit" class="btn btn-dark flex-fill">Filtrar</button><a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary" aria-label="Limpiar filtros" title="Limpiar"><i class="bi bi-x-lg" aria-hidden="true"></i></a></div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark"><tr><th>Artículo</th><th>Almacén / sección</th><th>Ubicación</th><th class="text-end">Existencia</th><th class="text-end">Costo promedio</th><th class="text-end">Valor</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse($existencias as $existencia)
                @php($articulo = $existencia->articulo)
                @php($bajo = (float) $existencia->cantidad <= (float) $existencia->stock_minimo)
                @php($alto = $existencia->stock_maximo !== null && (float) $existencia->cantidad >= (float) $existencia->stock_maximo)
                <tr>
                    <td><strong>{{ $articulo->sku }}</strong><br>{{ $articulo->nombre }}@if($articulo->numero_serie)<br><span class="small text-muted">Serie: {{ $articulo->numero_serie }}</span>@endif</td>
                    <td><strong>{{ $existencia->ubicacion->almacen->nombre }}</strong><br><span class="small text-muted">{{ $existencia->ubicacion->almacen->seccion ?: 'Inventario legado sin sección' }}</span></td>
                    <td><strong>{{ $existencia->ubicacion->codigo }}</strong><br><span class="small text-muted">{{ $existencia->ubicacion->anaquel }} · Nivel {{ $existencia->ubicacion->nivel }}</span></td>
                    <td class="text-end fw-semibold">{{ number_format((float) $existencia->cantidad, 3) }} <span class="small text-muted">{{ $articulo->unidad_medida }}</span></td>
                    <td class="text-end">${{ number_format((float) $articulo->costo_promedio, 4) }}</td>
                    <td class="text-end">${{ number_format((float) $existencia->cantidad * (float) $articulo->costo_promedio, 2) }}</td>
                    <td>@if($bajo)<span class="badge text-bg-danger">Mínimo</span>@elseif($alto)<span class="badge text-bg-warning">Máximo</span>@else<span class="badge text-bg-success">Normal</span>@endif</td>
                    <td class="text-end"><a href="{{ route('inventario.articulos.editar', $articulo) }}" class="btn btn-sm btn-outline-primary">Detalle</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5">No hay existencias con estos filtros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($existencias->hasPages())<div class="card-footer bg-white">{{ $existencias->links() }}</div>@endif
</div>
@endsection
