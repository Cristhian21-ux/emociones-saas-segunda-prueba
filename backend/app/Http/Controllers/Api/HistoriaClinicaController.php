<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoriaClinica;
use App\Services\Auditor;
use App\Services\IaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Historia clínica digital: datos sensibles, solo psicólogo (las suyas) y admin. */
class HistoriaClinicaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            HistoriaClinica::with(['paciente:id,nombres,apellidos,dni', 'psicologo:id,name'])
                ->when($request->user()->isPsicologo(), fn ($q) => $q->where('psicologo_id', $request->user()->id))
                ->when($request->filled('paciente_id'), fn ($q) => $q->where('paciente_id', $request->integer('paciente_id')))
                ->latest()
                ->paginate(15)
        );
    }

    public function store(Request $request, IaClient $ia): JsonResponse
    {
        $centroId = $request->user()->centro_id;
        $datos = $request->validate([
            'paciente_id' => ['required', Rule::exists('pacientes', 'id')->where('centro_id', $centroId)],
            'cita_id' => ['nullable', Rule::exists('citas', 'id')->where('centro_id', $centroId)],
            'diagnostico' => ['required', 'string', 'min:5'],
            'observaciones' => ['nullable', 'string'],
            'proxima_cita_recomendada' => ['nullable', 'date', 'after:today'],
        ]);

        // Plan Premium: el microservicio Python sugiere la emoción dominante y el nivel de riesgo.
        if ($request->user()->centro->plan->analisis_emociones) {
            $analisis = $ia->analizarEmociones(trim($datos['diagnostico'].' '.($datos['observaciones'] ?? '')));
            $datos['emocion_detectada'] = $analisis['emocion_dominante'];
            $datos['nivel_riesgo'] = $analisis['nivel_riesgo'];
        }

        $historia = HistoriaClinica::create($datos + ['psicologo_id' => $request->user()->id]);
        Auditor::registrar('historia.creada', $historia);

        return response()->json($historia, 201);
    }

    public function show(Request $request, HistoriaClinica $historia): JsonResponse
    {
        abort_if($request->user()->isPsicologo() && $historia->psicologo_id !== $request->user()->id, 403, 'Esta historia no te pertenece.');
        Auditor::registrar('historia.consultada', $historia);

        return response()->json($historia->load(['paciente', 'psicologo:id,name']));
    }
}
