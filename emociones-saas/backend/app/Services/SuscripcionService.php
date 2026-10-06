<?php

namespace App\Services;

use App\Models\Centro;
use App\Models\Paciente;
use App\Models\Plan;
use App\Models\Suscripcion;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Ciclo de vida de la suscripción SaaS de un centro (alta, cambio de plan, cancelación). */
class SuscripcionService
{
    public static function activar(Centro $centro, Plan $plan, string $ciclo = 'mensual'): Suscripcion
    {
        return DB::transaction(function () use ($centro, $plan, $ciclo) {
            Suscripcion::withoutGlobalScopes()
                ->where('centro_id', $centro->id)
                ->where('estado', 'activa')
                ->update(['estado' => 'cancelada']);

            $centro->update(['plan_id' => $plan->id]);

            $gratis = $plan->precio($ciclo) == 0;

            return Suscripcion::create([
                'centro_id' => $centro->id,
                'plan_id' => $plan->id,
                'ciclo' => $ciclo,
                'monto' => $plan->precio($ciclo),
                'estado' => 'activa',
                'inicia_at' => now(),
                'vence_at' => $gratis ? null : ($ciclo === 'anual' ? now()->addYear() : now()->addMonth()),
            ]);
        });
    }

    /** No se puede bajar a un plan cuyo límite ya se superó (no se pierde información). */
    public static function validarCambio(Centro $centro, Plan $nuevo): void
    {
        $psicologos = $centro->usuarios()->where('role', 'psicologo')->where('activo', true)->count();
        $pacientes = Paciente::withoutGlobalScopes()->where('centro_id', $centro->id)->whereNull('deleted_at')->count();

        if ($psicologos > $nuevo->max_psicologos || $pacientes > $nuevo->max_pacientes) {
            throw new DomainException("El centro supera los límites del plan {$nuevo->nombre}. Desactiva personal o pacientes antes de cambiar.");
        }
    }

    /** Al cancelar un plan de pago se vuelve al plan gratuito. */
    public static function cancelar(Centro $centro): Suscripcion
    {
        $gratuito = Plan::where('codigo', 'gratuito')->firstOrFail();
        self::validarCambio($centro, $gratuito);

        return self::activar($centro, $gratuito);
    }
}
