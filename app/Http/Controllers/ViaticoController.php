<?php

namespace App\Http\Controllers;

use App\Models\Viatico;
use App\Services\EmpleadoService;
use App\Services\SeccionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PDO;
use PDOException;

class ViaticoController extends Controller
{
    private const ESTATUS = [
        'PENDIENTE_REVISION',
        'APROBADO',
        'RECHAZADO',
    ];

    public function index(Request $request)
    {
        $status = $request->validate([
            'estatus' => ['nullable', Rule::in(self::ESTATUS)],
        ])['estatus'] ?? null;

        $consulta = $this->consultaVisible($request)
            ->select([
                'id_viatico', 'folio_ingreso', 'numero_empleado', 'tipo_transporte',
                'costo', 'origen', 'destino', 'fecha_salida', 'nombre_persona',
                'transportista', 'referencia_boleto', 'estatus_aprobacion',
                'semana_pagar', 'usuario_revisa', 'usuario_aprueba',
                'fecha_ingreso', 'fecha_revision', 'fecha_aprobacion',
            ])
            ->when($status, fn ($query) => $query->where('estatus_aprobacion', $status))
            ->when(! $status, fn ($query) => $query->where('estatus_aprobacion', '<>', 'RECHAZADO'))
            ->orderByRaw("CASE WHEN estatus_aprobacion = 'PENDIENTE_REVISION' THEN 0 ELSE 1 END")
            ->orderByDesc('fecha_ingreso');

        return view('viaticos.index', [
            'viaticos' => $consulta->paginate(25)->withQueryString(),
            'estatusSeleccionado' => $status,
        ]);
    }

    public function create(Request $request)
    {
        $empleados = [];
        if ($termino = $request->input('termino')) {
            $empleados = EmpleadoService::listar(
                null,
                $termino,
                50,
                SeccionService::permitidas($request->user()),
            );
        }

        return view('viaticos.create', compact('empleados'));
    }

    public function store(Request $request)
    {
        $data = $this->validarDatos($request);
        $empleado = $this->empleadoAutorizado($request, $data['numero_empleado']);
        $request->validate([
            'boleto' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:10240'],
        ]);

        $archivo = $request->file('boleto');
        $contenido = file_get_contents($archivo->getRealPath());
        $hash = hash('sha256', $contenido);
        try {
            DB::transaction(function () use ($data, $empleado, $archivo, $contenido, $hash) {
                $duplicado = Viatico::query()
                    ->where('hash_archivo', $hash)
                    ->when(filled($data['referencia_boleto']), function ($query) use ($data) {
                        $query->orWhere(function ($referencia) use ($data) {
                            $referencia->where('transportista', $data['transportista'])
                                ->where('referencia_boleto', $data['referencia_boleto']);
                        });
                    })
                    ->exists();

                if ($duplicado) {
                    throw ValidationException::withMessages([
                        'boleto' => 'El boleto ya fue registrado anteriormente.',
                    ]);
                }

                $this->insertarViatico([
                    ...$data,
                    'numero_empleado' => $empleado['clave'],
                    'nombre_persona' => $empleado['nombre_completo'],
                    'folio_ingreso' => 'VIA-'.now()->year.'-'.strtoupper(Str::random(10)),
                    'estatus_aprobacion' => 'PENDIENTE_REVISION',
                    'imagen_mime' => $archivo->getMimeType(),
                    'imagen_nombre' => Str::limit($archivo->getClientOriginalName(), 255, ''),
                    'imagen_boleto' => $contenido,
                    'hash_archivo' => $hash,
                ]);
            });
        } catch (PDOException $exception) {
            if ($this->esDuplicado($exception)) {
                throw ValidationException::withMessages([
                    'boleto' => 'El boleto ya fue registrado anteriormente.',
                ]);
            }

            throw $exception;
        }

        return redirect()->route('viaticos.index')->with('success', 'Viático capturado y enviado a revisión.');
    }

    public function edit(Request $request, int $viatico)
    {
        $viatico = $this->consultaVisible($request)
            ->select([
                'id_viatico', 'folio_ingreso', 'numero_empleado', 'tipo_transporte',
                'costo', 'origen', 'destino', 'fecha_salida', 'nombre_persona',
                'transportista', 'referencia_boleto', 'estatus_aprobacion',
                'semana_pagar', 'imagen_mime',
            ])
            ->whereKey($viatico)
            ->firstOrFail();

        return view('viaticos.edit', compact('viatico'));
    }

