<?php

namespace Tests\Unit;

use App\Services\Disponibilidad;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DisponibilidadTest extends TestCase
{
    public function test_un_dia_laborable_ofrece_nueve_horas_sin_el_almuerzo(): void
    {
        $lunes = Carbon::parse('next monday')->toDateString();
        $horas = Disponibilidad::horasDelDia($lunes);

        $this->assertCount(9, $horas);
        $this->assertSame('08:00', $horas[0]);
        $this->assertNotContains('13:00', $horas);
    }

    public function test_el_domingo_no_se_atiende(): void
    {
        $this->assertSame([], Disponibilidad::horasDelDia(Carbon::parse('next sunday')->toDateString()));
    }

    public function test_una_cita_ocupa_su_hora(): void
    {
        $centro = $this->centro();
        $psicologo = $this->usuario('psicologo', $centro);
        $cita = $this->cita($centro, $psicologo, null, ['hora' => '09:00']);

        $libres = Disponibilidad::libres($psicologo->id, $cita->fecha->toDateString());

        $this->assertNotContains('09:00', $libres);
        $this->assertContains('10:00', $libres);
    }

    public function test_una_cita_cancelada_libera_la_hora(): void
    {
        $centro = $this->centro();
        $psicologo = $this->usuario('psicologo', $centro);
        $cita = $this->cita($centro, $psicologo, null, ['hora' => '09:00', 'estado' => 'cancelada']);

        $this->assertTrue(Disponibilidad::estaLibre($psicologo->id, $cita->fecha->toDateString(), '09:00'));
    }

    public function test_una_hora_fuera_del_horario_no_esta_libre(): void
    {
        $centro = $this->centro();
        $psicologo = $this->usuario('psicologo', $centro);

        $this->assertFalse(Disponibilidad::estaLibre($psicologo->id, $this->diaHabil(), '20:00'));
    }
}
