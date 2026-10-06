<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\ListaEspera;
use App\Models\Notificacion;
use Tests\TestCase;

class CitaTest extends TestCase
{
    private function datosCita($centro, $psicologo, array $extra = []): array
    {
        return $extra + [
            'paciente_id' => $this->paciente($centro)->id,
            'psicologo_id' => $psicologo->id,
            'fecha' => $this->diaHabil(2),
            'hora' => '10:00',
            'motivo' => 'Ansiedad laboral',
        ];
    }

    public function test_recepcion_agenda_una_cita_y_se_envia_confirmacion(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);

        $id = $this->postJson('/api/citas', $this->datosCita($centro, $psicologo))
            ->assertCreated()->assertJsonPath('estado', 'pendiente')->json('id');

        $this->assertTrue(Notificacion::where('cita_id', $id)->where('tipo', 'confirmacion')->where('estado', 'enviada')->exists());
    }

    public function test_se_programa_recordatorio_24_horas_antes(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);

        $id = $this->postJson('/api/citas', $this->datosCita($centro, $psicologo, ['fecha' => $this->diaHabil(4)]))->json('id');

        $this->assertTrue(Notificacion::where('cita_id', $id)->where('tipo', 'recordatorio')->where('estado', 'pendiente')->exists());
    }

    public function test_no_se_permite_doble_reserva_y_se_ofrecen_alternativas(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);
        $datos = $this->datosCita($centro, $psicologo);
        $this->postJson('/api/citas', $datos)->assertCreated();

        $alternativas = $this->postJson('/api/citas', $this->datosCita($centro, $psicologo, ['fecha' => $datos['fecha']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('hora')
            ->json('alternativas');

        $this->assertNotContains('10:00', $alternativas);
        $this->assertContains('11:00', $alternativas);
    }

    public function test_no_se_agenda_en_fecha_pasada(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);

        $this->postJson('/api/citas', $this->datosCita($centro, $psicologo, ['fecha' => now()->subDay()->toDateString()]))
            ->assertStatus(422)->assertJsonValidationErrors('fecha');
    }

    public function test_no_se_agenda_fuera_del_horario_de_atencion(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);

        $this->postJson('/api/citas', $this->datosCita($centro, $psicologo, ['hora' => '13:00']))->assertStatus(422);
    }

    public function test_cancelar_ofrece_el_cupo_al_primero_de_la_lista_de_espera(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);
        $cita = $this->cita($centro, $psicologo);
        $enEspera = $this->paciente($centro);
        ListaEspera::create(['centro_id' => $centro->id, 'paciente_id' => $enEspera->id, 'franja' => 'cualquiera']);

        $this->postJson("/api/citas/{$cita->id}/cancelar")
            ->assertOk()
            ->assertJsonPath('cupo_ofrecido_a', $enEspera->nombre_completo);

        $this->assertDatabaseHas('lista_espera', ['paciente_id' => $enEspera->id, 'estado' => 'notificado']);
    }

    public function test_una_cita_atendida_no_se_puede_cancelar(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $cita = $this->cita($centro, $this->usuario('psicologo', $centro), null, ['estado' => 'atendida']);

        $this->postJson("/api/citas/{$cita->id}/cancelar")->assertStatus(422);
    }

    public function test_reprogramar_cita_a_horario_libre(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);
        $cita = $this->cita($centro, $psicologo);

        $this->putJson("/api/citas/{$cita->id}", [
            'paciente_id' => $cita->paciente_id, 'psicologo_id' => $psicologo->id,
            'fecha' => $cita->fecha->toDateString(), 'hora' => '15:00', 'motivo' => 'Seguimiento',
        ])->assertOk()->assertJsonPath('hora', '15:00');
    }

    public function test_psicologo_solo_ve_sus_citas(): void
    {
        $centro = $this->centro();
        $yo = $this->comoUsuario('psicologo', $centro);
        $otro = $this->usuario('psicologo', $centro);
        $this->cita($centro, $yo);
        $this->cita($centro, $otro, null, ['hora' => '11:00']);

        $this->getJson('/api/citas')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_psicologo_no_puede_ver_cita_ajena(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('psicologo', $centro);
        $cita = $this->cita($centro, $this->usuario('psicologo', $centro));

        $this->getJson("/api/citas/{$cita->id}")->assertForbidden();
    }

    public function test_disponibilidad_excluye_horas_ocupadas(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);
        $cita = $this->cita($centro, $psicologo, null, ['hora' => '08:00']);

        $libres = $this->getJson("/api/agenda/disponibilidad?psicologo_id={$psicologo->id}&fecha={$cita->fecha->toDateString()}")
            ->assertOk()->json('libres');

        $this->assertNotContains('08:00', $libres);
        $this->assertContains('09:00', $libres);
    }

    public function test_marcar_no_agendada(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $cita = $this->cita($centro, $this->usuario('psicologo', $centro));

        $this->postJson("/api/citas/{$cita->id}/no-agendar")->assertOk();
        $this->assertSame('no_agendada', $cita->fresh()->estado);
    }

    public function test_agendar_desde_lista_de_espera_cierra_la_entrada(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);
        $paciente = $this->paciente($centro);
        $espera = ListaEspera::create(['centro_id' => $centro->id, 'paciente_id' => $paciente->id, 'franja' => 'manana']);

        $this->postJson('/api/citas', $this->datosCita($centro, $psicologo, ['paciente_id' => $paciente->id, 'espera_id' => $espera->id]))
            ->assertCreated();

        $this->assertSame('agendado', $espera->fresh()->estado);
        $this->assertSame(1, Cita::count());
    }
}
