<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Services\SeccionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
    private const PERMISOS_DE_MODULOS = [
        'puede_asignar_rol',
        'puede_gestionar_descansos',
        'puede_gestionar_vacaciones',
        'puede_gestionar_permisos',
        'puede_aprobar',
        'es_admin',
        'puede_ver_visor',
        'puede_cargas_masivas',
        'puede_gestionar_incapacidades',
        'puede_ver_lista_asistencia',
        'puede_gestionar_viaticos',
    ];

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
        $permisosDisponibles = $this->permisosDisponibles();

        return view('perfiles.create', compact('secciones', 'permisosDisponibles'));
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
        $permisosDisponibles = $this->permisosDisponibles();

        return view('perfiles.edit', compact('perfil', 'secciones', 'permisosDisponibles'));
    }

    /**
     * Actualiza un perfil existente.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Perfil $perfil)
    {
        $data = $this->validar($request, $perfil);

        if ($perfil->es_admin && ! $data['es_admin']) {
            return back()->withInput()->with('error', 'No se puede retirar la condición de administrador al perfil administrador.');
        }

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
        $error = DB::transaction(function () use ($perfil) {
            $registro = Perfil::whereKey($perfil->id)->lockForUpdate()->firstOrFail();

            if ($registro->es_admin) {
                return 'No se puede eliminar el perfil administrador.';
            }

            if ($registro->usuarios()->where('activo', true)->exists()) {
                return 'No se puede eliminar un perfil asignado a usuarios activos. Reasígnalos o desactívalos primero.';
            }

            $registro->delete();

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

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

        return array_merge([
            'nombre' => $request->input('nombre'),
            'slug' => $request->input('slug'),
            'descripcion' => $request->input('descripcion'),
            'secciones' => $request->input('secciones', []),
        ], $this->permisosSolicitados($request));
    }

    private function permisosDisponibles(): array
    {
        return array_values(array_filter(
            self::PERMISOS_DE_MODULOS,
            fn (string $permiso) => Schema::hasColumn('perfiles', $permiso)
        ));
    }

    private function permisosSolicitados(Request $request): array
    {
        return collect($this->permisosDisponibles())
            ->mapWithKeys(fn (string $permiso) => [$permiso => $request->boolean($permiso)])
            ->all();
    }
}
