<?php

namespace App\Http\Controllers;

use App\Models\Viatico;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $consulta = Viatico::query()
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

    public function create()
    {
        return view('viaticos.create');
    }

    public function store(Request $request)
    {
        $data = $this->validarDatos($request);
        $request->validate([
            'boleto' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:10240'],
        ]);

        $archivo = $request->file('boleto');
        $contenido = file_get_contents($archivo->getRealPath());
        $hash = hash('sha256', $contenido);
        $duplicado = Viatico::query()
            ->where('hash_archivo', $hash)
            ->orWhere(function ($query) use ($data) {
                $query->where('transportista', $data['transportista'])
                    ->whereNotNull('referencia_boleto')
                    ->where('referencia_boleto', $data['referencia_boleto']);
            })
            ->first(['folio_ingreso']);

        if ($duplicado) {
            throw ValidationException::withMessages([
                'boleto' => "El boleto ya fue registrado con el folio {$duplicado->folio_ingreso}.",
            ]);
        }

        try {
            DB::table('tb_viaticos')->insert([
                'folio_ingreso' => 'VIA-'.now()->year.'-'.strtoupper(Str::random(10)),
                'numero_empleado' => $data['numero_empleado'],
                'tipo_transporte' => $data['tipo_transporte'],
                'costo' => $data['costo'],
                'origen' => $data['origen'],
                'destino' => $data['destino'],
                'fecha_salida' => $data['fecha_salida'],
                'nombre_persona' => $data['nombre_persona'],
                'transportista' => $data['transportista'],
                'referencia_boleto' => $data['referencia_boleto'],
                'estatus_aprobacion' => 'PENDIENTE_REVISION',
                'semana_pagar' => $data['semana_pagar'],
                'imagen_mime' => $archivo->getMimeType(),
                'imagen_nombre' => Str::limit($archivo->getClientOriginalName(), 255, ''),
                'imagen_boleto' => $contenido,
                'hash_archivo' => $hash,
            ]);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '2601', '2627'], true)) {
                throw ValidationException::withMessages([
                    'boleto' => 'El boleto ya fue registrado anteriormente.',
                ]);
            }

            throw $exception;
        }

        return redirect()->route('viaticos.index')->with('success', 'Viático capturado y enviado a revisión.');
    }

    public function edit(Viatico $viatico)
    {
        return view('viaticos.edit', compact('viatico'));
    }

    public function update(Request $request, Viatico $viatico)
    {
        $data = $this->validarDatos($request);

        DB::transaction(function () use ($viatico, $data) {
            $registro = Viatico::whereKey($viatico->getKey())->lockForUpdate()->firstOrFail();
            $this->asegurarPendiente($registro);
            $registro->update($data);
        });

        return redirect()->route('viaticos.index')->with('success', 'Viático actualizado correctamente.');
    }

    public function imagen(Viatico $viatico)
    {
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

        DB::transaction(function () use ($ids, $usuario, $ahora) {
            $viaticos = Viatico::whereIn('id_viatico', $ids)->lockForUpdate()->get();

            if ($viaticos->count() !== count($ids)) {
                throw ValidationException::withMessages(['viaticos' => 'Uno o más viáticos no existen.']);
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

        DB::transaction(function () use ($ids, $usuario) {
            $viaticos = Viatico::whereIn('id_viatico', $ids)->lockForUpdate()->get();

            if ($viaticos->count() !== count($ids)) {
                throw ValidationException::withMessages(['viaticos' => 'Uno o más viáticos no existen.']);
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
            'numero_empleado' => ['required', 'string', 'max:30'],
            'tipo_transporte' => ['required', Rule::in(['autobus', 'avion'])],
            'costo' => ['required', 'numeric', 'gt:0', 'max:9999999999999999.99'],
            'origen' => ['required', 'string', 'max:120'],
            'destino' => ['required', 'string', 'max:120'],
            'fecha_salida' => ['required', 'date_format:Y-m-d'],
            'nombre_persona' => ['required', 'string', 'max:180'],
            'transportista' => ['required', 'string', 'max:120'],
            'referencia_boleto' => ['nullable', 'string', 'max:100'],
            'semana_pagar' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function asegurarPendiente(Viatico $viatico): void
    {
        if ($viatico->estatus_aprobacion !== 'PENDIENTE_REVISION') {
            abort(409, 'Solo se pueden modificar viáticos pendientes de revisión.');
        }
    }
}
