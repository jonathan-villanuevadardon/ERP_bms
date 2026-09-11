<?php

namespace App\Imports;

use App\Models\RolDescanso;
use App\Services\EmpleadoService;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

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
class RolesDescansoImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    private array $empleados = [];

    public function __construct(private readonly ?array $seccionesPermitidas = null) {}

    /**
     * Reglas de validación por fila (se aplican a la fila en bruto).
     */
    public function rules(): array
    {
        return [
            'numero_empleado' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_numeric($value)) {
                        return;
                    }

                    $clave = (int) $value;
                    $empleado = EmpleadoService::buscarPorClave($clave);
                    if (! $empleado) {
                        $fail("Número de empleado {$clave} no encontrado.");

                        return;
                    }
                    if ($this->seccionesPermitidas !== null && ! in_array($empleado['seccion'], $this->seccionesPermitidas, true)) {
                        $fail("No tienes acceso a la sección del empleado {$clave}.");

                        return;
                    }

                    $this->empleados[$clave] = $empleado;
                },
            ],
            'dias_trabajo' => ['required', 'integer', 'min:1'],
            'dias_descanso' => ['required', 'integer', 'min:0'],
            'fecha_inicio' => ['nullable', 'date'],
        ];
    }

    /**
     * Convierte cada fila del Excel a un modelo RolDescanso.
     *
     * @return RolDescanso|null
     */
    public function model(array $row): ?Model
    {
        $clave = (int) $row['numero_empleado'];

        // Resolver los datos de identidad del empleado.
        $empleado = $this->empleados[$clave] ?? EmpleadoService::buscarPorClave($clave);

        return RolDescanso::updateOrCreate(
            ['clave' => $clave, 'activo' => true],
            [
                'nombre_completo' => $empleado['nombre_completo'],
                'area' => $empleado['area'],
                'cargo' => $empleado['cargo'],
                'seccion' => $empleado['seccion'],
                'dias_trabajo' => (int) $row['dias_trabajo'],
                'dias_descanso' => (int) $row['dias_descanso'],
                'fecha_inicio' => $row['fecha_inicio'] ?? null,
                'activo' => true,
            ]
        );
    }
}
