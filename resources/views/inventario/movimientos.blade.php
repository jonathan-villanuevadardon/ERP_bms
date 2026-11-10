@extends('layouts.app')

@section('title', 'Historial de inventario')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-secondary mb-2">Volver al inventario</a><h1 class="h3 mb-0">Historial de movimientos</h1></div><a href="{{ route('inventario.movimientos.crear') }}" class="btn btn-primary align-self-end">Nuevo movimiento</a></div>

<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-lg-3"><label class="form-label small" for="q">Artículo</label><input id="q" name="q" class="form-control" value="{{ $filtros['q'] ?? '' }}" placeholder="SKU o nombre"></div>
        <div class="col-lg-3"><label class="form-label small" for="trabajador">Trabajador</label><input id="trabajador" name="trabajador" class="form-control" value="{{ $filtros['trabajador'] ?? '' }}" placeholder="Número o nombre"></div>
        <div class="col-sm-4 col-lg-2"><label class="form-label small" for="tipo">Movimiento</label><select id="tipo" name="tipo" class="form-select"><option value="">Todos</option>@foreach(['ENTRADA','SALIDA','TRANSFERENCIA','AJUSTE_ENTRADA','AJUSTE_SALIDA','PRESTAMO','DEVOLUCION'] as $tipo)<option value="{{ $tipo }}" @selected(($filtros['tipo'] ?? '') === $tipo)>{{ str_replace('_', ' ', $tipo) }}</option>@endforeach</select></div>
        <div class="col-sm-4 col-lg-2"><label class="form-label small" for="almacen">Almacén</label><select id="almacen" name="almacen" class="form-select"><option value="">Todos</option>@foreach($almacenes as $almacen)<option value="{{ $almacen->id }}" @selected((string) ($filtros['almacen'] ?? '') === (string) $almacen->id)>{{ $almacen->codigo }}</option>@endforeach</select></div>
        <div class="col-sm-4 col-lg-2"><button type="submit" class="btn btn-dark w-100">Filtrar</button></div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead class="table-dark"><tr><th>Fecha</th><th>Artículo</th><th>Movimiento</th><th>Origen</th><th>Destino</th><th class="text-end">Cantidad</th><th class="text-end">Existencia total</th><th>Trabajador / referencia</th></tr></thead>
            <tbody>
            @forelse($movimientos as $movimiento)
            <tr>
                <td class="text-nowrap">{{ $movimiento->fecha_movimiento?->format('d/m/Y H:i') }}</td>
                <td><strong>{{ $movimiento->articulo?->sku }}</strong><br>{{ $movimiento->articulo?->nombre }}</td>
                <td><span class="badge text-bg-secondary">{{ str_replace('_', ' ', $movimiento->tipo) }}</span><br><span class="small text-muted">{{ $movimiento->observaciones }}</span></td>
                <td>@if($movimiento->ubicacionOrigen)<strong>{{ $movimiento->ubicacionOrigen->almacen?->codigo }}</strong><br><span class="small">{{ $movimiento->ubicacionOrigen->codigo }}</span><br><span class="small text-muted">{{ number_format((float) $movimiento->existencia_origen_anterior, 3) }} → {{ number_format((float) $movimiento->existencia_origen_nueva, 3) }} {{ $movimiento->articulo?->unidad_medida }}</span>@else<span class="text-muted">Externo</span>@endif</td>
                <td>@if($movimiento->ubicacionDestino)<strong>{{ $movimiento->ubicacionDestino->almacen?->codigo }}</strong><br><span class="small">{{ $movimiento->ubicacionDestino->codigo }}</span><br><span class="small text-muted">{{ number_format((float) $movimiento->existencia_destino_anterior, 3) }} → {{ number_format((float) $movimiento->existencia_destino_nueva, 3) }} {{ $movimiento->articulo?->unidad_medida }}</span>@else<span class="text-muted">Externo</span>@endif</td>
                <td class="text-end">{{ number_format((float) $movimiento->cantidad, 3) }} <span class="small text-muted">{{ $movimiento->articulo?->unidad_medida }}</span></td>
                <td class="text-end">{{ number_format((float) $movimiento->existencia_anterior, 3) }} → {{ number_format((float) $movimiento->existencia_nueva, 3) }} <span class="small text-muted">{{ $movimiento->articulo?->unidad_medida }}</span></td>
                <td>@if($movimiento->numero_empleado)<strong>#{{ $movimiento->numero_empleado }} {{ $movimiento->empleado_nombre }}</strong><br><span class="small text-muted">{{ $movimiento->empleado_seccion }}</span>@endif @if($movimiento->referencia)<div class="small">Ref. {{ $movimiento->referencia }}</div>@endif</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-5">No hay movimientos con estos filtros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($movimientos->hasPages())<div class="card-footer bg-white">{{ $movimientos->links() }}</div>@endif
</div>
@endsection
