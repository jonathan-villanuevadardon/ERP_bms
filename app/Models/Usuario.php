<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo "Usuario".
 *
 * Usuario del sistema ERP. Se autentica con email/password y pertenece a un
 * "Perfil" que define sus permisos y secciones. Opcionalmente se vincula a un
 * número de empleado (clave) del dominio operativo (HCBMS_ALL_OP).
 */
class Usuario extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'usuarios';

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'clave_empleado',
        'perfil_id',
        'activo',
    ];

    /**
     * Atributos ocultos en serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    /**
     * Relación: perfil (rol) del usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function perfil()
    {
        return $this->belongsTo(Perfil::class);
    }

    /**
     * Determina si es administrador maestro.
     *
     * @return bool
     */
    public function esAdmin(): bool
    {
        return $this->perfil && $this->perfil->es_admin;
    }

    /**
     * Determina si el usuario puede operar una sección concreta.
     *
     * @param  string|null  $seccion
     * @return bool
     */
    public function puedeVerSeccion(?string $seccion): bool
    {
        return $this->perfil ? $this->perfil->puedeVerSeccion($seccion) : false;
    }
}