    public function update(Request $request, int $viatico)
    {
        $this->consultaVisible($request)->whereKey($viatico)->firstOrFail(['id_viatico']);
        $data = $this->validarDatos($request);
        $empleado = $this->empleadoAutorizado($request, $data['numero_empleado']);

        DB::transaction(function () use ($request, $viatico, $data, $empleado) {
            $registro = $this->consultaVisible($request)
                ->whereKey($viatico)
                ->lockForUpdate()
                ->firstOrFail();
            $this->asegurarPendiente($registro);
            $registro->update([
                ...$data,
                'numero_empleado' => $empleado['clave'],
                'nombre_persona' => $empleado['nombre_completo'],
            ]);
        });

        return redirect()->route('viaticos.index')->with('success', 'Viático actualizado correctamente.');
    }

    public function imagen(Request $request, int $viatico)
    {
        $viatico = $this->consultaVisible($request)
            ->select(['id_viatico', 'numero_empleado', 'imagen_boleto', 'imagen_mime', 'imagen_nombre'])
            ->whereKey($viatico)
            ->firstOrFail();
        abort_if(empty($viatico->imagen_boleto), 404);

        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $viatico->imagen_nombre);
        $nombre = $nombre !== '' ? $nombre : 'boleto';
        $tiposPermitidos = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = in_array(strtolower((string) $viatico->imagen_mime), $tiposPermitidos, true)
            ? strtolower($viatico->imagen_mime)
            : 'application/octet-stream';

