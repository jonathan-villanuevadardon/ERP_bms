<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de SOLO LECTURA sobre la vista "HCBMS_ALL_OP".
 *
 * La vista es de origen externo (no podemos escribirla). Proporciona el
 * maestro de empleados: número de empleado (clave), nombre, categoría (cargo),
 * departamento, subarea, nivel 2 y sección.
 *
 * Se usa para resolver los datos de identidad del empleado al momento de
 * asignar roles, descansos, vacaciones o permisos.
 */
class Empleado extends Model
{
    /**
     * Tabla/vista asociada (solo lectura).
     */
    protected $table = 'HCBMS_ALL_OP';

    /**
     * Indicar a Eloquent que esta vista NO tiene `id` autoincremental.
     */
    public $incrementing = false;

    protected $primaryKey = 'clave';
    protected $keyType = 'int';

    /**
     * Evita que se intente escribir timestamps en una vista.
     */
    public $timestamps = false;

    /**
     * Atributos de la vista (mapeo 1:1 por nombre de columna).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clave',
        'Nombre_completo',
        'categoria',
        'dept_name',
        'subarea',
        'Nivel_2',
        'create_time',
        'seccion',
    ];
}