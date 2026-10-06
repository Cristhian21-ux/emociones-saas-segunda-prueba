<?php

namespace Tests;

use App\Models\Centro;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
        // Sin un Http::fake explícito el microservicio Python "no responde"
        // (la petición lanza excepción) y se prueba el modo degradado.
        Http::preventStrayRequests();
    }

    protected function centro(string $plan = 'gratuito'): Centro
    {
        return Centro::factory()->plan($plan)->create();
    }

    protected function usuario(string $rol = 'admin', ?Centro $centro = null): User
    {
        return User::factory()->rol($rol)->create(['centro_id' => ($centro ?? $this->centro())->id]);
    }

    protected function comoUsuario(string $rol = 'admin', ?Centro $centro = null): User
    {
        $usuario = $this->usuario($rol, $centro);
        Sanctum::actingAs($usuario);

        return $usuario;
    }

    protected function paciente(Centro $centro, array $extra = []): Paciente
    {
        return Paciente::factory()->create(['centro_id' => $centro->id] + $extra);
    }

    /** Próximo día laborable (lunes a sábado) a partir de mañana. */
    protected function diaHabil(int $desde = 1): string
    {
        $fecha = now()->addDays($desde);
        while ($fecha->dayOfWeekIso === 7) {
            $fecha->addDay();
        }

        return $fecha->toDateString();
    }

    protected function cita(Centro $centro, User $psicologo, ?Paciente $paciente = null, array $extra = []): Cita
    {
        return Cita::factory()->create([
            'centro_id' => $centro->id,
            'psicologo_id' => $psicologo->id,
            'paciente_id' => ($paciente ?? $this->paciente($centro))->id,
            'fecha' => $this->diaHabil(3),
        ] + $extra);
    }
}
