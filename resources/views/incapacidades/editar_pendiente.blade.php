@extends('layouts.app')

@section('title', 'Editar Incapacidad Pendiente')

@section('content')
<div class="mb-4"><a href="{{ route('incapacidades.pendientes') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a><h1 class="h3 mt-2">Editar incapacidad pendiente</h1></div>
<div class="card shadow-sm"><div class="card-body"><form method="POST" action="{{ route('incapacidades.actualizar_pendiente', $pendiente) }}">@include('incapacidades._form')</form></div></div>
@endsection
