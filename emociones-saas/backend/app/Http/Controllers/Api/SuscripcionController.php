<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Auditor;
use App\Services\LimitesPlan;
use App\Services\SuscripcionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuscripcionController extends Controller
{
    public function planes(): JsonResponse
    {
        return response()->json(Plan::orderBy('orden')->get());
    }

    public function actual(Request $request): JsonResponse
    {
        $centro = $request->user()->centro->load(['plan', 'suscripcionActiva']);

        return response()->json([
            'plan' => $centro->plan,
            'suscripcion' => $centro->suscripcionActiva,
            'uso' => LimitesPlan::uso($centro),
        ]);
    }

    /** Cambio de plan. El cobro se delega a la pasarela (no se guardan datos de tarjeta). */
    public function cambiar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'plan' => ['required', Rule::exists('planes', 'codigo')],
            'ciclo' => ['required', Rule::in(['mensual', 'anual'])],
        ]);

        $centro = $request->user()->centro;
        $plan = Plan::where('codigo', $datos['plan'])->first();

        try {
            SuscripcionService::validarCambio($centro, $plan);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $suscripcion = SuscripcionService::activar($centro, $plan, $datos['ciclo']);
        Auditor::registrar('suscripcion.cambiada', $suscripcion, ['plan' => $plan->codigo, 'ciclo' => $datos['ciclo']]);

        return response()->json($suscripcion->load('plan'));
    }

    public function cancelar(Request $request): JsonResponse
    {
        try {
            $suscripcion = SuscripcionService::cancelar($request->user()->centro);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        Auditor::registrar('suscripcion.cancelada', $suscripcion);

        return response()->json($suscripcion->load('plan'));
    }
}
