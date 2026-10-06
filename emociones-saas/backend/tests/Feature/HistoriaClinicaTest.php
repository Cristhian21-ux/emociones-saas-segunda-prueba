<?php

namespace Tests\Feature;

use App\Models\HistoriaClinica;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HistoriaClinicaTest extends TestCase
{
    public function test_psicologo_registra_historia_clinica(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('psicologo', $centro);

        $this->postJson('/api/historias', ['paciente_id' => $this->paciente($centro)->id, 'diagnostico' => 'Trastorno de ansiedad generalizada'])
            ->assertCreated()
            ->assertJsonPath('emocion_detectada', null);
    }

    public function test_recepcion_no_accede_a_historias_clinicas(): void
    {
        $this->comoUsuario('recepcionista');

        $this->getJson('/api/historias')->assertForbidden();
    }

    public function test_psicologo_no_lee_historias_de_otro_psicologo(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('psicologo', $centro);
        $otro = $this->usuario('psicologo', $centro);
        $historia = HistoriaClinica::create([
            'centro_id' => $centro->id, 'paciente_id' => $this->paciente($centro)->id,
            'psicologo_id' => $otro->id, 'diagnostico' => 'Duelo',
        ]);

        $this->getJson("/api/historias/{$historia->id}")->assertForbidden();
    }

    public function test_plan_premium_analiza_emociones_con_el_servicio_python(): void
    {
        Http::fake(['*/emociones/analizar' => Http::response(['emocion_dominante' => 'tristeza', 'nivel_riesgo' => 'medio', 'puntajes' => ['tristeza' => 0.7]])]);
        $centro = $this->centro('premium');
        $this->comoUsuario('psicologo', $centro);

        $this->postJson('/api/historias', [
            'paciente_id' => $this->paciente($centro)->id,
            'diagnostico' => 'Episodio depresivo',
            'observaciones' => 'Refiere tristeza y llanto frecuente',
        ])->assertCreated()->assertJsonPath('emocion_detectada', 'tristeza')->assertJsonPath('nivel_riesgo', 'medio');
    }

    public function test_si_el_servicio_python_falla_la_historia_se_guarda_igual(): void
    {
        $centro = $this->centro('premium');
        $this->comoUsuario('psicologo', $centro);

        $this->postJson('/api/historias', ['paciente_id' => $this->paciente($centro)->id, 'diagnostico' => 'Insomnio crónico'])
            ->assertCreated();

        $this->assertSame(1, HistoriaClinica::count());
    }
}
