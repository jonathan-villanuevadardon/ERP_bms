@extends('layouts.app')

@section('title', 'Almacenes y ubicaciones')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-secondary mb-2">Volver al inventario</a>
        <h1 class="h3 mb-1"><i class="bi bi-buildings text-primary" aria-hidden="true"></i> Almacenes y ubicaciones</h1>
        <p class="text-muted mb-0">Cada almacén pertenece a una sección vigente de HC y contiene sus anaqueles y niveles.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card shadow-sm sticky-xl-top" style="top: 1rem">
            <div class="card-header bg-primary text-white"><h2 class="h5 mb-0">Agregar almacén</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventario.almacenes.guardar') }}">
                    @csrf
                    <div class="mb-3"><label for="codigo" class="form-label">Código</label><input id="codigo" name="codigo" class="form-control" maxlength="50" value="{{ old('codigo') }}" placeholder="Ej. ALM-PLANTA" required></div>
                    <div class="mb-3"><label for="nombre" class="form-label">Nombre</label><input id="nombre" name="nombre" class="form-control" maxlength="150" value="{{ old('nombre') }}" placeholder="Almacén de planta" required></div>
                    <div class="mb-3"><label for="seccion" class="form-label">Sección HC</label><select id="seccion" name="seccion" class="form-select" required><option value="">Selecciona una sección</option>@foreach($secciones as $seccion)<option value="{{ $seccion }}" @selected(old('seccion') === $seccion)>{{ $seccion }}</option>@endforeach</select><div class="form-text">La lista se obtiene dinámicamente de HC. Los usuarios solo verán almacenes de sus secciones autorizadas.</div></div>
                    <div class="mb-3"><label for="descripcion" class="form-label">Descripción</label><textarea id="descripcion" name="descripcion" class="form-control" maxlength="255" rows="3">{{ old('descripcion') }}</textarea></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg" aria-hidden="true"></i> Crear almacén</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        @forelse($almacenes as $almacen)
        @php($existencias = $almacen->ubicaciones->flatMap->existencias)
        <section class="card shadow-sm mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 {{ $almacen->activo ? 'bg-dark text-white' : 'bg-secondary text-white' }}">
                <div><h2 class="h5 mb-0">{{ $almacen->codigo }} · {{ $almacen->nombre }}</h2><div class="small opacity-75">Sección HC: {{ $almacen->seccion ?: 'Sin sección (legado)' }}</div></div>
                <div class="d-flex gap-2"><span class="badge text-bg-light">{{ $almacen->ubicaciones->count() }} ubicaciones</span><span class="badge text-bg-light">{{ $existencias->where('cantidad', '>', 0)->count() }} existencias activas</span></div>
            </div>
            <div class="card-body">
                @if($almacen->codigo !== 'LEGACY')
                <details class="mb-4">
                    <summary class="btn btn-sm btn-outline-secondary">Editar datos del almacén</summary>
                    <form method="POST" action="{{ route('inventario.almacenes.actualizar', $almacen) }}" class="row g-2 mt-2 p-3 bg-body-tertiary rounded">
                        @csrf @method('PUT')
                        <div class="col-md-3"><label class="form-label small" for="codigo-{{ $almacen->id }}">Código</label><input id="codigo-{{ $almacen->id }}" name="codigo" class="form-control" value="{{ $almacen->codigo }}" required></div>
                        <div class="col-md-5"><label class="form-label small" for="nombre-{{ $almacen->id }}">Nombre</label><input id="nombre-{{ $almacen->id }}" name="nombre" class="form-control" value="{{ $almacen->nombre }}" required></div>
                        <div class="col-md-4"><label class="form-label small" for="seccion-{{ $almacen->id }}">Sección HC</label><select id="seccion-{{ $almacen->id }}" name="seccion" class="form-select" required>@if(!in_array($almacen->seccion, $secciones, true))<option value="{{ $almacen->seccion }}" selected>{{ $almacen->seccion }} (ya no vigente)</option>@endif @foreach($secciones as $seccion)<option value="{{ $seccion }}" @selected($almacen->seccion === $seccion)>{{ $seccion }}</option>@endforeach</select></div>
                        <div class="col-md-8"><label class="form-label small" for="descripcion-{{ $almacen->id }}">Descripción</label><input id="descripcion-{{ $almacen->id }}" name="descripcion" class="form-control" value="{{ $almacen->descripcion }}"></div>
                        <div class="col-md-2 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="hidden" name="activo" value="0"><input class="form-check-input" type="checkbox" id="activo-{{ $almacen->id }}" name="activo" value="1" @checked($almacen->activo)><label class="form-check-label" for="activo-{{ $almacen->id }}">Activo</label></div></div>
                        <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-dark w-100">Guardar</button></div>
                    </form>
                </details>
                @endif

                <div class="row g-3">
                    @forelse($almacen->ubicaciones as $ubicacion)
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between gap-2"><strong>{{ $ubicacion->codigo }}</strong><span class="badge text-bg-secondary">{{ $ubicacion->existencias->count() }} SKU</span></div>
                            <div>{{ $ubicacion->anaquel }} · Nivel {{ $ubicacion->nivel }}</div>
                            <div class="small text-muted">{{ $ubicacion->descripcion ?: 'Sin descripción' }}</div>
                            @if($ubicacion->existencias->where('cantidad', '>', 0)->isNotEmpty())<div class="small mt-2">Con stock: {{ $ubicacion->existencias->where('cantidad', '>', 0)->count() }} artículos</div>@endif
                        </div>
                    </div>
                    @empty
                    <div class="col-12"><div class="alert alert-secondary mb-0">Este almacén aún no tiene ubicaciones.</div></div>
                    @endforelse
                </div>

                @if($almacen->activo)
                <form method="POST" action="{{ route('inventario.ubicaciones.guardar', $almacen) }}" class="row g-2 align-items-end border-top mt-4 pt-3">
                    @csrf
                    <div class="col-sm-6 col-lg-3"><label for="ubic-codigo-{{ $almacen->id }}" class="form-label small">Código ubicación</label><input id="ubic-codigo-{{ $almacen->id }}" name="codigo" class="form-control" placeholder="A-01-N1" required></div>
                    <div class="col-sm-6 col-lg-3"><label for="anaquel-{{ $almacen->id }}" class="form-label small">Anaquel</label><input id="anaquel-{{ $almacen->id }}" name="anaquel" class="form-control" required></div>
                    <div class="col-sm-6 col-lg-2"><label for="nivel-{{ $almacen->id }}" class="form-label small">Nivel</label><input id="nivel-{{ $almacen->id }}" name="nivel" class="form-control" required></div>
                    <div class="col-sm-6 col-lg-2"><label for="ubic-desc-{{ $almacen->id }}" class="form-label small">Descripción</label><input id="ubic-desc-{{ $almacen->id }}" name="descripcion" class="form-control"></div>
                    <div class="col-lg-2"><button type="submit" class="btn btn-outline-primary w-100">Agregar ubicación</button></div>
                </form>
                @endif
            </div>
        </section>
        @empty
        <div class="alert alert-info">No hay almacenes disponibles para tus secciones.</div>
        @endforelse
    </div>
</div>
@endsection
