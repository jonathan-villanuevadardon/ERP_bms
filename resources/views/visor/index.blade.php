@extends('layouts.app')

@section('title', 'Visor de Cumplimiento')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Visor de cumplimiento de rol</h1>
    @if(auth()->user()->esAdmin())
    <form method="POST" action="{{ route('visor.refrescar') }}">
        @csrf
        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Ejecutar el SP de emergencia para sincronizar la tabla de hechos de asistencia ahora mismo?')">
            <i class="bi bi-arrow-repeat"></i> Ejecutar SP (emergencia)
        </button>
    </form>
    @endif
</div>

<form method="GET" action="{{ route('visor.index') }}" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="seccion" class="form-select">
            <option value="">Todas las secciones</option>
            @foreach($secciones as $s)
                <option value="{{ $s }}" @selected($seccion === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-outline-dark w-100">Filtrar</button>
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
                    <th>Sección</th>
                    <th>Rol (T x D)</th>
                    <th>Días trabajados</th>
                    <th>Descansos esperados</th>
                    <th>Bloque actual</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filas as $f)
                <tr>
                    <td>{{ $f['clave'] }}</td>
                    <td>{{ $f['nombre_completo'] }}</td>
                    <td>{{ $f['area'] }}</td>
                    <td><span class="badge bg-secondary">{{ $f['seccion'] }}</span></td>
                    <td><strong>{{ $f['dias_trabajo_rol'] }} x {{ $f['dias_descanso_rol'] }}</strong></td>
                    <td>{{ $f['dias_trabajados'] }}</td>
                    <td>{{ $f['dias_descanso_esperados'] }}</td>
                    <td>{{ $f['bloque_actual'] }} / {{ $f['dias_trabajo_rol'] }}</td>
                    <td>
                        <span class="badge {{ $f['cumple'] ? 'bg-success' : 'bg-danger' }}">
                            {{ $f['cumple'] ? 'Cumple' : 'Revisar' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No hay roles asignados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="text-muted small mt-2">
    <i class="bi bi-info-circle"></i> El estado "Cumple" indica que el bloque de trabajo actual no excede el rol asignado. El refresco automático ocurre cada 5 horas; el botón rojo "Ejecutar SP (emergencia)" solo está disponible para el administrador y sincroniza la asistencia de inmediato.
</div>
@endsection