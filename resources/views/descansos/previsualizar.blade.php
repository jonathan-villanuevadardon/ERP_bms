@extends('layouts.app')

@section('title', 'Previsualizar Descansos')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div><h1 class="h3 mb-1">Confirma la carga</h1><p class="text-muted mb-0">Se validaron {{ count($filas) }} filas. Revisa la información antes de guardarla.</p></div>
    <a href="{{ route('descansos.importar') }}" class="btn btn-outline-secondary">Cancelar</a>
</div>

<div class="card shadow-sm mb-3"><div class="table-responsive"><table class="table table-hover table-sm mb-0">
    <thead class="table-dark"><tr><th>Fila</th><th>N° empleado</th><th>Nombre</th><th>Sección</th><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Observaciones</th></tr></thead>
    <tbody>@foreach($filas as $indice => $fila)<tr>
        <td>{{ $indice + 2 }}</td><td>{{ $fila['clave'] }}</td><td>{{ $fila['nombre_completo'] }}</td><td>{{ $fila['seccion'] }}</td>
        <td><span class="badge {{ $fila['tipo'] === 'fijo' ? 'bg-primary' : 'bg-secondary' }}">{{ $fila['tipo'] }}</span></td>
        <td>{{ $fila['fecha_inicio'] }}</td><td>{{ $fila['fecha_fin'] }}</td><td>{{ $fila['observaciones'] }}</td>
    </tr>@endforeach</tbody>
</table></div></div>

<form method="POST" action="{{ route('descansos.confirmar_importacion') }}" onsubmit="this.querySelector('button').disabled=true">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <button class="btn btn-success"><i class="bi bi-check-lg"></i> Confirmar e importar {{ count($filas) }} filas</button>
</form>
@endsection
