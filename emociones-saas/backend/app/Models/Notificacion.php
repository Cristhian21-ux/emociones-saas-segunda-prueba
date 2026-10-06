<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACentro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use PerteneceACentro;

    protected $table = 'notificaciones';

    protected $fillable = [
        'centro_id', 'paciente_id', 'cita_id', 'tipo', 'canal', 'destino', 'mensaje',
        'estado', 'programada_para', 'enviada_at',
    ];

    protected $casts = [
        'programada_para' => 'datetime',
        'enviada_at' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
