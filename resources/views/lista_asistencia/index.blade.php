@extends('layouts.app')

@section('title', 'Lista de Asistencia')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div><h1 class="h3 mb-1">Lista diaria de asistencia</h1><p class="text-muted mb-0">Clasificación consolidada para revisión de nómina.</p></div>
    <a href="{{ route('lista_asistencia.exportar', request()->query()) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
</div>

<form method="GET" action="{{ route('lista_asistencia.index') }}" class="card card-body shadow-sm mb-3">
    <div class="row g-3 align-items-end">
        <div class="col-sm-6 col-lg-2"><label class="form-label">Desde</label><input type="date" name="desde" class="form-control" value="{{ $filtros['desde'] }}" required></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Hasta</label><input type="date" name="hasta" class="form-control" value="{{ $filtros['hasta'] }}" max="{{ now()->format('Y-m-d') }}" required></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label">Sección</label><select name="seccion" class="form-select" required>@foreach($secciones as $s)<option value="{{ $s }}" @selected($filtros['seccion'] === $s)>{{ $s }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label">Número de empleado exacto</label><input type="number" name="clave" class="form-control" value="{{ $filtros['clave'] }}"></div>
        <div class="col-lg-2"><button class="btn btn-dark w-100">Consultar</button></div>
    </div>
    <div class="form-text mt-2">El periodo incluye ambas fechas y admite un máximo de 30 días.</div>
</form>

<div class="row g-2 mb-3">
    @foreach(['INCAPACIDAD' => 'danger', 'VACACIONES' => 'info', 'PERMISO' => 'warning', 'DESCANSO' => 'secondary', 'ASISTENCIA' => 'success', 'FALTA' => 'dark'] as $estado => $color)
    <div class="col-6 col-md-4 col-xl-2"><div class="card border-{{ $color }}"><div class="card-body py-2"><div class="small text-muted">{{ $estado }}</div><strong class="fs-4">{{ number_format((int) ($resumen[$estado] ?? 0)) }}</strong></div></div></div>
    @endforeach
</div>

<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover table-sm mb-0">
    <thead class="table-dark"><tr><th>Fecha</th><th>N° empleado</th><th>Nombre</th><th>Área / Cargo</th><th>Sección</th><th>Estatus nómina</th><th>Detalle</th></tr></thead>
    <tbody>
    @forelse($filas as $f)
    @php($colores = ['INCAPACIDAD' => 'danger', 'VACACIONES' => 'info', 'PERMISO' => 'warning', 'DESCANSO' => 'secondary', 'ASISTENCIA' => 'success', 'FALTA' => 'dark'])
    <tr>
        <td>{{ $f->fecha->format('d/m/Y') }}</td><td>{{ $f->clave }}</td><td>{{ $f->nombre_completo }}</td>
        <td>{{ $f->area }}<br><span class="small text-muted">{{ $f->cargo }}</span></td><td>{{ $f->seccion }}</td>
        <td><span class="badge bg-{{ $colores[$f->estatus_nomina] ?? 'secondary' }}">{{ $f->estatus_nomina }}</span></td>
        <td class="small">@if($f->tipo_incapacidad){{ str_replace('_', ' ', $f->tipo_incapacidad) }} · Folio {{ $f->folio_incapacidad }}@elseif($f->tipo_permiso){{ str_replace('_', ' ', $f->tipo_permiso) }}@endif</td>
    </tr>
    @empty
    <tr><td colspan="7" class="text-center text-muted py-4">No hay filas para los filtros seleccionados.</td></tr>
    @endforelse
    </tbody>
</table></div><div class="p-3">{{ $filas->links() }}</div></div>

<p class="text-muted small mt-2">Prioridad aplicada: incapacidad, vacaciones, permiso, descanso, asistencia y falta.</p>
@endsection
