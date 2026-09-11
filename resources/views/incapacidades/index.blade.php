@extends('layouts.app')

@section('title', 'Incapacidades')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Incapacidades IMSS</h1>
        <p class="text-muted mb-0">Registros aprobados que alimentan la lista diaria de nómina.</p>
    </div>
    <div>
        <a href="{{ route('incapacidades.pendientes') }}" class="btn btn-outline-warning me-2"><i class="bi bi-bell"></i> Pendientes</a>
        <a href="{{ route('incapacidades.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> Registrar</a>
    </div>
</div>

<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Número de empleado exacto</label>
            <input type="number" name="clave" class="form-control" value="{{ request('clave') }}">
        </div>
        <div class="col-md-2"><button class="btn btn-outline-dark w-100">Filtrar</button></div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark"><tr><th>N° Empleado</th><th>Nombre</th><th>Tipo</th><th>Folio IMSS</th><th>Inicio</th><th>Fin</th><th>Días</th></tr></thead>
            <tbody>
                @forelse($incapacidades as $i)
                <tr>
                    <td>{{ $i->clave }}</td><td>{{ $i->nombre_completo }}</td>
                    <td>{{ $tipos[$i->tipo] ?? $i->tipo }}</td><td>{{ $i->folio }}</td>
                    <td>{{ $i->fecha_inicio->format('d/m/Y') }}</td><td>{{ $i->fecha_fin->format('d/m/Y') }}</td><td>{{ $i->dias }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay incapacidades aprobadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $incapacidades->links() }}</div>
</div>
@endsection
