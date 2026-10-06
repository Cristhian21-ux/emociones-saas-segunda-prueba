<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\ListaEspera;

/**
 * Lista de espera automática: al cancelarse una cita, el cupo se ofrece a la
 * primera persona en espera con preferencias compatibles (FIFO).
 */
class ListaEsperaService
{
    public static function liberarCupo(Cita $cita): ?ListaEspera
    {
        if ($cita->momento()->isPast()) {
            return null;
        }

        $franja = $cita->hora < '13:00' ? 'manana' : 'tarde';

        $entrada = ListaEspera::query()
            ->where('centro_id', $cita->centro_id)
            ->where('estado', 'en_espera')
            ->where('paciente_id', '!=', $cita->paciente_id)
            ->where(fn ($q) => $q->whereNull('psicologo_id')->orWhere('psicologo_id', $cita->psicologo_id))
            ->where(fn ($q) => $q->whereNull('fecha_preferida')->orWhereDate('fecha_preferida', $cita->fecha->toDateString()))
            ->whereIn('franja', ['cualquiera', $franja])
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if (! $entrada) {
            return null;
        }

        $entrada->update([
            'estado' => 'notificado',
            'cupo_fecha' => $cita->fecha->toDateString(),
            'cupo_hora' => $cita->hora,
            'cupo_psicologo_id' => $cita->psicologo_id,
            'notificado_at' => now(),
        ]);

        NotificacionService::cupoLiberado($entrada->paciente, $cita);

        return $entrada;
    }
}
