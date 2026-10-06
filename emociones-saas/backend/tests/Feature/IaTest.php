<?php

namespace Tests\Feature;

use App\Models\ChatbotMensaje;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaTest extends TestCase
{
    public function test_chatbot_responde_y_guarda_la_conversacion(): void
    {
        Http::fake(['*/chatbot/responder' => Http::response(['respuesta' => 'Ve a Citas > Nueva cita.'])]);
        $this->comoUsuario('recepcionista');

        $this->postJson('/api/chatbot', ['pregunta' => '¿Cómo agendo una cita?'])
            ->assertOk()->assertJsonPath('fuente', 'ia')->assertJsonPath('respuesta', 'Ve a Citas > Nueva cita.');

        $this->assertSame(1, ChatbotMensaje::count());
    }

    public function test_chatbot_responde_en_local_si_python_cae(): void
    {
        Http::fake(['*' => Http::response(null, 500)]);
        $this->comoUsuario('recepcionista');

        $this->postJson('/api/chatbot', ['pregunta' => 'hola'])->assertOk()
            ->assertJsonPath('fuente', 'local')
            ->assertJson(fn ($json) => $json->where('respuesta', fn ($r) => str_starts_with($r, '¡Hola!'))->etc());

        $this->postJson('/api/chatbot', ['pregunta' => '¿Cómo agendo una cita?'])->assertOk()
            ->assertJson(fn ($json) => $json->where('respuesta', fn ($r) => str_contains($r, 'Nueva cita'))->etc());
    }

    public function test_analisis_de_emociones_en_local_si_python_cae(): void
    {
        Http::fake(['*' => Http::response(null, 500)]);
        $this->comoUsuario('psicologo', $this->centro('premium'));

        $this->postJson('/api/ia/emociones', ['texto' => 'Me siento muy ansioso y preocupado'])
            ->assertOk()->assertJsonPath('fuente', 'local')->assertJsonPath('emocion_dominante', 'ansiedad');
    }

    public function test_el_chatbot_envia_la_llave_del_servicio(): void
    {
        Http::fake(['*/chatbot/responder' => Http::response(['respuesta' => 'ok'])]);
        config(['services.ia.key' => 'llave-secreta']);
        $this->comoUsuario('admin');

        $this->postJson('/api/chatbot', ['pregunta' => 'planes'])->assertOk();

        Http::assertSent(fn ($request) => $request->hasHeader('X-API-Key', 'llave-secreta'));
    }

    public function test_analisis_de_emociones_requiere_plan_premium(): void
    {
        $this->comoUsuario('psicologo', $this->centro('vip'));

        $this->postJson('/api/ia/emociones', ['texto' => 'Me siento muy ansioso'])->assertStatus(402);
    }

    public function test_analisis_de_emociones_en_premium(): void
    {
        Http::fake(['*/emociones/analizar' => Http::response(['emocion_dominante' => 'ansiedad', 'nivel_riesgo' => 'bajo', 'puntajes' => []])]);
        $this->comoUsuario('psicologo', $this->centro('premium'));

        $this->postJson('/api/ia/emociones', ['texto' => 'Me siento muy ansioso'])
            ->assertOk()->assertJsonPath('emocion_dominante', 'ansiedad');
    }
}
