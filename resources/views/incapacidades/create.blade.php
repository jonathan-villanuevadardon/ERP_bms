@extends('layouts.app')

@section('title', 'Registrar Incapacidad')

@section('content')
<div class="mb-4"><a href="{{ route('incapacidades.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a><h1 class="h3 mt-2">Registrar incapacidad IMSS</h1></div>

<div class="card shadow-sm mb-4"><div class="card-body">
    <form method="GET" action="{{ route('incapacidades.create') }}" class="row g-2">
        <div class="col-md-6"><input type="text" name="termino" class="form-control" placeholder="Buscar por nombre, clave o cargo" value="{{ request('termino') }}"></div>
        <div class="col-md-2"><button class="btn btn-outline-dark w-100">Buscar</button></div>
    </form>
    @if(!empty($empleados))
    <div class="list-group mt-3">
        @foreach($empleados as $e)
        <button type="button" class="list-group-item list-group-item-action" onclick="document.getElementById('clave').value='{{ $e['clave'] }}'">
            <strong>#{{ $e['clave'] }}</strong> {{ $e['nombre_completo'] }} <span class="text-muted small">{{ $e['area'] }} · {{ $e['seccion'] }}</span>
        </button>
        @endforeach
    </div>
    @endif
</div></div>

<div class="card shadow-sm"><div class="card-body"><form method="POST" action="{{ route('incapacidades.store') }}">@include('incapacidades._form')</form></div></div>
@endsection
