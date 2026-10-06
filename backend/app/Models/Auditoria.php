<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACentro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    use PerteneceACentro;

    public const UPDATED_AT = null;

    protected $table = 'auditoria';

    protected $fillable = ['centro_id', 'user_id', 'accion', 'entidad', 'entidad_id', 'detalle', 'ip'];

    protected $casts = ['detalle' => 'array'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
