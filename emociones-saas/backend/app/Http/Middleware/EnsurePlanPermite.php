<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Funcionalidades por plan SaaS: uso `plan:evaluaciones` o `plan:analisis_emociones`. */
class EnsurePlanPermite
{
    public function handle(Request $request, Closure $next, string $funcion): Response
    {
        $plan = $request->user()?->centro?->plan;

        if (! $plan || ! $plan->permite($funcion)) {
            return response()->json([
                'message' => 'Tu plan actual no incluye esta funcionalidad. Mejora tu suscripción.',
                'funcion' => $funcion,
            ], 402);
        }

        return $next($request);
    }
}
