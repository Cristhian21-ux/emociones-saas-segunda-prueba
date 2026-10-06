<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = [
        'codigo', 'nombre', 'precio_mensual', 'precio_anual', 'max_psicologos',
        'max_pacientes', 'evaluaciones', 'analisis_emociones', 'orden',
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'precio_anual' => 'decimal:2',
        'evaluaciones' => 'boolean',
        'analisis_emociones' => 'boolean',
    ];

    /** Funcionalidades que se habilitan según el plan contratado. */
    public function permite(string $funcion): bool
    {
        return (bool) ($this->{$funcion} ?? false);
    }

    public function precio(string $ciclo): float
    {
        return (float) ($ciclo === 'anual' ? $this->precio_anual : $this->precio_mensual);
    }
}
