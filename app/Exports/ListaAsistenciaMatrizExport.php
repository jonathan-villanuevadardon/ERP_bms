<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ListaAsistenciaMatrizExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    private const ESTATUS = [
        'INCAPACIDAD',
        'VACACIONES',
        'PERMISO',
        'DESCANSO',
        'ASISTENCIA',
        'FALTA',
    ];

    /** @var array<int, string> */
    private array $fechas = [];

    public function __construct(
        private readonly Collection $filas,
        string $desde,
        string $hasta,
    ) {
        $fecha = CarbonImmutable::createFromFormat('Y-m-d', $desde);
        $fin = CarbonImmutable::createFromFormat('Y-m-d', $hasta);

        while ($fecha->lessThanOrEqualTo($fin)) {
            $this->fechas[] = $fecha->format('Y-m-d');
            $fecha = $fecha->addDay();
        }
    }

    public function collection(): Collection
    {
        return $this->filas
            ->groupBy(fn ($fila) => (string) $fila->clave)
            ->map(function (Collection $filasEmpleado): array {
                $empleado = $filasEmpleado->first();
                $porFecha = $filasEmpleado->keyBy(
                    fn ($fila) => $fila->fecha->format('Y-m-d')
                );
                $conteos = array_fill_keys(self::ESTATUS, 0);
                $estatusPorFecha = [];

                foreach ($this->fechas as $fecha) {
                    $estatus = $porFecha->get($fecha)?->estatus_nomina;
                    $estatusPorFecha[] = $estatus ?? '';

                    if (isset($conteos[$estatus])) {
                        $conteos[$estatus]++;
                    }
                }

                return [
                    $empleado->clave,
                    $empleado->nombre_completo,
                    $empleado->area,
                    $empleado->cargo,
                    $empleado->seccion,
                    ...$estatusPorFecha,
                    ...array_values($conteos),
                ];
            })
            ->values();
    }

    public function headings(): array
    {
        return [
            'Número de empleado',
            'Nombre',
            'Área',
            'Cargo',
            'Sección',
            ...array_map(
                fn (string $fecha) => CarbonImmutable::createFromFormat('Y-m-d', $fecha)->format('d/m/Y'),
                $this->fechas,
            ),
            ...array_map(fn (string $estatus) => "Total {$estatus}", self::ESTATUS),
        ];
    }
}
