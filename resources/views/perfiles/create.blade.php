@extends('layouts.app')

@section('title', 'Nuevo Perfil')

@section('content')
<div class="mb-4">
    <a href="{{ route('perfiles.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Nuevo perfil</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('perfiles.store') }}">
            @include('perfiles._form')
        </form>
    </div>
</div>
@endsection