@extends('layouts.app')

@section('title', 'Préstamos de herramientas')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><h1 class="h3 mb-1"><i class="bi bi-tools text-warning" aria-hidden="true"></i> Préstamos de herramientas</h1><p class="text-muted mb-0">Salidas por almacén, resguardos, devoluciones y responsables.</p></div><div class="d-flex gap-2"><a href="{{ route('prestamos-herramientas.historial') }}" class="btn btn-outline-dark"><i class="bi bi-clock-history" aria-hidden="true"></i> Historial</a><a href="{{ route('prestamos-herramientas.crear') }}" class="btn btn-warning"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Nueva salida</a></div></div>

<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-lg-2"><label for="q" class="form-label small">Folio o herramienta</label><input id="q" name="q" class="form-control" value="{{ $filtros['q'] ?? '' }}"></div>
        <div class="col-lg-3"><label for="trabajador" class="form-label small">Trabajador</label><input id="trabajador" name="trabajador" class="form-control" value="{{ $filtros['trabajador'] ?? '' }}" placeholder="Número o nombre"></div>
        <div class="col-sm-4 col-lg-2"><label for="almacen" class="form-label small">Almacén</label><select id="almacen" name="almacen" class="form-select"><option value="">Todos</option>@foreach($almacenes as $almacen)<option value="{{ $almacen->id }}" @selected((string) ($filtros['almacen'] ?? '') === (string) $almacen->id)>{{ $almacen->codigo }}</option>@endforeach</select></div>
        <div class="col-sm-4 col-lg-2"><label for="estado" class="form-label small">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option>@foreach(['ACTIVO','PARCIAL','DEVUELTO'] as $estado)<option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ $estado }}</option>@endforeach</select></div>
        <div class="col-sm-4 col-lg-2"><label for="tipo" class="form-label small">Asignación</label><select id="tipo" name="tipo" class="form-select"><option value="">Todas</option><option value="PRESTAMO" @selected(($filtros['tipo'] ?? '') === 'PRESTAMO')>Préstamo</option><option value="RESGUARDO" @selected(($filtros['tipo'] ?? '') === 'RESGUARDO')>Resguardo</option></select></div>
        <div class="col-lg-1"><button type="submit" class="btn btn-dark w-100">Filtrar</button></div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-dark"><tr><th>Folio / salida</th><th>Herramienta</th><th>Almacén / ubicación</th><th>Trabajador</th><th>Asignación</th><th class="text-end">Pendiente</th><th>Estado</th><th style="min-width:260px">Recepción</th></tr></thead><tbody>
    @forelse($prestamos as $prestamo)
    @php($pendiente = (float) $prestamo->cantidad_entregada - (float) $prestamo->cantidad_devuelta)
    <tr>
        <td><strong>{{ $prestamo->folio }}</strong><br><span class="small text-muted">{{ $prestamo->fecha_salida?->format('d/m/Y H:i') }}</span></td>
        <td><strong>{{ $prestamo->articulo?->sku }}</strong><br>{{ $prestamo->articulo?->nombre }}@if($prestamo->articulo?->numero_serie)<br><span class="small text-muted">Serie {{ $prestamo->articulo->numero_serie }}</span>@endif</td>
        <td><strong>{{ $prestamo->ubicacion?->almacen?->codigo }}</strong><br>{{ $prestamo->ubicacion?->codigo }}<br><span class="small text-muted">{{ $prestamo->ubicacion?->almacen?->seccion }}</span></td>
        <td><strong>#{{ $prestamo->numero_empleado }} {{ $prestamo->empleado_nombre }}</strong><br><span class="small text-muted">{{ $prestamo->empleado_area }} · {{ $prestamo->empleado_seccion }}</span></td>
        <td><span class="badge {{ $prestamo->tipo_asignacion === 'RESGUARDO' ? 'text-bg-info' : 'text-bg-secondary' }}">{{ $prestamo->tipo_asignacion }}</span>@if($prestamo->fecha_compromiso)<br><span class="small text-muted">Compromiso {{ $prestamo->fecha_compromiso->format('d/m/Y') }}</span>@endif</td>
        <td class="text-end">{{ number_format($pendiente, 3) }}</td>
        <td><span class="badge {{ $prestamo->estado === 'DEVUELTO' ? 'text-bg-success' : ($prestamo->estado === 'PARCIAL' ? 'text-bg-warning' : 'text-bg-primary') }}">{{ $prestamo->estado }}</span></td>
        <td>@if($pendiente > 0)<form method="POST" action="{{ route('prestamos-herramientas.devolver', $prestamo) }}" class="d-flex gap-2">@csrf<input type="number" name="cantidad" min="0.001" max="{{ $pendiente }}" step="0.001" value="{{ $pendiente }}" class="form-control form-control-sm" required><input name="observaciones" class="form-control form-control-sm" maxlength="500" placeholder="Condición / nota"><button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Registrar esta recepción en {{ $prestamo->ubicacion?->codigo }}?')">Recibir</button></form>@else<span class="small text-muted">Recibido {{ $prestamo->fecha_devolucion?->format('d/m/Y H:i') }}</span>@endif</td>
    </tr>
    @empty<tr><td colspan="8" class="text-center text-muted py-5">No hay préstamos con estos filtros.</td></tr>@endforelse
    </tbody></table></div>
    @if($prestamos->hasPages())<div class="card-footer bg-white">{{ $prestamos->links() }}</div>@endif
</div>
@endsection
