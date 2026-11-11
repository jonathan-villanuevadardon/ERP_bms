<?php

namespace App\Http\Controllers;

use App\Models\AlmacenAlmacen;
use App\Models\AlmacenExistencia;
use App\Models\AlmacenUbicacion;
use App\Services\SeccionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AlmacenConfiguracionController extends Controller
{
    public function index(Request $request)
    {
        $almacenes = AlmacenAlmacen::query()
            ->visiblesPara($request->user())
            ->with(['ubicaciones' => function ($query) {
                $query->with(['existencias.articulo'])->orderBy('anaquel')->orderBy('nivel');
            }])
            ->orderBy('nombre')
            ->get();

        return view('inventario.almacenes', [
            'almacenes' => $almacenes,
            'secciones' => SeccionService::disponiblesPara($request->user()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validarAlmacen($request);
        SeccionService::autorizar($request->user(), $data['seccion']);
        AlmacenAlmacen::query()->create([...$data, 'activo' => true]);

        return redirect()->route('inventario.almacenes')->with('success', 'Almacén registrado correctamente.');
    }

    public function update(Request $request, AlmacenAlmacen $almacen)
    {
        SeccionService::autorizar($request->user(), $almacen->seccion);
        $data = $this->validarAlmacen($request, $almacen);
        SeccionService::autorizar($request->user(), $data['seccion']);
        if ($data['seccion'] !== $almacen->seccion) {
            throw ValidationException::withMessages([
                'seccion' => 'La sección de un almacén no puede cambiarse. Crea otro almacén para la nueva sección.',
            ]);
        }
        $data['activo'] = $request->boolean('activo');

        if (! $data['activo']) {
            $tieneStock = AlmacenExistencia::query()
                ->whereHas('ubicacion', fn ($query) => $query->where('almacen_id', $almacen->id))
                ->where('cantidad', '>', 0)
                ->exists();
            $tienePrestamos = $almacen->ubicaciones()
                ->whereHas('prestamos', fn ($query) => $query->whereIn('estado', ['ACTIVO', 'PARCIAL']))
                ->exists();
            if ($tieneStock || $tienePrestamos) {
                throw ValidationException::withMessages([
                    'activo' => 'No puedes desactivar un almacén con existencias o préstamos pendientes.',
                ]);
            }
        }

        $almacen->update($data);

        return redirect()->route('inventario.almacenes')->with('success', 'Almacén actualizado correctamente.');
    }

    public function storeUbicacion(Request $request, AlmacenAlmacen $almacen)
    {
        SeccionService::autorizar($request->user(), $almacen->seccion);
        if (! $almacen->activo) {
            throw ValidationException::withMessages(['almacen_id' => 'El almacén está inactivo.']);
        }

        $data = $this->validarUbicacion($request);
        $almacen->ubicaciones()->create([...$data, 'activo' => true]);

        return redirect()->route('inventario.almacenes')->with('success', 'Ubicación agregada correctamente.');
    }

    public function updateUbicacion(Request $request, AlmacenUbicacion $ubicacion)
    {
        $ubicacion->load('almacen');
        SeccionService::autorizar($request->user(), $ubicacion->almacen->seccion);

        $data = $this->validarUbicacion($request, $ubicacion);
        $data['activo'] = $request->boolean('activo');

        if ($data['activo'] && ! $ubicacion->activo && ! $ubicacion->almacen->activo) {
            throw ValidationException::withMessages([
                'activo' => 'No puedes activar una ubicación de un almacén inactivo.',
            ]);
        }

        if (! $data['activo']) {
            $tieneStock = $ubicacion->existencias()->where('cantidad', '>', 0)->exists();
            $tienePrestamos = $ubicacion->prestamos()->whereIn('estado', ['ACTIVO', 'PARCIAL'])->exists();
            if ($tieneStock || $tienePrestamos) {
                throw ValidationException::withMessages([
                    'activo' => 'No puedes desactivar una ubicación con existencias o préstamos pendientes.',
                ]);
            }
        }

        $ubicacion->update($data);

        return redirect()->route('inventario.almacenes')->with('success', 'Ubicación actualizada correctamente.');
    }

    public function updateReglas(Request $request, AlmacenExistencia $existencia)
    {
        $existencia->load('ubicacion.almacen');
        SeccionService::autorizar($request->user(), $existencia->ubicacion->almacen->seccion);
        $data = $request->validate([
            'stock_minimo' => ['required', 'numeric', 'gte:0', 'max:999999999999999.999'],
            'stock_maximo' => ['nullable', 'numeric', 'gt:stock_minimo', 'max:999999999999999.999'],
        ]);
        $existencia->update($data);

        return back()->with('success', 'Reglas de abastecimiento actualizadas.');
    }

    private function validarAlmacen(Request $request, ?AlmacenAlmacen $almacen = null): array
    {
        $secciones = SeccionService::disponiblesPara($request->user());
        if ($almacen && ! in_array($almacen->seccion, $secciones, true)) {
            $secciones[] = $almacen->seccion;
        }

        return $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('almacen_almacenes', 'codigo')->ignore($almacen?->id)],
            'nombre' => ['required', 'string', 'max:150'],
            'seccion' => ['required', 'string', 'max:100', Rule::in($secciones)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }

    private function validarUbicacion(Request $request, ?AlmacenUbicacion $ubicacion = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('almacen_ubicaciones', 'codigo')->ignore($ubicacion?->id)],
            'anaquel' => ['required', 'string', 'max:80'],
            'nivel' => ['required', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }
}
