<?php

namespace Tests\Feature;

use Tests\TestCase;

class PagoTest extends TestCase
{
    public function test_cobrar_confirma_la_cita(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $cita = $this->cita($centro, $this->usuario('psicologo', $centro));

        $this->postJson("/api/citas/{$cita->id}/cobrar", ['monto' => 80, 'comprobante_numero' => 'B001-0001', 'metodo_pago' => 'yape'])
            ->assertOk()
            ->assertJsonPath('pagado', true)
            ->assertJsonPath('estado', 'confirmada');
    }

    public function test_no_se_cobra_dos_veces(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $cita = $this->cita($centro, $this->usuario('psicologo', $centro), null, ['pagado' => true]);

        $this->postJson("/api/citas/{$cita->id}/cobrar", ['monto' => 80, 'comprobante_numero' => 'B001-0002', 'metodo_pago' => 'efectivo'])
            ->assertStatus(422);
    }

    public function test_comprobante_repetido_se_rechaza(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologo = $this->usuario('psicologo', $centro);
        $this->cita($centro, $psicologo, null, ['comprobante_numero' => 'B001-0003', 'pagado' => true]);
        $cita = $this->cita($centro, $psicologo, null, ['hora' => '11:00']);

        $this->postJson("/api/citas/{$cita->id}/cobrar", ['monto' => 80, 'comprobante_numero' => 'B001-0003', 'metodo_pago' => 'plin'])
            ->assertStatus(422)->assertJsonValidationErrors('comprobante_numero');
    }

    public function test_monto_debe_ser_positivo(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $cita = $this->cita($centro, $this->usuario('psicologo', $centro));

        $this->postJson("/api/citas/{$cita->id}/cobrar", ['monto' => -5, 'comprobante_numero' => 'B001-9', 'metodo_pago' => 'yape'])
            ->assertStatus(422)->assertJsonValidationErrors('monto');
    }

    public function test_no_se_atiende_una_cita_sin_pago(): void
    {
        $centro = $this->centro();
        $psicologo = $this->comoUsuario('psicologo', $centro);
        $cita = $this->cita($centro, $psicologo);

        $this->postJson("/api/citas/{$cita->id}/atender")->assertStatus(422);
    }

    public function test_psicologo_atiende_una_cita_pagada(): void
    {
        $centro = $this->centro();
        $psicologo = $this->comoUsuario('psicologo', $centro);
        $cita = $this->cita($centro, $psicologo, null, ['pagado' => true, 'estado' => 'confirmada']);

        $this->postJson("/api/citas/{$cita->id}/atender")->assertOk()->assertJsonPath('estado', 'atendida');
    }
}
