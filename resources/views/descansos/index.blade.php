@extends('layouts.app')

@section('title', 'Descansos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Descansos</h1>
    <div>
        @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_cargas_masivas)
        <a href="{{ route('descansos.importar') }}" class="btn btn-outline-success me-2"><i class="bi bi-file-earmark-excel"></i> Carga masiva</a>
        @endif
        <a href="{{ route('descansos.pendientes') }}" class="btn btn-outline-warning me-2">
            <i class="bi bi-bell"></i> Pendientes
        </a>
        <a href="{{ route('descansos.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Agregar descanso
        </a>
    </div>
</div>

<form method="GET" action="{{ route('descansos.index') }}" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-8 col-lg-4">
            <label for="clave" class="form-label">Número de empleado</label>
            <input type="number" id="clave" name="clave" class="form-control" min="1" max="9223372036854775807" value="{{ $filtros['clave'] ?? '' }}" placeholder="Ej. 12345">
        </div>
        <div class="col-sm-4 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark flex-fill"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
            <a href="{{ route('descansos.index') }}" class="btn btn-outline-secondary" title="Limpiar filtro" aria-label="Limpiar filtro"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
</form>

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
                    <th>Observaciones</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($descansos as $d)
                <tr>
                    <td>{{ $d->clave }}</td>
                    <td>{{ $d->nombre_completo }}</td>
                    <td>{{ $d->area }}</td>
                    <td>
                        <span class="badge {{ $d->tipo === 'fijo' ? 'bg-primary' : 'bg-secondary' }}">
                            {{ $d->tipo === 'fijo' ? 'Fijo' : 'Por periodo' }}
                        </span>
                    </td>
                    <td>{{ $d->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $d->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                    <td class="small text-muted">{{ Str::limit($d->observaciones, 40) }}</td>
                    <td class="text-end">
                        <a href="{{ route('descansos.edit', $d) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        <form method="POST" action="{{ route('descansos.destroy', $d) }}" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ isset($filtros['clave']) ? 'No hay descansos para este número de empleado.' : 'No hay descansos registrados.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $descansos->links() }}</div>
</div>
@endsection
