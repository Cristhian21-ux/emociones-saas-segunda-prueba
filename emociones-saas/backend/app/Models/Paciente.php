<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACentro;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use HasFactory, PerteneceACentro, SoftDeletes;

    protected $fillable = [
        'centro_id', 'nombres', 'apellidos', 'dni', 'fecha_nacimiento', 'telefono',
        'email', 'direccion', 'contacto_emergencia', 'antecedentes',
    ];

    protected $casts = ['fecha_nacimiento' => 'date:Y-m-d'];

    protected $appends = ['nombre_completo'];

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function historiasClinicas(): HasMany
    {
        return $this->hasMany(HistoriaClinica::class);
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
