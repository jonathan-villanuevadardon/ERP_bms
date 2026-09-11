<?php

namespace App\Http\Controllers;

use App\Exports\RolesDescansoPlantilla;
use App\Imports\RolesDescansoImport;
use App\Models\RolDescanso;
use App\Services\EmpleadoService;
use App\Services\SeccionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controlador "RolDescansoController".
 *
 * Módulo "Asignación de rol". Permite asignar a cada empleado su rol de
 * descanso (ej. 10 días trabajados por 5 de descanso) de tres formas:
 *   1) Masiva, cargando un archivo Excel (.xlsx).
 *   2) Individual, seleccionando un empleado y definiendo su ciclo.
 *   3) Modificando roles ya asignados.
 *
 * Muestra nombre, área, cargo y número de empleado para identificar a quién
 * se asigna cuando no se hace de forma masiva.
 */
class RolDescansoController extends Controller
{
    /**
     * Lista las asignaciones de rol existentes (con filtros opcionales).
     *
     * @return View
     */
    public function index(Request $request)
    {
        $q = RolDescanso::query();

        // Filtro por sección (respeta la restricción del perfil del usuario).
        $seccionesPermitidas = SeccionService::permitidas($request->user());
        if ($seccionesPermitidas !== null) {
            $q->whereIn('seccion', $seccionesPermitidas);
        }

        if ($seccion = $request->input('seccion')) {
            $q->where('seccion', $seccion);
        }
        if ($termino = $request->input('termino')) {
            $q->where(function ($sub) use ($termino) {
                $sub->where('nombre_completo', 'like', "%{$termino}%")
                    ->orWhere('clave', 'like', "%{$termino}%")
                    ->orWhere('cargo', 'like', "%{$termino}%");
            });
        }

        $roles = $q->orderBy('nombre_completo')->paginate(25);

        $secciones = SeccionService::disponiblesPara($request->user());

        return view('roles.index', compact('roles', 'secciones'));
    }

    /**
     * Muestra el formulario de asignación individual (con búsqueda de empleado).
     *
     * @return View
     */
    public function create(Request $request)
    {
        $empleados = [];
        if ($termino = $request->input('termino')) {
            $empleados = EmpleadoService::listar(null, $termino, 50, SeccionService::permitidas($request->user()));
        }

        $secciones = SeccionService::disponiblesPara($request->user());

        return view('roles.create', compact('empleados', 'secciones'));
    }

    /**
     * Almacena una asignación individual de rol.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $this->validarIndividual($request);

        // Resolver datos de identidad del empleado desde la vista.
        $empleado = EmpleadoService::buscarPorClave($data['clave']);
        if (! $empleado) {
            return back()->withInput()->withErrors(['clave' => 'Número de empleado no encontrado.']);
        }

        SeccionService::autorizar($request->user(), $empleado['seccion']);

        RolDescanso::updateOrCreate(
            ['clave' => $data['clave'], 'activo' => true],
            array_merge($data, [
                'nombre_completo' => $empleado['nombre_completo'],
                'area' => $empleado['area'],
                'cargo' => $empleado['cargo'],
                'seccion' => $empleado['seccion'],
            ])
        );

        return redirect()->route('roles.index')->with('success', 'Rol asignado correctamente.');
    }

    /**
     * Muestra el formulario de edición de un rol.
     *
     * @return View
     */
    public function edit(Request $request, RolDescanso $rol)
    {
        SeccionService::autorizar($request->user(), $rol->seccion);

        return view('roles.edit', compact('rol'));
    }

    /**
     * Actualiza un rol existente.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, RolDescanso $rol)
    {
        SeccionService::autorizar($request->user(), $rol->seccion);
        $data = $this->validarIndividual($request);
        $empleado = EmpleadoService::buscarPorClave($data['clave']);
        if (! $empleado) {
            return back()->withInput()->withErrors(['clave' => 'Número de empleado no encontrado.']);
        }
        SeccionService::autorizar($request->user(), $empleado['seccion']);

        $rol->update(array_merge($data, [
            'nombre_completo' => $empleado['nombre_completo'],
            'area' => $empleado['area'],
            'cargo' => $empleado['cargo'],
            'seccion' => $empleado['seccion'],
        ]));

        return redirect()->route('roles.index')->with('success', 'Rol actualizado.');
    }

    /**
     * Elimina (soft delete) una asignación de rol.
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request, RolDescanso $rol)
    {
        SeccionService::autorizar($request->user(), $rol->seccion);
        $rol->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado.');
    }

    /**
     * Descarga la plantilla Excel para carga masiva.
     *
     * @return BinaryFileResponse
     */
    public function plantilla()
    {
        return Excel::download(new RolesDescansoPlantilla, 'plantilla_roles_descanso.xlsx');
    }

    /**
     * Procesa la carga masiva de roles desde un archivo Excel.
     *
     * @return RedirectResponse
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ]);

        try {
            $importacion = new RolesDescansoImport(SeccionService::permitidas($request->user()));
            Excel::import($importacion, $request->file('archivo'));

            if ($importacion->failures()->isNotEmpty()) {
                $msj = $importacion->failures()
                    ->map(fn ($f) => "Fila {$f->row()}: ".implode(', ', $f->errors()))
                    ->implode(' | ');

                return back()->withErrors(['archivo' => "La carga terminó con filas rechazadas. {$msj}"]);
            }

            return redirect()->route('roles.index')->with('success', 'Carga masiva procesada correctamente.');
        } catch (ValidationException $e) {
            $errores = $e->failures();
            $msj = collect($errores)->map(fn ($f) => "Fila {$f->row()}: ".implode(', ', $f->errors()))->implode(' | ');

            return back()->withErrors(['archivo' => $msj]);
        } catch (\Throwable $e) {
            return back()->withErrors(['archivo' => 'Error al procesar el archivo: '.$e->getMessage()]);
        }
    }

    /**
     * Valida los datos de asignación individual.
     */
    private function validarIndividual(Request $request): array
    {
        return $request->validate([
            'clave' => ['required', 'integer'],
            'dias_trabajo' => ['required', 'integer', 'min:1'],
            'dias_descanso' => ['required', 'integer', 'min:0'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'activo' => ['boolean'],
        ]);
    }
}
