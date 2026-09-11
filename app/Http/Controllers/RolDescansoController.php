<?php

namespace App\Http\Controllers;

use App\Models\RolDescanso;
use App\Services\EmpleadoService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RolesDescansoImport;

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $q = RolDescanso::query();

        // Filtro por sección (respeta la restricción del perfil del usuario).
        $seccionesPermitidas = $this->seccionesPermitidas($request->user());
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

        $secciones = config('erp.secciones');

        return view('roles.index', compact('roles', 'secciones'));
    }

    /**
     * Muestra el formulario de asignación individual (con búsqueda de empleado).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $empleados = [];
        if ($termino = $request->input('termino')) {
            $empleados = EmpleadoService::listar(null, $termino, 50);
        }

        $secciones = config('erp.secciones');

        return view('roles.create', compact('empleados', 'secciones'));
    }

    /**
     * Almacena una asignación individual de rol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $this->validarIndividual($request);

        // Resolver datos de identidad del empleado desde la vista.
        $empleado = EmpleadoService::buscarPorClave($data['clave']);
        if (! $empleado) {
            return back()->withInput()->withErrors(['clave' => 'Número de empleado no encontrado.']);
        }

        RolDescanso::updateOrCreate(
            ['clave' => $data['clave'], 'activo' => true],
            array_merge($data, [
                'nombre_completo' => $empleado['nombre_completo'],
                'area'             => $empleado['area'],
                'cargo'            => $empleado['cargo'],
                'seccion'          => $empleado['seccion'],
            ])
        );

        return redirect()->route('roles.index')->with('success', 'Rol asignado correctamente.');
    }

    /**
     * Muestra el formulario de edición de un rol.
     *
     * @param  \App\Models\RolDescanso  $rol
     * @return \Illuminate\View\View
     */
    public function edit(RolDescanso $rol)
    {
        return view('roles.edit', compact('rol'));
    }

    /**
     * Actualiza un rol existente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RolDescanso  $rol
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, RolDescanso $rol)
    {
        $data = $this->validarIndividual($request);

        $rol->update($data);

        return redirect()->route('roles.index')->with('success', 'Rol actualizado.');
    }

    /**
     * Elimina (soft delete) una asignación de rol.
     *
     * @param  \App\Models\RolDescanso  $rol
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RolDescanso $rol)
    {
        $rol->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado.');
    }

    /**
     * Descarga la plantilla Excel para carga masiva.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function plantilla()
    {
        return Excel::download(new \App\Exports\RolesDescansoPlantilla, 'plantilla_roles_descanso.xlsx');
    }

    /**
     * Procesa la carga masiva de roles desde un archivo Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ]);

        try {
            Excel::import(new RolesDescansoImport, $request->file('archivo'));

            return redirect()->route('roles.index')->with('success', 'Carga masiva procesada correctamente.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $errores = $e->failures();
            $msj = collect($errores)->map(fn ($f) => "Fila {$f->row()}: " . implode(', ', $f->errors()))->implode(' | ');

            return back()->withErrors(['archivo' => $msj]);
        } catch (\Throwable $e) {
            return back()->withErrors(['archivo' => 'Error al procesar el archivo: ' . $e->getMessage()]);
        }
    }

    /**
     * Valida los datos de asignación individual.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function validarIndividual(Request $request): array
    {
        return $request->validate([
            'clave'          => ['required', 'integer'],
            'dias_trabajo'   => ['required', 'integer', 'min:1'],
            'dias_descanso'  => ['required', 'integer', 'min:0'],
            'fecha_inicio'   => ['nullable', 'date'],
            'fecha_fin'      => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'activo'         => ['boolean'],
        ]);
    }

    /**
     * Devuelve las secciones permitidas para el usuario, o null si puede ver todas.
     *
     * @param  \App\Models\Usuario  $user
     * @return array|null
     */
    private function seccionesPermitidas($user): ?array
    {
        if ($user->esAdmin()) {
            return null; // admin ve todas
        }

        $secciones = $user->perfil->secciones ?? [];
        if (empty($secciones)) {
            return null; // perfil sin restricción ve todas
        }

        return $secciones;
    }
}