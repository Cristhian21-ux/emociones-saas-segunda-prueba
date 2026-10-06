<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cliente del microservicio Python (FastAPI) de inteligencia emocional:
 * análisis de emociones, calificación de pruebas y chatbot. Si el servicio
 * falla, cada método usa su versión local en PHP (RNF-12).
 */
class IaClient
{
    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.ia.url'), '/'))
            ->withHeaders(['X-API-Key' => (string) config('services.ia.key')])
            ->timeout((int) config('services.ia.timeout', 5))
            ->acceptJson();
    }

    public function disponible(): bool
    {
        try {
            return $this->http()->get('/health')->successful();
        } catch (Throwable) {
            return false;
        }
    }

    public function analizarEmociones(string $texto): array
    {
        try {
            $respuesta = $this->http()->post('/emociones/analizar', ['texto' => $texto]);
            if ($respuesta->successful()) {
                return $respuesta->json() + ['fuente' => 'ia'];
            }
        } catch (Throwable $e) {
            Log::warning('IA no disponible (emociones): '.$e->getMessage());
        }

        return IaLocal::analizar($texto) + ['fuente' => 'local'];
    }

    public function puntuarEvaluacion(string $instrumento, array $respuestas): array
    {
        try {
            $respuesta = $this->http()->post('/evaluaciones/puntuar', compact('instrumento', 'respuestas'));
            if ($respuesta->successful()) {
                return $respuesta->json() + ['fuente' => 'ia'];
            }
        } catch (Throwable $e) {
            Log::warning('IA no disponible (evaluación): '.$e->getMessage());
        }

        return EvaluacionLocal::puntuar($instrumento, $respuestas);
    }

    public function chatbot(string $pregunta, string $rol): array
    {
        try {
            $respuesta = $this->http()->post('/chatbot/responder', compact('pregunta', 'rol'));
            if ($respuesta->successful()) {
                return ['respuesta' => $respuesta->json('respuesta'), 'fuente' => 'ia'];
            }
        } catch (Throwable $e) {
            Log::warning('IA no disponible (chatbot): '.$e->getMessage());
        }

        return ['respuesta' => IaLocal::responder($pregunta)['respuesta'], 'fuente' => 'local'];
    }
}
