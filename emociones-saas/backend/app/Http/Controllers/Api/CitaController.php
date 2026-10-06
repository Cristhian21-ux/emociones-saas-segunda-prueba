<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\ListaEspera;
use App\Services\Auditor;
use App\Services\Disponibilidad;
use App\Services\ListaEsperaService;
use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CitaController extends Controller
{
    /** El psicólogo solo ve sus citas (mínimo privilegio); recepción y admin ven todo el centro. */
    public function index(Request $request): JsonResponse
    {
        $citas = Cita::query()
            ->with(['paciente:id,nombres,apellidos,dni', 'psicologo:id,name'])
            ->when($request->user()->isPsicologo(), fn ($q) => $q->where('psicologo_id', $request->user()->id))
            ->when($request->filled('fecha'), fn ($q) => $q->whereDate('fecha', $request->date('fecha')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->orderBy('fecha')
            ->orderBy('hora')
            ->paginate(20);

        return response()->json($citas);
    }

    /**
     * Compuerta BPM "¿Hay horario disponible?": se valida contra una sola fuente
     * de verdad para evitar el doble registro en la agenda.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $this->validar($request, true);
        $esperaId = $datos['espera_id'] ?? null;
        unset($datos['espera_id']);

        if (! Disponibilidad::estaLibre($datos['psicologo_id'], $datos['fecha'], $datos['hora'])) {
            return $this->sinDisponibilidad($datos);
        }

        $cita = Cita::create($datos + ['recepcionista_id' => $request->user()->id, 'estado' => 'pendiente']);

        NotificacionService::confirmacion($cita);
        NotificacionService::recordatorio($cita);

        if ($esperaId) {
            ListaEspera::whereKey($esperaId)->update(['estado' => 'agendado', 'cita_id' => $cita->id]);
        }

        Auditor::registrar('cita.creada', $cita);

        return response()->json($cita->load(['paciente', 'psicologo:id,name']), 201);
    }

    public function show(Request $request, Cita $cita): JsonResponse
    {
        $this->autorizarPsicologo($request, $cita);

        return response()->json($cita->load(['paciente', 'psicologo:id,name', 'historiaClinica']));
    }

    public function update(Request $request, Cita $cita): JsonResponse
    {
        $datos = $this->validar($request, false);

        if (! Disponibilidad::estaLibre($datos['psicologo_id'], $datos['fecha'], $datos['hora'], $cita->id)) {
            return $this->sinDisponibilidad($datos);
        }

        $cita->update($datos);

        if ($cita->wasChanged(['fecha', 'hora', 'psicologo_id'])) {
            NotificacionService::omitirPendientes($cita);
            NotificacionService::recordatorio($cita->fresh());
        }

        Auditor::registrar('cita.reprogramada', $cita, $cita->getChanges());

        return response()->json($cita->fresh(['paciente', 'psicologo:id,name']));
    }

    public function cancelar(Cita $cita): JsonResponse
    {
        if (! $cita->sePuedeCancelar()) {
            return response()->json(['message' => 'Esta cita ya no se puede cancelar.'], 422);
        }

        $cita->update(['estado' => 'cancelada']);
        NotificacionService::omitirPendientes($cita);
        NotificacionService::cancelacion($cita);
        $entrada = ListaEsperaService::liberarCupo($cita);

        Auditor::registrar('cita.cancelada', $cita);

        return response()->json([
            'cita' => $cita,
            'cupo_ofrecido_a' => $entrada?->paciente?->nombre_completo,
        ]);
    }

    public function noAgendar(Cita $cita): JsonResponse
    {
        $cita->update(['estado' => 'no_agendada']);
        Auditor::registrar('cita.no_agendada', $cita);

        return response()->json($cita);
    }

    /** "Cobrar sesión y emitir comprobante": sin pago no se puede atender. */
    public function cobrar(Request $request, Cita $cita): JsonResponse
    {
        if ($cita->pagado) {
            return response()->json(['message' => 'La cita ya fue pagada.'], 422);
        }

        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0', 'max:9999'],
            'comprobante_numero' => ['required', 'string', 'max:50',
                Rule::unique('citas')->where('centro_id', $request->user()->centro_id)],
            'metodo_pago' => ['required', Rule::in(Cita::METODOS_PAGO)],
        ]);

        $cita->update($datos + ['pagado' => true, 'pagado_at' => now(), 'estado' => 'confirmada']);
        Auditor::registrar('pago.registrado', $cita, ['monto' => $datos['monto']]);

        return response()->json($cita);
    }

    public function atender(Request $request, Cita $cita): JsonResponse
    {
        $this->autorizarPsicologo($request, $cita);

        if (! $cita->pagado) {
            return response()->json(['message' => 'Primero se debe registrar el pago de la sesión.'], 422);
        }

        $cita->update(['estado' => 'atendida']);
        Auditor::registrar('cita.atendida', $cita);

        return response()->json($cita);
    }

    public function disponibilidad(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'psicologo_id' => ['required', 'integer', $this->psicologoDelCentro($request)],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'fecha' => $datos['fecha'],
            'horas' => Disponibilidad::horasDelDia($datos['fecha']),
            'libres' => Disponibilidad::libres((int) $datos['psicologo_id'], $datos['fecha']),
        ]);
    }

    private function validar(Request $request, bool $nueva): array
    {
        $centroId = $request->user()->centro_id;

        return $request->validate([
            'paciente_id' => ['required', 'integer', Rule::exists('pacientes', 'id')->where('centro_id', $centroId)->whereNull('deleted_at')],
            'psicologo_id' => ['required', 'integer', $this->psicologoDelCentro($request)],
            'fecha' => ['required', 'date_format:Y-m-d', $nueva ? 'after_or_equal:today' : 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'motivo' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'espera_id' => ['nullable', 'integer', Rule::exists('lista_espera', 'id')->where('centro_id', $centroId)],
        ]);
    }

    private function psicologoDelCentro(Request $request)
    {
        return Rule::exists('users', 'id')
            ->where('centro_id', $request->user()->centro_id)
            ->where('role', 'psicologo')
            ->where('activo', true);
    }

    private function sinDisponibilidad(array $datos): JsonResponse
    {
        return response()->json([
            'message' => 'El psicólogo no tiene ese horario disponible. Elige un horario alternativo.',
            'errors' => ['hora' => ['Horario no disponible.']],
            'alternativas' => Disponibilidad::libres((int) $datos['psicologo_id'], $datos['fecha']),
        ], 422);
    }

    private function autorizarPsicologo(Request $request, Cita $cita): void
    {
        abort_if($request->user()->isPsicologo() && $cita->psicologo_id !== $request->user()->id, 403, 'Esta cita no te pertenece.');
    }
}
