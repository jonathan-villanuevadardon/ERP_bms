@extends('layouts.app')

@section('title', 'Perfiles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Perfiles</h1>
    <a href="{{ route('perfiles.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> Nuevo perfil</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Descripción</th>
                    <th>Asignar rol</th>
                    <th>Descansos</th>
                    <th>Vacaciones</th>
                    <th>Permisos</th>
                    <th>Aprobar</th>
                    <th>Secciones</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perfiles as $p)
                <tr>
                    <td>
                        {{ $p->nombre }}
                        @if($p->es_admin) <span class="badge bg-danger">ADMIN</span> @endif
                    </td>
                    <td><code>{{ $p->slug }}</code></td>
                    <td class="small text-muted">{{ Str::limit($p->descripcion, 40) }}</td>
                    <td>@if($p->puede_asignar_rol)<i class="bi bi-check text-success"></i>@endif</td>
                    <td>@if($p->puede_gestionar_descansos)<i class="bi bi-check text-success"></i>@endif</td>
                    <td>@if($p->puede_gestionar_vacaciones)<i class="bi bi-check text-success"></i>@endif</td>
                    <td>@if($p->puede_gestionar_permisos)<i class="bi bi-check text-success"></i>@endif</td>
                    <td>@if($p->puede_aprobar)<i class="bi bi-check text-success"></i>@endif</td>
                    <td>
                        @if(empty($p->secciones))
                            <span class="text-muted small">Todas</span>
                        @else
                            {{ implode(', ', $p->secciones) }}
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('perfiles.edit', $p) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        <form method="POST" action="{{ route('perfiles.destroy', $p) }}" class="d-inline" onsubmit="return confirm('¿Eliminar este perfil?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No hay perfiles.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection