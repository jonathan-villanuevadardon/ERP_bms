@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Usuarios</h1>
    <a href="{{ route('usuarios.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> Nuevo usuario</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>N° Empleado</th>
                    <th>Perfil</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->clave_empleado ?? '—' }}</td>
                    <td><span class="badge bg-secondary">{{ $u->perfil?->nombre ?? 'Sin perfil' }}</span></td>
                    <td>
                        <span class="badge {{ $u->activo ? 'bg-success' : 'bg-danger' }}">
                            {{ $u->activo ? 'Activo' : 'Suspendido' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        @if(!$u->esAdmin())
                        <form method="POST" action="{{ route('usuarios.destroy', $u) }}" class="d-inline" onsubmit="return confirm('¿Desactivar este usuario?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Desactivar</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection