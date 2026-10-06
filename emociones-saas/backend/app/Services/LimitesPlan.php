<?php

namespace App\Services;

use App\Models\Centro;
use App\Models\Paciente;

/** Límites de uso según el plan contratado por el centro. */
class LimitesPlan
{
    public static function puedeAgregarPsicologo(Centro $centro): bool
    {
        $actuales = $centro->usuarios()->where('role', 'psicologo')->where('activo', true)->count();

        return $actuales < $centro->plan->max_psicologos;
    }

    public static function puedeAgregarPaciente(Centro $centro): bool
    {
        return Paciente::where('centro_id', $centro->id)->count() < $centro->plan->max_pacientes;
    }

    public static function uso(Centro $centro): array
    {
        return [
            'psicologos' => $centro->usuarios()->where('role', 'psicologo')->where('activo', true)->count(),
            'max_psicologos' => $centro->plan->max_psicologos,
            'pacientes' => Paciente::where('centro_id', $centro->id)->count(),
            'max_pacientes' => $centro->plan->max_pacientes,
        ];
    }
}
