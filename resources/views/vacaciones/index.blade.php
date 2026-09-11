@extends('layouts.app')

@section('title', 'Vacaciones')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Vacaciones</h1>
    <div>
        <a href="{{ route('vacaciones.pendientes') }}" class="btn btn-outline-warning me-2">
            <i class="bi bi-bell"></i> Pendientes
        </a>
        <a href="{{ route('vacaciones.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Agregar vacaciones
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>N° Empleado</th>
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vacaciones as $v)
                <tr>
                    <td>{{ $v->clave }}</td>
                    <td>{{ $v->nombre_completo }}</td>
                    <td>{{ $v->area }}</td>
                    <td>{{ $v->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $v->fecha_fin->format('d/m/Y') }}</td>
                    <td class="small text-muted">{{ Str::limit($v->observaciones, 40) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No hay vacaciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $vacaciones->links() }}</div>
</div>
@endsection
