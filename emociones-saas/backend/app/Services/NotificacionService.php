<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Notificacion;
use App\Models\Paciente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Notificaciones y recordatorios. El envío real (WhatsApp/SMS/correo) se conecta
 * en enviar(); en modo demostración se escribe en el log.
 */
class NotificacionService
{
    public static function programar(Paciente $paciente, string $tipo, string $mensaje, ?Cita $cita = null, ?Carbon $cuando = null): Notificacion
    {
        $canal = config('centro.canal_predeterminado');

        return Notificacion::create([
            'centro_id' => $paciente->centro_id,
            'paciente_id' => $paciente->id,
            'cita_id' => $cita?->id,
            'tipo' => $tipo,
            'canal' => $canal,
            'destino' => $canal === 'correo' ? $paciente->email : $paciente->telefono,
            'mensaje' => $mensaje,
            'estado' => 'pendiente',
            'programada_para' => $cuando ?? now(),
        ]);
    }

    public static function enviar(Notificacion $notificacion): Notificacion
    {
        if ($notificacion->estado !== 'pendiente') {
            return $notificacion;
        }

        if (blank($notificacion->destino)) {
            $notificacion->update(['estado' => 'fallida']);

            return $notificacion;
        }

        Log::info("[notificación {$notificacion->canal}] a {$notificacion->destino}: {$notificacion->mensaje}");
        $notificacion->update(['estado' => 'enviada', 'enviada_at' => now()]);

        return $notificacion;
    }

    public static function enviarPendientes(): int
    {
        $pendientes = Notificacion::query()
            ->where('estado', 'pendiente')
            ->where('programada_para', '<=', now())
            ->get();

        $pendientes->each(fn (Notificacion $n) => self::enviar($n));

        return $pendientes->count();
    }

    public static function confirmacion(Cita $cita): Notificacion
    {
        $cita->loadMissing(['paciente', 'psicologo']);
        $mensaje = "Hola {$cita->paciente->nombres}, tu cita quedó registrada para el ".self::cuando($cita).'.';

        return self::enviar(self::programar($cita->paciente, 'confirmacion', $mensaje, $cita));
    }

    /** Recordatorio N horas antes; no se crea si ese momento ya pasó. */
    public static function recordatorio(Cita $cita): ?Notificacion
    {
        $cita->loadMissing(['paciente']);
        $cuando = $cita->momento()->subHours((int) config('centro.recordatorio_horas_antes'));

        if ($cuando->lte(now())) {
            return null;
        }

        return self::programar($cita->paciente, 'recordatorio', 'Recordatorio: tienes cita el '.self::cuando($cita).'.', $cita, $cuando);
    }

    public static function cancelacion(Cita $cita): Notificacion
    {
        $cita->loadMissing(['paciente']);

        return self::enviar(self::programar($cita->paciente, 'cancelacion', 'Tu cita del '.self::cuando($cita).' fue cancelada.', $cita));
    }

    public static function cupoLiberado(Paciente $paciente, Cita $cita): Notificacion
    {
        return self::enviar(self::programar($paciente, 'cupo_liberado', 'Se liberó un cupo el '.self::cuando($cita).'. Comunícate para reservarlo.', $cita));
    }

    public static function omitirPendientes(Cita $cita): void
    {
        Notificacion::where('cita_id', $cita->id)->where('estado', 'pendiente')->update(['estado' => 'omitida']);
    }

    private static function cuando(Cita $cita): string
    {
        return $cita->fecha->format('d/m/Y').' a las '.$cita->hora;
    }
}
