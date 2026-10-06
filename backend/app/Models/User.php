<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Roles (RBAC, principio de mínimo privilegio). */
    public const ROLES = [
        'superadmin' => 'Plataforma',
        'admin' => 'Administración',
        'recepcionista' => 'Recepción',
        'psicologo' => 'Psicología',
    ];

    protected $fillable = ['centro_id', 'name', 'email', 'password', 'role', 'especialidad', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function centro(): BelongsTo
    {
        return $this->belongsTo(Centro::class);
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isPsicologo(): bool
    {
        return $this->role === 'psicologo';
    }

    /** Psicólogos activos del mismo centro. */
    public function scopePsicologosActivos($query, int $centroId)
    {
        return $query->where('centro_id', $centroId)->where('role', 'psicologo')->where('activo', true);
    }

    public function citasComoPsicologo(): HasMany
    {
        return $this->hasMany(Cita::class, 'psicologo_id');
    }
}
