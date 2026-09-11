<?php

namespace App\Imports;

use App\Models\RolDescanso;
use App\Services\EmpleadoService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

/**
 * Clase "RolesDescansoImport".
 *
 * Importa de forma masiva los roles de descanso desde un archivo Excel (.xlsx).
 * Por cada fila válida crea o actualiza el rol del empleado (por clave),
 * resolviendo nombre/área/cargo/sección desde la vista HCBMS_ALL_OP.
 *
 * La importación es transaccional dentro de cada fila: si una fila falla por
 * validación se registra el error y se continúa con las demás.
 */
class RolesDescansoImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    /**
     * Reglas de validación por fila (se aplican a la fila en bruto).
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'numero_empleado' => ['required', 'integer'],
            'dias_trabajo'    => ['required', 'integer', 'min:1'],
            'dias_descanso'   => ['required', 'integer', 'min:0'],
            'fecha_inicio'    => ['nullable', 'date'],
        ];
    }

    /**
     * Convierte cada fila del Excel a un modelo RolDescanso.
     *
     * @param  array  $row
     * @return \App\Models\RolDescanso|null
     */
    public function model(array $row)
    {
        $clave = (int) $row['numero_empleado'];

        // Resolver los datos de identidad del empleado.
        $empleado = EmpleadoService::buscarPorClave($clave);
        if (! $empleado) {
            return null; // se descarta; el visor de fallos lo reflejará
        }

        return RolDescanso::updateOrCreate(
            ['clave' => $clave, 'activo' => true],
            [
                'nombre_completo' => $empleado['nombre_completo'],
                'area'             => $empleado['area'],
                'cargo'            => $empleado['cargo'],
                'seccion'          => $empleado['seccion'],
                'dias_trabajo'     => (int) $row['dias_trabajo'],
                'dias_descanso'    => (int) $row['dias_descanso'],
                'fecha_inicio'     => $row['fecha_inicio'] ?? null,
                'activo'           => true,
            ]
        );
    }
}