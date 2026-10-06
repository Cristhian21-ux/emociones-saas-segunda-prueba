<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Centro;
use App\Models\Plan;
use App\Models\User;
use App\Services\Auditor;
use App\Services\SuscripcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /** Alta de un centro (tenant) con su administrador: recibe el plan Gratuito automáticamente. */
    public function registrarCentro(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'centro' => ['required', 'string', 'max:120'],
            'ruc' => ['nullable', 'digits:11'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        [$centro, $usuario] = DB::transaction(function () use ($datos) {
            $centro = Centro::create([
                'nombre' => $datos['centro'],
                'slug' => Str::slug($datos['centro']).'-'.Str::lower(Str::random(5)),
                'ruc' => $datos['ruc'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'email' => $datos['email'],
                'plan_id' => Plan::where('codigo', 'gratuito')->value('id'),
            ]);
            SuscripcionService::activar($centro, $centro->plan);

            $usuario = User::create([
                'centro_id' => $centro->id,
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => $datos['password'],
                'role' => 'admin',
            ]);

            return [$centro, $usuario];
        });

        Auditor::registrar('centro.registrado', $centro, [], $centro->id, $usuario->id);

        return response()->json([
            'token' => $usuario->createToken('spa')->plainTextToken,
            'usuario' => $this->perfil($usuario),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = User::where('email', $datos['email'])->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            Auditor::registrar('login.fallido', null, ['email' => $datos['email']], $usuario?->centro_id, $usuario?->id);

            return response()->json(['message' => 'Credenciales incorrectas.', 'errors' => ['email' => ['Credenciales incorrectas.']]], 422);
        }

        if (! $usuario->activo) {
            return response()->json(['message' => 'Tu cuenta está desactivada.'], 403);
        }

        Auditor::registrar('login', $usuario, [], $usuario->centro_id, $usuario->id);

        return response()->json([
            'token' => $usuario->createToken('spa', ['*'], now()->addHours(12))->plainTextToken,
            'usuario' => $this->perfil($usuario),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        Auditor::registrar('logout');

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->perfil($request->user()));
    }

    public function actualizarPerfil(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        $usuario->update($datos);
        Auditor::registrar('perfil.actualizado', $usuario);

        return response()->json($this->perfil($usuario->fresh()));
    }

    private function perfil(User $usuario): array
    {
        $usuario->loadMissing('centro.plan');

        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => $usuario->role,
            'especialidad' => $usuario->especialidad,
            'centro' => $usuario->centro ? [
                'id' => $usuario->centro->id,
                'nombre' => $usuario->centro->nombre,
                'plan' => $usuario->centro->plan->only(['codigo', 'nombre', 'evaluaciones', 'analisis_emociones']),
            ] : null,
        ];
    }
}
