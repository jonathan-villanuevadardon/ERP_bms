@extends('layouts.app')

@section('title', 'Aprobación de Vacaciones')

@section('content')
<div class="mb-4">
    <a href="{{ (auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_vacaciones) ? route('vacaciones.index') : route('dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Aprobación de vacaciones</h1>
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
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendientes as $p)
                <tr>
                    <td>{{ $p->clave }}</td>
                    <td>{{ $p->nombre_completo }}</td>
                    <td>{{ $p->area }}</td>
                    <td>{{ $p->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $p->fecha_fin->format('d/m/Y') }}</td>
                    <td class="small text-muted">{{ Str::limit($p->observaciones, 40) }}</td>
                    <td class="text-end">
                        @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_vacaciones)
                        <a href="{{ route('vacaciones.editar_pendiente', $p) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        @endif
                        @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_aprobar)
                        <form method="POST" action="{{ route('vacaciones.aprobar', $p) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Aprobar</button>
                        </form>
                        <form method="POST" action="{{ route('vacaciones.rechazar', $p) }}" class="d-inline" onsubmit="return confirm('¿Rechazar?')">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger">Rechazar</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay vacaciones pendientes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
