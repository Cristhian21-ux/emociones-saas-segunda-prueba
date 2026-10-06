<?php

namespace Tests\Feature;

use App\Models\ListaEspera;
use App\Models\Notificacion;
use Tests\TestCase;

class ListaEsperaNotificacionTest extends TestCase
{
    public function test_agregar_paciente_a_lista_de_espera(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);

        $this->postJson('/api/lista-espera', ['paciente_id' => $this->paciente($centro)->id, 'franja' => 'tarde'])
            ->assertCreated()->assertJsonPath('estado', 'en_espera');
    }

    public function test_no_se_duplica_en_lista_de_espera(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $paciente = $this->paciente($centro);
        ListaEspera::create(['centro_id' => $centro->id, 'paciente_id' => $paciente->id, 'franja' => 'cualquiera']);

        $this->postJson('/api/lista-espera', ['paciente_id' => $paciente->id, 'franja' => 'manana'])->assertStatus(422);
    }

    public function test_el_cupo_respeta_la_franja_preferida(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);
        $cita = $this->cita($centro, $psicologo, null, ['hora' => '09:00']);
        $tarde = $this->paciente($centro);
        ListaEspera::create(['centro_id' => $centro->id, 'paciente_id' => $tarde->id, 'franja' => 'tarde']);

        $this->postJson("/api/citas/{$cita->id}/cancelar")->assertOk()->assertJsonPath('cupo_ofrecido_a', null);
    }

    public function test_retirar_de_la_lista_de_espera(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $entrada = ListaEspera::create(['centro_id' => $centro->id, 'paciente_id' => $this->paciente($centro)->id, 'franja' => 'cualquiera']);

        $this->deleteJson("/api/lista-espera/{$entrada->id}")->assertOk()->assertJsonPath('estado', 'cancelado');
    }

    public function test_enviar_recordatorios_pendientes_vencidos(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $paciente = $this->paciente($centro);
        Notificacion::create([
            'centro_id' => $centro->id, 'paciente_id' => $paciente->id, 'tipo' => 'recordatorio',
            'destino' => $paciente->telefono, 'mensaje' => 'Recordatorio', 'programada_para' => now()->subMinute(),
        ]);

        $this->postJson('/api/notificaciones/enviar-pendientes')->assertOk()->assertJsonPath('enviadas', 1);
        $this->assertSame('enviada', Notificacion::first()->estado);
    }

    public function test_notificacion_sin_destino_queda_fallida(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $paciente = $this->paciente($centro, ['telefono' => null]);
        Notificacion::create([
            'centro_id' => $centro->id, 'paciente_id' => $paciente->id, 'tipo' => 'recordatorio',
            'destino' => null, 'mensaje' => 'Recordatorio', 'programada_para' => now()->subMinute(),
        ]);

        $this->postJson('/api/notificaciones/enviar-pendientes')->assertOk();
        $this->assertSame('fallida', Notificacion::first()->estado);
    }
}
