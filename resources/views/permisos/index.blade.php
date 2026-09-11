@extends('layouts.app')

@section('title', 'Permisos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Permisos</h1>
    <div>
        @if(auth()->user()->esAdmin())
        <a href="{{ route('permisos.pendientes') }}" class="btn btn-outline-warning me-2">
            <i class="bi bi-bell"></i> Aprobaciones
        </a>
        @endif
        <a href="{{ route('permisos.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Agregar permiso
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
                    <th>Tipo</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permisos as $p)
                <tr>
                    <td>{{ $p->clave }}</td>
                    <td>{{ $p->nombre_completo }}</td>
                    <td>{{ $p->area }}</td>
                    <td>
                        <span class="badge {{ $p->tipo === 'con_goce' ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $p->tipo === 'con_goce' ? 'Con goce' : 'Sin goce' }}
                        </span>
                    </td>
                    <td>{{ $p->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $p->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                    <td class="small text-muted">{{ Str::limit($p->motivo, 40) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay permisos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $permisos->links() }}</div>
</div>
@endsection