<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotMensaje;
use App\Services\IaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IaController extends Controller
{
    public function emociones(Request $request, IaClient $ia): JsonResponse
    {
        $datos = $request->validate(['texto' => ['required', 'string', 'min:3', 'max:5000']]);

        return response()->json($ia->analizarEmociones($datos['texto']));
    }

    /** Chatbot de ayuda: responde desde una base de conocimiento controlada (sin datos clínicos). */
    public function chatbot(Request $request, IaClient $ia): JsonResponse
    {
        $datos = $request->validate(['pregunta' => ['required', 'string', 'min:2', 'max:500']]);
        $usuario = $request->user();

        $resultado = $ia->chatbot(strip_tags($datos['pregunta']), $usuario->role);

        ChatbotMensaje::create([
            'centro_id' => $usuario->centro_id,
            'user_id' => $usuario->id,
            'pregunta' => $datos['pregunta'],
            'respuesta' => $resultado['respuesta'],
            'fuente' => $resultado['fuente'],
        ]);

        return response()->json($resultado);
    }
}
