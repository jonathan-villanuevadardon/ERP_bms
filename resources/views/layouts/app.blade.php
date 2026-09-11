<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ERP BMS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    @auth
    {{-- Encabezado / barra de navegación superior --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-building"></i> ERP BMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navPrincipal">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Inicio</a>
                    </li>
                    @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_asignar_rol)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('roles.index') }}">Asignación de Rol</a>
                    </li>
                    @endif
                    @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_descansos)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('descansos.index') }}">Descansos</a>
                    </li>
                    @endif
                    @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_vacaciones)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('vacaciones.index') }}">Vacaciones</a>
                    </li>
                    @endif
                    @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_permisos)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('permisos.index') }}">Permisos</a>
                    </li>
                    @endif
                    @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_incapacidades)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('incapacidades.index') }}">Incapacidades</a>
                    </li>
                    @endif
                    @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_ver_visor)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('visor.index') }}">Visor</a>
                    </li>
                    @endif
                    @if(auth()->user()->esAdmin() || auth()->user()->perfil->puede_ver_lista_asistencia)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('lista_asistencia.index') }}">Lista de Asistencia</a>
                    </li>
                    @endif
                    @if(auth()->user()->esAdmin())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Administración
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('perfiles.index') }}">Perfiles</a></li>
                            <li><a class="dropdown-item" href="{{ route('usuarios.index') }}">Usuarios</a></li>
                        </ul>
                    </li>
                    @endif
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Cerrar sesión</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @endauth

    {{-- Contenido principal --}}
    <main class="container-fluid px-4">
        {{-- Mensajes flash (éxito / error) --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
