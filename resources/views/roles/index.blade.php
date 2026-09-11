@extends('layouts.app')

@section('title', 'Asignación de Rol')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Asignación de Rol</h1>
    <div>
        <a href="{{ route('roles.plantilla') }}" class="btn btn-outline-dark me-2">
            <i class="bi bi-download"></i> Plantilla Excel
        </a>
        <a href="{{ route('roles.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Asignar individual
        </a>
    </div>
</div>

{{-- Carga masiva desde Excel --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title">Carga masiva (Excel)</h5>
        <form method="POST" action="{{ route('roles.importar') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Archivo .xlsx</label>
                <input type="file" name="archivo" class="form-control" accept=".xlsx" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-upload"></i> Procesar
                </button>
            </div>
        </form>
        <small class="text-muted">Descarga la plantilla, llénala y súbela aquí para asignar roles de forma masiva.</small>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('roles.index') }}" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="seccion" class="form-select">
            <option value="">Todas las secciones</option>
            @foreach($secciones as $s)
                <option value="{{ $s }}" @selected(request('seccion') === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <input type="text" name="termino" class="form-control" placeholder="Buscar por nombre, clave o cargo" value="{{ request('termino') }}">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-outline-dark w-100">Filtrar</button>
    </div>
</form>

{{-- Tabla de roles --}}
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>N° Empleado</th>
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Cargo</th>
                    <th>Sección</th>
                    <th>Rol (Trabajo x Descanso)</th>
                    <th>Vigencia</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $rol)
                <tr>
                    <td>{{ $rol->clave }}</td>
                    <td>{{ $rol->nombre_completo }}</td>
                    <td>{{ $rol->area }}</td>
                    <td>{{ $rol->cargo }}</td>
                    <td><span class="badge bg-secondary">{{ $rol->seccion }}</span></td>
                    <td><strong>{{ $rol->dias_trabajo }}</strong> x <strong>{{ $rol->dias_descanso }}</strong></td>
                    <td class="small">
                        @if($rol->fecha_inicio || $rol->fecha_fin)
                            {{ $rol->fecha_inicio?->format('d/m/Y') ?? '—' }} al {{ $rol->fecha_fin?->format('d/m/Y') ?? 'indefinido' }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('roles.edit', $rol) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        <form method="POST" action="{{ route('roles.destroy', $rol) }}" class="d-inline" onsubmit="return confirm('¿Eliminar este rol?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No hay roles asignados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">
        {{ $roles->links() }}
    </div>
</div>
@endsection