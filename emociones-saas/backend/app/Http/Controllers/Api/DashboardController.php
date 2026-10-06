<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Cita;
use App\Models\Evaluacion;
use App\Models\ListaEspera;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $citas = Cita::query()->when($usuario->isPsicologo(), fn ($q) => $q->where('psicologo_id', $usuario->id));

        return response()->json([
            'pacientes' => Paciente::count(),
            'citas_hoy' => (clone $citas)->whereDate('fecha', today())->whereNotIn('estado', Cita::ESTADOS_LIBRES)->count(),
            'citas_pendientes_pago' => (clone $citas)->where('pagado', false)->whereIn('estado', ['pendiente'])->count(),
            'ingresos_mes' => (float) (clone $citas)->where('pagado', true)
                ->whereBetween('pagado_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('monto'),
            'en_espera' => ListaEspera::where('estado', 'en_espera')->count(),
            'alertas_evaluacion' => Evaluacion::where('alerta', true)->count(),
            'citas_por_estado' => (clone $citas)->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado'),
        ]);
    }

    public function auditoria(Request $request): JsonResponse
    {
        return response()->json(
            Auditoria::with('usuario:id,name')
                ->when($request->filled('accion'), fn ($q) => $q->where('accion', 'like', $request->string('accion').'%'))
                ->latest('id')
                ->paginate(30)
        );
    }
}
