<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
     * @return View
     */
    public function index()
    {
        $usuarios = Usuario::with('perfil')->orderBy('name')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Muestra el formulario de alta.
     *
     * @return View
     */
    public function create()
    {
        $perfiles = Perfil::orderBy('nombre')->get();

        return view('usuarios.create', compact('perfiles'));
    }

    /**
     * Almacena un nuevo usuario.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['password'] = Hash::make($request->input('password'));

        try {
            DB::transaction(function () use ($data) {
                $this->bloquearPerfiles(null, $data['perfil_id']);
                Usuario::create($data);
            });
        } catch (QueryException $exception) {
            if ($this->esCorreoDuplicado($exception)) {
                throw ValidationException::withMessages([
                    'email' => 'Ya existe un usuario registrado con este correo electrónico.',
                ]);
            }

            throw $exception;
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Muestra el formulario de edición.
     *
     * @return View
     */
    public function edit(Usuario $usuario)
    {
        $perfiles = Perfil::orderBy('nombre')->get();

        return view('usuarios.edit', compact('usuario', 'perfiles'));
    }

    /**
     * Actualiza un usuario (incluye cambio de contraseña si se provee).
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Usuario $usuario)
    {
        $data = $this->validar($request, $usuario);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        try {
            DB::transaction(function () use ($usuario, $data) {
                $perfiles = $this->bloquearPerfiles($usuario->perfil_id, $data['perfil_id']);
                $perfilActual = $perfiles->get($usuario->perfil_id);
                $perfilSeleccionado = $perfiles->get($data['perfil_id']);

                if ($perfilActual && ! $perfilActual->trashed() && $perfilActual->es_admin
                    && (! $perfilSeleccionado->es_admin || ! $data['activo'])) {
                    throw ValidationException::withMessages([
                        'perfil_id' => 'No se puede desactivar ni retirar el perfil administrador a un administrador.',
                    ]);
                }

                $usuario->update($data);
            });
        } catch (QueryException $exception) {
            if ($this->esCorreoDuplicado($exception)) {
                throw ValidationException::withMessages([
                    'email' => 'Ya existe un usuario registrado con este correo electrónico.',
                ]);
            }

            throw $exception;
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado.');
    }

    /**
     * Desactiva (baja lógica) un usuario.
     *
     * @return RedirectResponse
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
     */
    private function validar(Request $request, ?Usuario $usuario = null): array
    {
        $claveEmpleado = $request->input('clave_empleado');
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'clave_empleado' => is_scalar($claveEmpleado) && trim((string) $claveEmpleado) !== ''
                ? trim((string) $claveEmpleado)
                : null,
        ]);

        $correoUnico = Rule::unique('usuarios', 'email');
        if ($usuario) {
            $correoUnico->ignore($usuario->id);
        }

        $reglas = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', $correoUnico],
            'clave_empleado' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'perfil_id' => ['required', 'integer', Rule::exists('perfiles', 'id')->whereNull('deleted_at')],
        ];

        if (! $usuario || $request->filled('password')) {
            $reglas['password'] = ['required', 'string', 'min:6', 'confirmed'];
        }

        $data = $request->validate($reglas, [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe un usuario registrado con este correo electrónico.',
            'clave_empleado.integer' => 'La clave de empleado debe contener únicamente números enteros.',
            'clave_empleado.min' => 'La clave de empleado debe ser mayor que cero.',
            'clave_empleado.max' => 'La clave de empleado excede el valor permitido.',
            'perfil_id.required' => 'Selecciona un perfil.',
            'perfil_id.integer' => 'El perfil seleccionado no es válido.',
            'perfil_id.exists' => 'El perfil seleccionado ya no existe.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'clave_empleado' => isset($data['clave_empleado']) ? (int) $data['clave_empleado'] : null,
            'perfil_id' => (int) $data['perfil_id'],
            'activo' => $usuario ? $request->boolean('activo') : true,
        ];
    }

    private function esCorreoDuplicado(QueryException $exception): bool
    {
        $codigoSqlServer = (int) ($exception->errorInfo[1] ?? 0);

        return in_array($codigoSqlServer, [2601, 2627], true);
    }

    private function bloquearPerfiles(?int $perfilActualId, int $perfilSeleccionadoId)
    {
        $perfilIds = array_values(array_unique(array_filter([
            $perfilActualId,
            $perfilSeleccionadoId,
        ], fn ($id) => $id !== null)));
        sort($perfilIds);
        $perfiles = Perfil::withTrashed()->whereIn('id', $perfilIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $perfilSeleccionado = $perfiles->get($perfilSeleccionadoId);

        if (! $perfilSeleccionado || $perfilSeleccionado->trashed()) {
            throw ValidationException::withMessages([
                'perfil_id' => 'El perfil seleccionado ya no existe.',
            ]);
        }

        return $perfiles;
    }
}