        return response($viatico->imagen_boleto, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => ($mime === 'application/octet-stream' ? 'attachment' : 'inline').'; filename="'.$nombre.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function aprobar(Request $request)
    {
        $ids = $this->validarIds($request);
        $usuario = Str::limit((string) $request->user()->name, 100, '');
        $ahora = now();

        DB::transaction(function () use ($request, $ids, $usuario, $ahora) {
            $viaticos = $this->consultaVisible($request)
                ->select(['id_viatico', 'folio_ingreso', 'numero_empleado', 'estatus_aprobacion', 'semana_pagar'])
                ->whereIn('id_viatico', $ids)
                ->lockForUpdate()
                ->get();

            if ($viaticos->count() !== count($ids)) {
                throw ValidationException::withMessages(['viaticos' => 'Uno o más viáticos no existen o no pertenecen a tus secciones.']);
            }

            foreach ($viaticos as $viatico) {
                if ($viatico->estatus_aprobacion !== 'PENDIENTE_REVISION') {
                    throw ValidationException::withMessages([
                        'viaticos' => "El folio {$viatico->folio_ingreso} ya fue procesado.",
                    ]);
                }

                if (blank($viatico->semana_pagar)) {
                    throw ValidationException::withMessages([
                        'viaticos' => "El folio {$viatico->folio_ingreso} requiere una semana de pago.",
                    ]);
                }

                $guardado = $viatico->forceFill([
                    'estatus_aprobacion' => 'APROBADO',
                    'usuario_revisa' => $usuario,
                    'usuario_aprueba' => $usuario,
                    'fecha_revision' => $ahora,
                    'fecha_aprobacion' => $ahora,
                ])->save();

                if (! $guardado) {
                    throw ValidationException::withMessages([
                        'viaticos' => "No fue posible aprobar el folio {$viatico->folio_ingreso}.",
                    ]);
                }
            }
        });

        return redirect()->route('viaticos.index')->with('success', 'Viático(s) aprobado(s) correctamente.');
    }

    public function rechazar(Request $request)
    {
        $ids = $this->validarIds($request);
        $usuario = Str::limit((string) $request->user()->name, 100, '');

        DB::transaction(function () use ($request, $ids, $usuario) {
            $viaticos = $this->consultaVisible($request)
                ->select(['id_viatico', 'folio_ingreso', 'numero_empleado', 'estatus_aprobacion'])
                ->whereIn('id_viatico', $ids)
                ->lockForUpdate()
                ->get();

            if ($viaticos->count() !== count($ids)) {
                throw ValidationException::withMessages(['viaticos' => 'Uno o más viáticos no existen o no pertenecen a tus secciones.']);
            }

            foreach ($viaticos as $viatico) {
                if ($viatico->estatus_aprobacion !== 'PENDIENTE_REVISION') {
                    throw ValidationException::withMessages([
                        'viaticos' => "El folio {$viatico->folio_ingreso} ya fue procesado.",
                    ]);
                }

                $guardado = $viatico->forceFill([
                    'estatus_aprobacion' => 'RECHAZADO',
                    'usuario_revisa' => $usuario,
                    'fecha_revision' => now(),
                ])->save();

                if (! $guardado) {
                    throw ValidationException::withMessages([
                        'viaticos' => "No fue posible rechazar el folio {$viatico->folio_ingreso}.",
                    ]);
                }
            }
        });

        return redirect()->route('viaticos.index')->with('success', 'Viático(s) rechazado(s) correctamente.');
    }

    private function validarIds(Request $request): array
    {
        $data = $request->validate([
            'viaticos' => ['required', 'array', 'min:1'],
            'viaticos.*' => ['required', 'integer', 'distinct'],
        ]);

        return array_values(array_map('intval', $data['viaticos']));
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'numero_empleado' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
            'tipo_transporte' => ['required', Rule::in(['autobus', 'avion'])],
            'costo' => ['required', 'numeric', 'gt:0', 'max:9999999999999999.99'],
            'origen' => ['required', 'string', 'max:120'],
            'destino' => ['required', 'string', 'max:120'],
            'fecha_salida' => ['required', 'date_format:Y-m-d'],
            'transportista' => ['required', 'string', 'max:120'],
            'referencia_boleto' => ['nullable', 'string', 'max:100'],
            'semana_pagar' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function consultaVisible(Request $request): Builder
    {
        $consulta = Viatico::query();
        $secciones = SeccionService::permitidas($request->user());

        if ($secciones === null) {
            return $consulta;
        }

        $comparacion = DB::connection()->getDriverName() === 'sqlite'
            ? 'CAST(empleado_viatico.clave AS INTEGER) = CAST(tb_viaticos.numero_empleado AS INTEGER)'
            : 'empleado_viatico.clave = TRY_CONVERT(BIGINT, tb_viaticos.numero_empleado)';

        return $consulta->whereExists(function ($empleados) use ($secciones, $comparacion) {
            $empleados->selectRaw('1')
                ->from('HCBMS_ALL_OP as empleado_viatico')
                ->whereRaw($comparacion)
                ->whereIn(DB::raw('LTRIM(RTRIM(empleado_viatico.seccion))'), $secciones);
        });
    }

    private function empleadoAutorizado(Request $request, int $clave): array
    {
        $empleado = EmpleadoService::buscarPorClave($clave);

        if (! $empleado) {
            throw ValidationException::withMessages([
                'numero_empleado' => 'Número de empleado no encontrado.',
            ]);
        }

        SeccionService::autorizar($request->user(), $empleado['seccion']);

        return $empleado;
    }

    private function insertarViatico(array $data): void
    {
        $pdo = DB::connection()->getPdo();
        $sentencia = $pdo->prepare(<<<'SQL'
            INSERT INTO tb_viaticos (
                folio_ingreso, numero_empleado, tipo_transporte, costo, origen, destino,
                fecha_salida, nombre_persona, transportista, referencia_boleto,
                estatus_aprobacion, semana_pagar, imagen_mime, imagen_nombre,
                imagen_boleto, hash_archivo
            ) VALUES (
                :folio_ingreso, :numero_empleado, :tipo_transporte, :costo, :origen, :destino,
                :fecha_salida, :nombre_persona, :transportista, :referencia_boleto,
                :estatus_aprobacion, :semana_pagar, :imagen_mime, :imagen_nombre,
                :imagen_boleto, :hash_archivo
            )
            SQL);

        foreach ([
            'folio_ingreso', 'numero_empleado', 'tipo_transporte', 'costo', 'origen',
            'destino', 'fecha_salida', 'nombre_persona', 'transportista',
            'referencia_boleto', 'estatus_aprobacion', 'semana_pagar', 'imagen_mime',
            'imagen_nombre', 'hash_archivo',
        ] as $campo) {
            $tipo = $data[$campo] === null ? PDO::PARAM_NULL : PDO::PARAM_STR;
            $sentencia->bindValue(":{$campo}", $data[$campo], $tipo);
        }

        $contenido = $data['imagen_boleto'];
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlsrv' && defined('PDO::SQLSRV_ENCODING_BINARY')) {
            $sentencia->bindParam(
                ':imagen_boleto',
                $contenido,
                PDO::PARAM_LOB,
                0,
                constant('PDO::SQLSRV_ENCODING_BINARY'),
            );
        } else {
            $sentencia->bindParam(':imagen_boleto', $contenido, PDO::PARAM_LOB);
        }

        $sentencia->execute();
    }

    private function esDuplicado(PDOException $exception): bool
    {
        $codigoServidor = (int) ($exception->errorInfo[1] ?? 0);

        return in_array($codigoServidor, [2601, 2627], true)
            || $exception->getCode() === '23000';
    }

    private function asegurarPendiente(Viatico $viatico): void
    {
        if ($viatico->estatus_aprobacion !== 'PENDIENTE_REVISION') {
            abort(409, 'Solo se pueden modificar viáticos pendientes de revisión.');
        }
    }
}
