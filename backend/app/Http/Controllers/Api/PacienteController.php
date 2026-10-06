<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Services\Auditor;
use App\Services\LimitesPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $buscar = $request->string('buscar')->trim()->toString();

        $pacientes = Paciente::query()
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombres', 'like', "%$buscar%")
                ->orWhere('apellidos', 'like', "%$buscar%")
                ->orWhere('dni', 'like', "%$buscar%")))
            ->orderBy('apellidos')
            ->paginate(15);

        return response()->json($pacientes);
    }

    public function store(Request $request): JsonResponse
    {
        if (! LimitesPlan::puedeAgregarPaciente($request->user()->centro)) {
            return response()->json(['message' => 'Alcanzaste el límite de pacientes de tu plan.'], 402);
        }

        $paciente = Paciente::create($this->validar($request));
        Auditor::registrar('paciente.creado', $paciente);

        return response()->json($paciente, 201);
    }

    public function show(Paciente $paciente): JsonResponse
    {
        return response()->json($paciente->load(['citas.psicologo:id,name']));
    }

    public function update(Request $request, Paciente $paciente): JsonResponse
    {
        $paciente->update($this->validar($request, $paciente));
        Auditor::registrar('paciente.actualizado', $paciente);

        return response()->json($paciente);
    }

    /** Borrado lógico: se conserva la trazabilidad (soft delete). */
    public function destroy(Paciente $paciente): JsonResponse
    {
        $paciente->delete();
        Auditor::registrar('paciente.eliminado', $paciente);

        return response()->json(null, 204);
    }

    private function validar(Request $request, ?Paciente $paciente = null): array
    {
        $centroId = $request->user()->centro_id;

        return $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'dni' => ['required', 'digits:8', Rule::unique('pacientes')->where('centro_id', $centroId)->ignore($paciente?->id)],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'telefono' => ['nullable', 'regex:/^9\d{8}$/'],
            'email' => ['nullable', 'email'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'contacto_emergencia' => ['nullable', 'string', 'max:150'],
            'antecedentes' => ['nullable', 'string'],
        ], [
            'telefono.regex' => 'El teléfono debe ser un celular peruano de 9 dígitos que empiece con 9.',
        ]);
    }
}
