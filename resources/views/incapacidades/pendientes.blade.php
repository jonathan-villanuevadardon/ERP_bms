@extends('layouts.app')

@section('title', 'Incapacidades Pendientes')

@section('content')
<div class="mb-4"><a href="{{ (auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_incapacidades) ? route('incapacidades.index') : route('dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a><h1 class="h3 mt-2">Aprobación de incapacidades</h1></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead class="table-dark"><tr><th>Empleado</th><th>Tipo</th><th>Folio</th><th>Periodo</th><th>Días</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    @forelse($pendientes as $p)
    <tr>
        <td><strong>#{{ $p->clave }}</strong><br><span class="small">{{ $p->nombre_completo }}</span></td>
        <td>{{ $tipos[$p->tipo] ?? $p->tipo }}</td><td>{{ $p->folio }}</td>
        <td>{{ $p->fecha_inicio->format('d/m/Y') }} - {{ $p->fecha_fin->format('d/m/Y') }}</td><td>{{ $p->dias }}</td>
        <td class="text-end text-nowrap">
            @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_incapacidades)
            <a href="{{ route('incapacidades.editar_pendiente', $p) }}" class="btn btn-sm btn-outline-primary">Editar</a>
            <form method="POST" action="{{ route('incapacidades.eliminar_pendiente', $p) }}" class="d-inline" onsubmit="return confirm('¿Eliminar la solicitud?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Eliminar</button></form>
            @endif
            @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_aprobar)
            <form method="POST" action="{{ route('incapacidades.aprobar', $p) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Aprobar</button></form>
            <form method="POST" action="{{ route('incapacidades.rechazar', $p) }}" class="d-inline" onsubmit="return confirm('¿Rechazar la solicitud?')">@csrf<button class="btn btn-sm btn-outline-danger">Rechazar</button></form>
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="6" class="text-center text-muted py-4">No hay incapacidades pendientes.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
