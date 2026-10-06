<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auditor;
use App\Services\LimitesPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Gestión del personal del centro (solo administrador del centro). */
class PersonalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            User::where('centro_id', $request->user()->centro_id)
                ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'especialidad', 'activo'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'recepcionista', 'psicologo'])],
            'especialidad' => ['nullable', 'required_if:role,psicologo', 'string', 'max:120'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ]);

        $centro = $request->user()->centro;
        if ($datos['role'] === 'psicologo' && ! LimitesPlan::puedeAgregarPsicologo($centro)) {
            return response()->json(['message' => "Tu plan {$centro->plan->nombre} permite hasta {$centro->plan->max_psicologos} psicólogos."], 402);
        }

        $usuario = User::create($datos + ['centro_id' => $centro->id]);
        Auditor::registrar('personal.creado', $usuario, ['role' => $usuario->role]);

        return response()->json($usuario, 201);
    }

    /** Activar/desactivar en lugar de borrar: se conserva el historial. */
    public function alternarActivo(Request $request, User $usuario): JsonResponse
    {
        abort_if($usuario->centro_id !== $request->user()->centro_id, 404);
        abort_if($usuario->id === $request->user()->id, 422, 'No puedes desactivar tu propia cuenta.');

        if (! $usuario->activo && $usuario->isPsicologo() && ! LimitesPlan::puedeAgregarPsicologo($request->user()->centro)) {
            return response()->json(['message' => 'Tu plan no permite más psicólogos activos.'], 402);
        }

        $usuario->update(['activo' => ! $usuario->activo]);
        $usuario->tokens()->delete();
        Auditor::registrar($usuario->activo ? 'personal.activado' : 'personal.desactivado', $usuario);

        return response()->json($usuario->only(['id', 'name', 'activo']));
    }
}
