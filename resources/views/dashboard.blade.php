@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Bienvenido, {{ $user->name }}</h1>
</div>

<div class="row g-3">
    @if($user->esAdmin() || $user->perfil->puede_asignar_rol)
    <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-person-badge"></i> Asignación de Rol</h5>
                <p class="card-text text-muted small">Asigna roles de descanso de forma masiva o individual.</p>
                <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-dark">Ir al módulo</a>
            </div>
        </div>
    </div>
    @endif

    @if($user->esAdmin() || $user->perfil->puede_gestionar_descansos)
    <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-moon"></i> Descansos</h5>
                <p class="card-text text-muted small">Administra descansos fijos y por periodo.</p>
                <a href="{{ route('descansos.index') }}" class="btn btn-sm btn-outline-dark">Ir al módulo</a>
            </div>
        </div>
    </div>
    @endif

    @if($user->esAdmin() || $user->perfil->puede_gestionar_vacaciones)
    <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-sun"></i> Vacaciones</h5>
                <p class="card-text text-muted small">Solicita y aprueba periodos de vacaciones.</p>
                <a href="{{ route('vacaciones.index') }}" class="btn btn-sm btn-outline-dark">Ir al módulo</a>
            </div>
        </div>
    </div>
    @endif

    @if($user->esAdmin() || $user->perfil->puede_gestionar_permisos)
    <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-file-earmark-check"></i> Permisos</h5>
                <p class="card-text text-muted small">Registra permisos con o sin goce de sueldo.</p>
                <a href="{{ route('permisos.index') }}" class="btn btn-sm btn-outline-dark">Ir al módulo</a>
            </div>
        </div>
    </div>
    @endif

    @if($user->esAdmin() || $user->perfil->puede_ver_visor)
    <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-graph-up"></i> Visor de cumplimiento</h5>
                <p class="card-text text-muted small">Consulta si se cumple el rol asignado.</p>
                <a href="{{ route('visor.index') }}" class="btn btn-sm btn-outline-dark">Ir al visor</a>
            </div>
        </div>
    </div>
    @endif
</div>

@if($user->esAdmin())
<div class="card shadow-sm mt-4 border-warning">
    <div class="card-body">
        <h5 class="card-title text-warning"><i class="bi bi-bell"></i> Pendientes de aprobación</h5>
        <div class="row text-center mt-2">
            <div class="col">
                <div class="fs-4">{{ $pendientesDescansos }}</div>
                <small class="text-muted">Descansos fijos</small>
                <div class="mt-1"><a href="{{ route('descansos.pendientes') }}" class="btn btn-sm btn-outline-warning">Revisar</a></div>
            </div>
            <div class="col">
                <div class="fs-4">{{ $pendientesVacaciones }}</div>
                <small class="text-muted">Vacaciones</small>
                <div class="mt-1"><a href="{{ route('vacaciones.pendientes') }}" class="btn btn-sm btn-outline-warning">Revisar</a></div>
            </div>
            <div class="col">
                <div class="fs-4">{{ $pendientesPermisos }}</div>
                <small class="text-muted">Permisos con goce</small>
                <div class="mt-1"><a href="{{ route('permisos.pendientes') }}" class="btn btn-sm btn-outline-warning">Revisar</a></div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection