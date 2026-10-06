<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Centro;
use App\Services\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Panel del dueño de la plataforma SaaS (superadmin): todos los centros suscritos. */
class PlataformaController extends Controller
{
    public function centros(): JsonResponse
    {
        return response()->json(
            Centro::with('plan:id,codigo,nombre')->withCount('usuarios')->orderBy('nombre')->get()
        );
    }

    public function metricas(): JsonResponse
    {
        $centros = Centro::with('plan', 'suscripcionActiva')->get();

        return response()->json([
            'centros' => $centros->count(),
            'activos' => $centros->where('estado', 'activo')->count(),
            'por_plan' => $centros->groupBy(fn ($c) => $c->plan->codigo)->map->count(),
            'mrr' => round($centros->sum(fn ($c) => $c->suscripcionActiva
                ? ($c->suscripcionActiva->ciclo === 'anual' ? $c->suscripcionActiva->monto / 12 : $c->suscripcionActiva->monto)
                : 0), 2),
        ]);
    }

    public function cambiarEstado(Request $request, Centro $centro): JsonResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(['activo', 'suspendido'])]]);
        $centro->update($datos);
        Auditor::registrar('centro.'.$datos['estado'], $centro, [], $centro->id);

        return response()->json($centro);
    }
}
