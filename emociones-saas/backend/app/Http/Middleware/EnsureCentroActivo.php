<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bloquea a usuarios desactivados y a centros suspendidos por la plataforma. */
class EnsureCentroActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->activo) {
            return response()->json(['message' => 'Tu cuenta está desactivada.'], 403);
        }

        if ($usuario?->centro && ! $usuario->centro->estaActivo()) {
            return response()->json(['message' => 'El centro está suspendido. Contacta a soporte.'], 403);
        }

        return $next($request);
    }
}
