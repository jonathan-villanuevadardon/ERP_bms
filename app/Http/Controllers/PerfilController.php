<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Services\SeccionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador "PerfilController".
 *
 * CRUD de perfiles (roles de acceso). Solo accesible por el Admin.
 * Permite dar altas, bajas, modificaciones y consultas de perfiles, así como
 * definir qué módulos/secciones puede ver cada uno.
 */
class PerfilController extends Controller
{
    /**
     * Lista los perfiles existentes.
     *
     * @return View
     */
    public function index()
    {
        $perfiles = Perfil::orderBy('nombre')->get();

        return view('perfiles.index', compact('perfiles'));
    }

    /**
     * Muestra el formulario de alta de un perfil.
     *
     * @return View
     */
    public function create()
    {
        $secciones = SeccionService::disponibles();

        return view('perfiles.create', compact('secciones'));
    }

    /**
     * Almacena un nuevo perfil.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        Perfil::create($data);

        return redirect()->route('perfiles.index')->with('success', 'Perfil creado correctamente.');
    }

    /**
     * Muestra el formulario de edición de un perfil.
     *
     * @return View
     */
    public function edit(Perfil $perfil)
    {
        $secciones = SeccionService::disponibles();

        return view('perfiles.edit', compact('perfil', 'secciones'));
    }

    /**
     * Actualiza un perfil existente.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Perfil $perfil)
    {
        $data = $this->validar($request, $perfil);

        $perfil->update($data);

        return redirect()->route('perfiles.index')->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Elimina (soft delete) un perfil.
     *
     * @return RedirectResponse
     */
    public function destroy(Perfil $perfil)
    {
        if ($perfil->es_admin) {
            return back()->with('error', 'No se puede eliminar el perfil administrador.');
        }

        $perfil->delete();

        return redirect()->route('perfiles.index')->with('success', 'Perfil eliminado.');
    }

    /**
     * Valida y normaliza los datos de un perfil.
     */
    private function validar(Request $request, ?Perfil $perfil = null): array
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:perfiles,slug,'.($perfil?->id ?? 'NULL')],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'secciones' => ['nullable', 'array'],
            'secciones.*' => ['string', Rule::in(SeccionService::disponibles())],
        ]);

        return [
            'nombre' => $request->input('nombre'),
            'slug' => $request->input('slug'),
            'descripcion' => $request->input('descripcion'),
            'puede_asignar_rol' => $request->boolean('puede_asignar_rol'),
            'puede_gestionar_descansos' => $request->boolean('puede_gestionar_descansos'),
            'puede_gestionar_vacaciones' => $request->boolean('puede_gestionar_vacaciones'),
            'puede_gestionar_permisos' => $request->boolean('puede_gestionar_permisos'),
            'puede_aprobar' => $request->boolean('puede_aprobar'),
            'es_admin' => $request->boolean('es_admin'),
            'puede_ver_visor' => $request->boolean('puede_ver_visor'),
            'puede_cargas_masivas' => $request->boolean('puede_cargas_masivas'),
            'puede_gestionar_incapacidades' => $request->boolean('puede_gestionar_incapacidades'),
            'puede_ver_lista_asistencia' => $request->boolean('puede_ver_lista_asistencia'),
            'secciones' => $request->input('secciones', []),
        ];
    }
}
