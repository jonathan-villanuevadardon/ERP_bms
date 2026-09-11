@extends('layouts.app')

@section('title', 'Carga Masiva de Descansos')

@section('content')
<div class="mb-4">
    <a href="{{ route('descansos.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="h3 mt-2">Carga masiva de descansos</h1>
    <p class="text-muted">Primero se validará el archivo completo. Ningún registro se guardará hasta que confirmes la previsualización.</p>
</div>

<div class="row g-4">
    <div class="col-lg-7"><div class="card shadow-sm"><div class="card-body">
        <h2 class="h5">1. Prepara el archivo</h2>
        <a href="{{ route('descansos.plantilla') }}" class="btn btn-outline-success mb-4"><i class="bi bi-download"></i> Descargar plantilla XLSX</a>
        <h2 class="h5">2. Valida y previsualiza</h2>
        <form method="POST" action="{{ route('descansos.previsualizar') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="archivo" class="form-control mb-3" accept=".xlsx" required>
            <button class="btn btn-dark"><i class="bi bi-search"></i> Previsualizar</button>
        </form>
    </div></div></div>
    <div class="col-lg-5"><div class="card border-0 bg-body-secondary"><div class="card-body">
        <h2 class="h6">Reglas del archivo</h2>
        <ul class="mb-0">
            <li>Máximo 1,000 filas y 5 MB.</li>
            <li>Tipos válidos: <code>fijo</code> y <code>por_periodo</code>.</li>
            <li>Fechas escritas exactamente como <code>AAAA-MM-DD</code>.</li>
            <li>La fecha inicial no puede ser anterior a {{ now()->subDays(10)->format('d/m/Y') }}.</li>
            <li>La fecha final debe ser igual o posterior a la inicial.</li>
            <li>Los descansos fijos se enviarán a aprobación.</li>
        </ul>
    </div></div></div>
</div>
@endsection
