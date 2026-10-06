<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Support\Carbon;

/**
 * Agenda digital: horas libres de un psicólogo en una fecha, según el horario
 * del centro (config/centro.php) y las citas que ya ocupan la agenda.
 */
class Disponibilidad
{
    /** Horas de inicio ofrecidas ese día ("H:i"). Vacío si el centro no atiende. */
    public static function horasDelDia(string $fecha): array
    {
        $cfg = config('centro.horario');

        if (! in_array(Carbon::parse($fecha)->dayOfWeekIso, $cfg['dias'], true)) {
            return [];
        }

        $horas = [];
        $inicio = Carbon::parse("$fecha {$cfg['inicio']}");
        $fin = Carbon::parse("$fecha {$cfg['fin']}");

        while ($inicio->copy()->addMinutes($cfg['duracion'])->lte($fin)) {
            $hora = $inicio->format('H:i');
            if (! in_array($hora, $cfg['descanso'], true)) {
                $horas[] = $hora;
            }
            $inicio->addMinutes($cfg['duracion']);
        }

        return $horas;
    }

    public static function ocupadas(int $psicologoId, string $fecha, ?int $exceptoCitaId = null): array
    {
        return Cita::query()
            ->where('psicologo_id', $psicologoId)
            ->whereDate('fecha', $fecha)
            ->whereNotIn('estado', Cita::ESTADOS_LIBRES)
            ->when($exceptoCitaId, fn ($q) => $q->where('id', '!=', $exceptoCitaId))
            ->pluck('hora')
            ->all();
    }

    /** Horas libres y todavía no pasadas. */
    public static function libres(int $psicologoId, string $fecha, ?int $exceptoCitaId = null): array
    {
        $ocupadas = self::ocupadas($psicologoId, $fecha, $exceptoCitaId);

        return array_values(array_filter(
            self::horasDelDia($fecha),
            fn (string $hora) => ! in_array($hora, $ocupadas, true) && Carbon::parse("$fecha $hora")->isFuture()
        ));
    }

    public static function estaLibre(int $psicologoId, string $fecha, string $hora, ?int $exceptoCitaId = null): bool
    {
        return in_array($hora, self::horasDelDia($fecha), true)
            && ! in_array($hora, self::ocupadas($psicologoId, $fecha, $exceptoCitaId), true);
    }
}
