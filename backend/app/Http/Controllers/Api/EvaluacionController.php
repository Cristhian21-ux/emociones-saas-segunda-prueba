<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Services\Auditor;
use App\Services\EvaluacionLocal;
use App\Services\IaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pruebas psicológicas estandarizadas calificadas automáticamente (planes VIP y Premium). */
class EvaluacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Evaluacion::with('paciente:id,nombres,apellidos')
                ->when($request->filled('paciente_id'), fn ($q) => $q->where('paciente_id', $request->integer('paciente_id')))
                ->latest()
                ->paginate(15)
        );
    }

    public function store(Request $request, IaClient $ia): JsonResponse
    {
        $instrumento = $request->input('instrumento');
        $items = EvaluacionLocal::ITEMS[$instrumento] ?? 0;

        $datos = $request->validate([
            'paciente_id' => ['required', Rule::exists('pacientes', 'id')->where('centro_id', $request->user()->centro_id)],
            'instrumento' => ['required', Rule::in(array_keys(EvaluacionLocal::ITEMS))],
            'respuestas' => ['required', 'array', "size:$items"],
            'respuestas.*' => ['required', 'integer', 'between:0,3'],
        ]);

        $respuestas = array_map('intval', array_values($datos['respuestas']));
        $resultado = $ia->puntuarEvaluacion($datos['instrumento'], $respuestas);

        $evaluacion = Evaluacion::create([
            'paciente_id' => $datos['paciente_id'],
            'psicologo_id' => $request->user()->id,
            'instrumento' => $datos['instrumento'],
            'respuestas' => $respuestas,
            'puntaje' => $resultado['puntaje'],
            'severidad' => $resultado['severidad'],
            'alerta' => $resultado['alerta'],
            'interpretacion' => $resultado['interpretacion'],
            'fuente' => $resultado['fuente'] === 'ia' ? 'ia' : 'local',
        ]);

        Auditor::registrar('evaluacion.registrada', $evaluacion, ['severidad' => $evaluacion->severidad]);

        return response()->json($evaluacion, 201);
    }
}
