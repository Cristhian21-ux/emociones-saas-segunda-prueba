<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACentro;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Cita extends Model
{
    use HasFactory, PerteneceACentro;

    public const METODOS_PAGO = ['efectivo', 'yape', 'plin', 'transferencia', 'tarjeta'];

    /** Estados que ya no ocupan el horario del psicólogo. */
    public const ESTADOS_LIBRES = ['no_agendada', 'cancelada'];

    protected $fillable = [
        'centro_id', 'paciente_id', 'psicologo_id', 'recepcionista_id', 'fecha', 'hora', 'motivo',
        'estado', 'monto', 'pagado', 'comprobante_numero', 'metodo_pago', 'pagado_at', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'pagado' => 'boolean',
        'monto' => 'decimal:2',
        'pagado_at' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function psicologo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'psicologo_id');
    }

    public function historiaClinica(): HasOne
    {
        return $this->hasOne(HistoriaClinica::class);
    }

    public function momento(): Carbon
    {
        return Carbon::parse($this->fecha->toDateString().' '.$this->hora);
    }

    public function sePuedeCancelar(): bool
    {
        return ! in_array($this->estado, ['atendida', 'cancelada', 'no_agendada'], true);
    }
}
