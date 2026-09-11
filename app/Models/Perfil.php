<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo "Perfil".
 *
 * Representa un rol de acceso del sistema. Determina qué módulos y qué
 * secciones puede ver/operar un usuario. El perfil con es_admin=true es el
 * administrador maestro (ADMIN) que puede aprobar y gestionar perfiles.
 */
class Perfil extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'perfiles';

    /**
     * Campos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'puede_asignar_rol',
        'puede_gestionar_descansos',
        'puede_gestionar_vacaciones',
        'puede_gestionar_permisos',
        'puede_aprobar',
        'es_admin',
        'puede_ver_visor',
        'secciones',
    ];

    /**
     * Casts: secciones se almacena como JSON y se devuelve como array.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'secciones' => 'array',
        'puede_asignar_rol' => 'boolean',
        'puede_gestionar_descansos' => 'boolean',
        'puede_gestionar_vacaciones' => 'boolean',
        'puede_gestionar_permisos' => 'boolean',
        'puede_aprobar' => 'boolean',
        'es_admin' => 'boolean',
        'puede_ver_visor' => 'boolean',
    ];

    /**
     * Relación: usuarios que tienen este perfil.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }

    /**
     * Determina si el perfil puede ver una sección concreta.
     *
     * Un perfil sin restricción de secciones (secciones vacío/null) puede ver
     * todas. De lo contrario, solo las listadas en su arreglo "secciones".
     *
     * @param  string|null  $seccion
     * @return bool
     */
    public function puedeVerSeccion(?string $seccion): bool
    {
        if ($this->es_admin) {
            return true;
        }

        $secciones = $this->secciones ?? [];
        if (empty($secciones)) {
            return true;
        }

        return in_array($seccion, $secciones, true);
    }
}