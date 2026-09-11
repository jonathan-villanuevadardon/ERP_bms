<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;

/**
 * Clase "RolesDescansoPlantilla".
 *
 * Genera la plantilla Excel (.xlsx) para la carga masiva de roles de descanso.
 * Columnas:
 *   A: numero_empleado  (clave)
 *   B: dias_trabajo     (número entero >= 1)
 *   C: dias_descanso    (número entero >= 0)
 *   D: fecha_inicio     (opcional, formato AAAA-MM-DD)
 *
 * Incluye una fila de ejemplo para guiar al usuario.
 */
class RolesDescansoPlantilla implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Datos de la plantilla (una fila de ejemplo).
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return new Collection([
            ['numero_empleado' => 116, 'dias_trabajo' => 10, 'dias_descanso' => 5, 'fecha_inicio' => '2026-01-01'],
        ]);
    }

    /**
     * Encabezados de columna.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'numero_empleado',
            'dias_trabajo',
            'dias_descanso',
            'fecha_inicio',
        ];
    }

    /**
     * Mapea cada fila a celdas.
     *
     * @param  mixed  $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row['numero_empleado'],
            $row['dias_trabajo'],
            $row['dias_descanso'],
            $row['fecha_inicio'],
        ];
    }
}