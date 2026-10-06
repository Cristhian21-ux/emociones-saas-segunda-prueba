<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Notificacion::with('paciente:id,nombres,apellidos')
                ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
                ->latest('programada_para')
                ->paginate(20)
        );
    }

    public function enviarPendientes(): JsonResponse
    {
        return response()->json(['enviadas' => NotificacionService::enviarPendientes()]);
    }
}
