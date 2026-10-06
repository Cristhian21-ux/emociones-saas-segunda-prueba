<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Tenant del SaaS: un centro psicológico que usa la plataforma. */
class Centro extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['nombre', 'slug', 'ruc', 'telefono', 'email', 'plan_id', 'estado'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class);
    }

    public function suscripcionActiva(): HasOne
    {
        return $this->hasOne(Suscripcion::class)->where('estado', 'activa')->latestOfMany();
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }
}
