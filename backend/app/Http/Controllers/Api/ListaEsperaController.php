<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListaEspera;
use App\Services\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListaEsperaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            ListaEspera::with(['paciente:id,nombres,apellidos', 'psicologo:id,name'])
                ->whereIn('estado', ['en_espera', 'notificado'])
                ->orderBy('created_at')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $centroId = $request->user()->centro_id;
        $datos = $request->validate([
            'paciente_id' => ['required', Rule::exists('pacientes', 'id')->where('centro_id', $centroId)],
            'psicologo_id' => ['nullable', Rule::exists('users', 'id')->where('centro_id', $centroId)->where('role', 'psicologo')],
            'fecha_preferida' => ['nullable', 'date', 'after_or_equal:today'],
            'franja' => ['required', Rule::in(['cualquiera', 'manana', 'tarde'])],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $yaEspera = ListaEspera::where('paciente_id', $datos['paciente_id'])->where('estado', 'en_espera')->exists();
        if ($yaEspera) {
            return response()->json(['message' => 'El paciente ya está en la lista de espera.'], 422);
        }

        $entrada = ListaEspera::create($datos);
        Auditor::registrar('espera.agregado', $entrada);

        return response()->json($entrada->fresh(), 201);
    }

    public function destroy(ListaEspera $listaEspera): JsonResponse
    {
        $listaEspera->update(['estado' => 'cancelado']);
        Auditor::registrar('espera.retirado', $listaEspera);

        return response()->json($listaEspera);
    }
}
