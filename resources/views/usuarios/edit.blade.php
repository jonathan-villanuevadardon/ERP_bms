@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="mb-4">
    <a href="{{ route('usuarios.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Editar usuario: {{ $usuario->name }}</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('usuarios.update', $usuario) }}" id="usuario-form">
            @include('usuarios.form')
        </form>
    </div>
</div>
@endsection
