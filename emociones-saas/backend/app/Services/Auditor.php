<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/** RF de trazabilidad (ISO/IEC 27001): registra quién hizo qué, cuándo y desde qué IP. */
class Auditor
{
    public static function registrar(string $accion, ?Model $entidad = null, array $detalle = [], ?int $centroId = null, ?int $userId = null): Auditoria
    {
        $usuario = Auth::user();

        return Auditoria::create([
            'centro_id' => $centroId ?? $usuario?->centro_id,
            'user_id' => $userId ?? $usuario?->id,
            'accion' => $accion,
            'entidad' => $entidad ? class_basename($entidad) : null,
            'entidad_id' => $entidad?->getKey(),
            'detalle' => $detalle ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
