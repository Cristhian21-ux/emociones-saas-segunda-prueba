<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACentro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suscripcion extends Model
{
    use PerteneceACentro;

    protected $table = 'suscripciones';

    protected $fillable = ['centro_id', 'plan_id', 'ciclo', 'monto', 'estado', 'inicia_at', 'vence_at'];

    protected $casts = [
        'monto' => 'decimal:2',
        'inicia_at' => 'datetime',
        'vence_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
