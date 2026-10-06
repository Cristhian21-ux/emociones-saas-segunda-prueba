<?php

namespace App\Models\Concerns;

use App\Models\Centro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Multi-tenancy del SaaS: cada consulta se limita al centro del usuario
 * autenticado y cada registro nuevo se asocia a ese centro. El superadmin
 * (sin centro) no tiene filtro.
 */
trait PerteneceACentro
{
    public static function bootPerteneceACentro(): void
    {
        static::addGlobalScope('centro', function (Builder $query) {
            $centroId = Auth::user()?->centro_id;
            if ($centroId) {
                $query->where($query->getModel()->getTable().'.centro_id', $centroId);
            }
        });

        static::creating(function ($model) {
            if (! $model->centro_id && Auth::user()?->centro_id) {
                $model->centro_id = Auth::user()->centro_id;
            }
        });
    }

    public function centro(): BelongsTo
    {
        return $this->belongsTo(Centro::class);
    }
}
