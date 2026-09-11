<?php

namespace App\Http\Controllers;

use App\Models\Incapacidad;
use App\Models\IncapacidadPendiente;
use App\Services\EmpleadoService;
use App\Services\SeccionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IncapacidadController extends Controller
{
    public const TIPOS = [
        'enfermedad_general' => 'Enfermedad general',
        'riesgo_trabajo' => 'Riesgo de trabajo',
        'maternidad' => 'Maternidad',
    ];

    public function index(Request $request)
    {
        $consulta = SeccionService::aplicar(Incapacidad::query(), $request->user());

        if ($request->filled('clave')) {
            $consulta->where('clave', (int) $request->input('clave'));
        }

        $incapacidades = $consulta->orderByDesc('fecha_inicio')->paginate(25)->withQueryString();

        return view('incapacidades.index', ['incapacidades' => $incapacidades, 'tipos' => self::TIPOS]);
    }

    public function create(Request $request)
    {
        $empleados = [];
        if ($termino = $request->input('termino')) {
            $empleados = EmpleadoService::listar(
                null,
                $termino,
                50,
                SeccionService::permitidas($request->user())
            );
        }

        return view('incapacidades.create', ['empleados' => $empleados, 'tipos' => self::TIPOS]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $empleado = EmpleadoService::buscarPorClave($data['clave']);

        if (! $empleado) {
            return back()->withInput()->withErrors(['clave' => 'Número de empleado no encontrado.']);
        }

        SeccionService::autorizar($request->user(), $empleado['seccion']);
        $this->validarFolioDisponible($data['folio']);

        IncapacidadPendiente::create(array_merge($data, [
            'nombre_completo' => $empleado['nombre_completo'],
            'area' => $empleado['area'],
            'seccion' => $empleado['seccion'],
            'dias' => $this->dias($data['fecha_inicio'], $data['fecha_fin']),
            'estado' => 'pendiente',
            'creado_por' => $request->user()->id,
        ]));

        return redirect()->route('incapacidades.pendientes')->with('success', 'Incapacidad enviada a aprobación.');
    }

    public function pendientes(Request $request)
    {
        $consulta = SeccionService::aplicar(
            IncapacidadPendiente::where('estado', 'pendiente'),
            $request->user()
        );

        $pendientes = $consulta->orderBy('fecha_inicio')->get();

        return view('incapacidades.pendientes', ['pendientes' => $pendientes, 'tipos' => self::TIPOS]);
    }

    public function editarPendiente(Request $request, IncapacidadPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);

        return view('incapacidades.editar_pendiente', ['pendiente' => $pendiente, 'tipos' => self::TIPOS]);
    }

    public function actualizarPendiente(Request $request, IncapacidadPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);
        $data = $this->validar($request);
        $empleado = EmpleadoService::buscarPorClave($data['clave']);

        if (! $empleado) {
            return back()->withInput()->withErrors(['clave' => 'Número de empleado no encontrado.']);
        }

        SeccionService::autorizar($request->user(), $empleado['seccion']);
        $this->validarFolioDisponible($data['folio'], $pendiente->id);

        $pendiente->update(array_merge($data, [
            'nombre_completo' => $empleado['nombre_completo'],
            'area' => $empleado['area'],
            'seccion' => $empleado['seccion'],
            'dias' => $this->dias($data['fecha_inicio'], $data['fecha_fin']),
        ]));

        return redirect()->route('incapacidades.pendientes')->with('success', 'Incapacidad pendiente actualizada.');
    }

    public function eliminarPendiente(Request $request, IncapacidadPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);
        $pendiente->delete();

        return redirect()->route('incapacidades.pendientes')->with('success', 'Incapacidad pendiente eliminada.');
    }

    public function aprobar(Request $request, IncapacidadPendiente $pendiente)
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);

        DB::transaction(function () use ($request, $pendiente) {
            $registro = IncapacidadPendiente::whereKey($pendiente->id)->lockForUpdate()->firstOrFail();

            if ($registro->estado !== 'pendiente') {
                throw ValidationException::withMessages(['incapacidad' => 'La solicitud ya fue procesada.']);
            }

            Incapacidad::create([
                'clave' => $registro->clave,
                'nombre_completo' => $registro->nombre_completo,
                'area' => $registro->area,
                'seccion' => $registro->seccion,
                'tipo' => $registro->tipo,
                'folio' => $registro->folio,
                'fecha_inicio' => $registro->fecha_inicio,
                'fecha_fin' => $registro->fecha_fin,
                'dias' => $registro->dias,
                'observaciones' => $registro->observaciones,
                'pendiente_id' => $registro->id,
            ]);

            $registro->update([
                'estado' => 'aprobado',
                'aprobado_por' => $request->user()->id,
                'aprobado_en' => now(),
            ]);
        });

        return redirect()->route('incapacidades.pendientes')->with('success', 'Incapacidad aprobada.');
    }

    public function rechazar(Request $request, IncapacidadPendiente $pendiente)
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);

        $actualizados = IncapacidadPendiente::whereKey($pendiente->id)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'rechazado', 'aprobado_por' => $request->user()->id, 'aprobado_en' => now()]);

        if ($actualizados === 0) {
            return back()->with('error', 'La solicitud ya fue procesada.');
        }

        return redirect()->route('incapacidades.pendientes')->with('success', 'Incapacidad rechazada.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'clave' => ['required', 'integer'],
            'tipo' => ['required', Rule::in(array_keys(self::TIPOS))],
            'folio' => ['required', 'string', 'max:100'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function dias(string $inicio, string $fin): int
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $inicio)->diffInDays(
            CarbonImmutable::createFromFormat('Y-m-d', $fin)
        ) + 1;
    }

    private function autorizarPendiente(Request $request, IncapacidadPendiente $pendiente): void
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);

        if ($pendiente->estado !== 'pendiente') {
            abort(409, 'Solo se pueden modificar solicitudes pendientes.');
        }
    }

    private function validarFolioDisponible(string $folio, ?int $ignorarPendiente = null): void
    {
        $duplicadoPendiente = IncapacidadPendiente::where('folio', $folio)
            ->where('estado', 'pendiente')
            ->when($ignorarPendiente, fn ($q) => $q->where('id', '<>', $ignorarPendiente))
            ->exists();

        if ($duplicadoPendiente || Incapacidad::where('folio', $folio)->exists()) {
            throw ValidationException::withMessages(['folio' => 'El folio IMSS ya está registrado.']);
        }
    }
}
