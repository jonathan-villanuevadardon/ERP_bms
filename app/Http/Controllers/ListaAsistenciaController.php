<?php

namespace App\Http\Controllers;

use App\Exports\ListaAsistenciaExport;
use App\Models\ListaAsistenciaNomina;
use App\Services\SeccionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ListaAsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $secciones = SeccionService::disponiblesPara($request->user());
        $filtros = $this->filtros($request, $secciones);
        $consulta = $this->consulta($request, $filtros);

        $resumen = (clone $consulta)
            ->selectRaw('estatus_nomina, COUNT(*) AS total')
            ->groupBy('estatus_nomina')
            ->pluck('total', 'estatus_nomina');

        $filas = $consulta
            ->orderByDesc('fecha')
            ->orderBy('nombre_completo')
            ->paginate(100)
            ->withQueryString();

        return view('lista_asistencia.index', compact('filas', 'resumen', 'secciones', 'filtros'));
    }

    public function exportar(Request $request)
    {
        $secciones = SeccionService::disponiblesPara($request->user());
        $filtros = $this->filtros($request, $secciones);
        $filas = $this->consulta($request, $filtros)
            ->orderBy('fecha')
            ->orderBy('nombre_completo')
            ->get();

        $archivo = "lista_asistencia_{$filtros['desde']}_{$filtros['hasta']}.xlsx";

        return Excel::download(new ListaAsistenciaExport($filas), $archivo);
    }

    /**
     * @param  array<int, string>  $secciones
     * @return array{desde: string, hasta: string, seccion: string, clave: int|null}
     */
    private function filtros(Request $request, array $secciones): array
    {
        if (empty($secciones)) {
            abort(403, 'No hay secciones disponibles para tu perfil.');
        }

        $valores = [
            'desde' => $request->input('desde', now()->subDays(6)->format('Y-m-d')),
            'hasta' => $request->input('hasta', now()->format('Y-m-d')),
            'seccion' => $request->input('seccion', $secciones[0]),
            'clave' => $request->input('clave'),
        ];

        $datos = validator($valores, [
            'desde' => ['required', 'date_format:Y-m-d', 'before_or_equal:hasta'],
            'hasta' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'seccion' => ['required', Rule::in($secciones)],
            'clave' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        $dias = CarbonImmutable::createFromFormat('Y-m-d', $datos['desde'])
            ->diffInDays(CarbonImmutable::createFromFormat('Y-m-d', $datos['hasta']));

        if ($dias > 29) {
            throw ValidationException::withMessages(['hasta' => 'El periodo no puede exceder 30 días naturales.']);
        }

        $datos['clave'] = isset($datos['clave']) ? (int) $datos['clave'] : null;

        return $datos;
    }

    private function consulta(Request $request, array $filtros)
    {
        $consulta = ListaAsistenciaNomina::query()
            ->whereBetween('fecha', [$filtros['desde'], $filtros['hasta']])
            ->where('seccion', $filtros['seccion']);

        SeccionService::aplicar($consulta, $request->user());

        if ($filtros['clave']) {
            $consulta->where('clave', $filtros['clave']);
        }

        return $consulta;
    }
}
