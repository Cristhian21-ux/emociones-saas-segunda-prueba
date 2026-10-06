<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Monitoreo (Cap. 14): estado de la API, la base de datos y el servicio de IA. */
class HealthController extends Controller
{
    public function __invoke(IaClient $ia): JsonResponse
    {
        try {
            DB::select('select 1');
            $db = 'ok';
        } catch (Throwable) {
            $db = 'error';
        }

        return response()->json([
            'status' => $db === 'ok' ? 'ok' : 'degradado',
            'database' => $db,
            'ia_service' => $ia->disponible() ? 'ok' : 'no_disponible',
            'time' => now()->toIso8601String(),
        ], $db === 'ok' ? 200 : 503);
    }
}
