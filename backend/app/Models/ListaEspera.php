<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACentro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListaEspera extends Model
{
    use PerteneceACentro;

    protected $table = 'lista_espera';

    protected $fillable = [
        'centro_id', 'paciente_id', 'psicologo_id', 'fecha_preferida', 'franja', 'motivo', 'estado',
        'cita_id', 'cupo_fecha', 'cupo_hora', 'cupo_psicologo_id', 'notificado_at',
    ];

    protected $casts = [
        'fecha_preferida' => 'date:Y-m-d',
        'cupo_fecha' => 'date:Y-m-d',
        'notificado_at' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function psicologo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'psicologo_id');
    }
}
