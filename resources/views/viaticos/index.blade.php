@extends('layouts.app')

@section('title', 'Viáticos')

@section('content')
@php($puedeGestionar = auth()->user()->esAdmin() || auth()->user()->perfil->puede_gestionar_viaticos)
@php($puedeAprobar = auth()->user()->esAdmin() || auth()->user()->perfil->puede_aprobar)
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Revisión de viáticos</h1>
        <p class="text-muted mb-0">Consulta boletos capturados y valida su pago.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('viaticos.crear') }}" class="btn btn-sm btn-primary">Capturar viático</a>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label for="estatus" class="small text-muted">Estatus</label>
            <select name="estatus" id="estatus" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Pendientes y aprobados</option>
                <option value="PENDIENTE_REVISION" @selected($estatusSeleccionado === 'PENDIENTE_REVISION')>Pendientes</option>
                <option value="APROBADO" @selected($estatusSeleccionado === 'APROBADO')>Aprobados</option>
                <option value="RECHAZADO" @selected($estatusSeleccionado === 'RECHAZADO')>Rechazados</option>
            </select>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <form method="POST" id="acciones-viaticos">
            @csrf
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        @if($puedeGestionar)<th class="text-center"><input class="form-check-input" type="checkbox" id="seleccionar-todos" title="Seleccionar pendientes"></th>@endif
                        <th>Folio / empleado</th>
                        <th>Boleto</th>
                        <th>Ruta y fecha</th>
                        <th class="text-end">Costo</th>
                        <th>Semana de pago</th>
                        <th>Estatus</th>
                        @if($puedeGestionar)<th class="text-end">Acciones</th>@endif
                    </tr>
                </thead>
                <tbody>
                @forelse($viaticos as $viatico)
                    @php($pendiente = $viatico->estatus_aprobacion === 'PENDIENTE_REVISION')
                    <tr>
                        @if($puedeGestionar)
                        <td class="text-center">
                            @if($pendiente)
                            <input class="form-check-input viatico-checkbox" type="checkbox" name="viaticos[]" value="{{ $viatico->id_viatico }}" data-ready="{{ filled($viatico->semana_pagar) ? 1 : 0 }}" aria-label="Seleccionar viático {{ $viatico->folio_ingreso }}">
                            @endif
                        </td>
                        @endif
                        <td><strong>{{ $viatico->folio_ingreso }}</strong><br><span class="small text-muted">#{{ $viatico->numero_empleado }} · {{ $viatico->nombre_persona }}</span></td>
                        <td><span class="text-uppercase small">{{ $viatico->tipo_transporte }}</span><br>{{ $viatico->transportista }}<br><span class="small text-muted">{{ $viatico->referencia_boleto ?: 'Sin referencia' }}</span></td>
                        <td>{{ $viatico->origen }} → {{ $viatico->destino }}<br><span class="small text-muted">{{ $viatico->fecha_salida?->format('d/m/Y') }}</span></td>
                        <td class="text-end">${{ number_format((float) $viatico->costo, 2) }}</td>
                        <td>{{ $viatico->semana_pagar ?: 'Sin asignar' }}</td>
                        <td>
                            @if($viatico->estatus_aprobacion === 'APROBADO')
                                <span class="badge text-bg-success">Aprobado</span>
                            @elseif($viatico->estatus_aprobacion === 'RECHAZADO')
                                <span class="badge text-bg-danger">Rechazado</span>
                            @else
                                <span class="badge text-bg-warning">Pendiente</span>
                            @endif
                        </td>
                        @if($puedeGestionar)
                        <td class="text-end text-nowrap">
                            <a href="{{ route('viaticos.editar', $viatico) }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $puedeGestionar ? 8 : 6 }}" class="text-center text-muted py-4">No hay viáticos para mostrar.</td></tr>
                @endforelse
                </tbody>
            </table>
            @if($puedeGestionar)
            <div class="d-flex flex-wrap justify-content-end gap-2 p-3 border-top">
                <button type="submit" formaction="{{ route('viaticos.rechazar', absolute: false) }}" class="btn btn-outline-danger" id="rechazar-seleccionados" disabled>Rechazar seleccionados</button>
                @if($puedeAprobar)
                <button type="submit" formaction="{{ route('viaticos.aprobar', absolute: false) }}" class="btn btn-success" id="aprobar-seleccionados" disabled>Aprobar seleccionados</button>
                @endif
            </div>
            @endif
        </form>
    </div>
</div>

<div class="mt-3">{{ $viaticos->links() }}</div>
@endsection

@push('scripts')
<script>
(() => {
    const all = document.getElementById('seleccionar-todos');
    const boxes = [...document.querySelectorAll('.viatico-checkbox')];
    const reject = document.getElementById('rechazar-seleccionados');
    const approve = document.getElementById('aprobar-seleccionados');
    const refresh = () => {
        const selected = boxes.filter((box) => box.checked);
        reject.disabled = selected.length === 0;
        if (approve) approve.disabled = selected.length === 0 || selected.some((box) => box.dataset.ready !== '1');
        if (all) all.checked = boxes.length > 0 && selected.length === boxes.length;
    };
    boxes.forEach((box) => box.addEventListener('change', refresh));
    all?.addEventListener('change', () => {
        boxes.forEach((box) => { box.checked = all.checked; });
        refresh();
    });
    document.getElementById('acciones-viaticos')?.addEventListener('submit', (event) => {
        const cantidad = boxes.filter((box) => box.checked).length;
        const accion = event.submitter?.id === 'aprobar-seleccionados' ? 'aprobar' : 'rechazar';
        if (!confirm(`¿${accion === 'aprobar' ? 'Aprobar' : 'Rechazar'} ${cantidad} viático(s) seleccionado(s)?`)) {
            event.preventDefault();
        }
    });
})();
</script>
@endpush
