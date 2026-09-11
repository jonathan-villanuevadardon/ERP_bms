<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador "UsuarioController".
 *
 * CRUD de usuarios del ERP. Solo accesible por el Admin. Cada usuario se
 * vincula a un perfil (perfil_id) que define permisos y secciones.
 */
class UsuarioController extends Controller
{
    /**
     * Lista los usuarios del sistema.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $usuarios = Usuario::with('perfil')->orderBy('name')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Muestra el formulario de alta.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $perfiles = Perfil::orderBy('nombre')->get();

        return view('usuarios.create', compact('perfiles'));
    }

    /**
     * Almacena un nuevo usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['password'] = Hash::make($request->input('password'));

        Usuario::create($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Muestra el formulario de edición.
     *
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\View\View
     */
    public function edit(Usuario $usuario)
    {
        $perfiles = Perfil::orderBy('nombre')->get();

        return view('usuarios.edit', compact('usuario', 'perfiles'));
    }

    /**
     * Actualiza un usuario (incluye cambio de contraseña si se provee).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Usuario $usuario)
    {
        $data = $this->validar($request, $usuario);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado.');
    }

    /**
     * Desactiva (baja lógica) un usuario.
     *
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Usuario $usuario)
    {
        if ($usuario->esAdmin()) {
            return back()->with('error', 'No se puede dar de baja a un administrador.');
        }

        $usuario->update(['activo' => false]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado.');
    }

    /**
     * Valida los datos de un usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Usuario|null  $usuario
     * @return array
     */
    private function validar(Request $request, ?Usuario $usuario = null): array
    {
        $reglas = [
            'name'           => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email', 'max:100', 'unique:usuarios,email,' . ($usuario?->id ?? 'NULL')],
            'clave_empleado' => ['nullable', 'integer'],
            'perfil_id'      => ['required', 'exists:perfiles,id'],
        ];

        if (! $usuario || $request->filled('password')) {
            $reglas['password'] = ['required', 'string', 'min:6', 'confirmed'];
        }

        $request->validate($reglas);

        return [
            'name'           => $request->input('name'),
            'email'          => $request->input('email'),
            'clave_empleado' => $request->input('clave_empleado'),
            'perfil_id'      => $request->input('perfil_id'),
            'activo'         => $request->boolean('activo', true),
        ];
    }
}