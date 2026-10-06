<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Centro;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private function registro(array $extra = []): array
    {
        return $extra + [
            'centro' => 'Centro Mente Sana',
            'name' => 'Ana Pérez',
            'email' => 'ana@mentesana.test',
            'password' => 'Clave12345',
            'password_confirmation' => 'Clave12345',
        ];
    }

    public function test_registrar_un_centro_le_asigna_el_plan_gratuito(): void
    {
        $this->postJson('/api/auth/registro', $this->registro())
            ->assertCreated()
            ->assertJsonPath('usuario.role', 'admin')
            ->assertJsonPath('usuario.centro.plan.codigo', 'gratuito')
            ->assertJsonStructure(['token']);

        $centro = Centro::firstWhere('nombre', 'Centro Mente Sana');
        $this->assertSame('activa', $centro->suscripcionActiva->estado);
    }

    public function test_registro_rechaza_correo_duplicado(): void
    {
        $this->usuario('admin')->update(['email' => 'ana@mentesana.test']);

        $this->postJson('/api/auth/registro', $this->registro())->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_registro_exige_contrasena_segura(): void
    {
        $this->postJson('/api/auth/registro', $this->registro(['password' => '123', 'password_confirmation' => '123']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_login_correcto_devuelve_token_y_perfil(): void
    {
        $usuario = $this->usuario('recepcionista');

        $this->postJson('/api/auth/login', ['email' => $usuario->email, 'password' => 'Clave12345'])
            ->assertOk()
            ->assertJsonPath('usuario.email', $usuario->email)
            ->assertJsonStructure(['token']);
    }

    public function test_login_con_clave_incorrecta_falla_y_se_audita(): void
    {
        $usuario = $this->usuario();

        $this->postJson('/api/auth/login', ['email' => $usuario->email, 'password' => 'otraClave99'])->assertStatus(422);

        $this->assertTrue(Auditoria::withoutGlobalScopes()->where('accion', 'login.fallido')->exists());
    }

    public function test_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $usuario = $this->usuario();
        $usuario->update(['activo' => false]);

        $this->postJson('/api/auth/login', ['email' => $usuario->email, 'password' => 'Clave12345'])->assertForbidden();
    }

    public function test_sin_token_la_api_responde_401(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_me_devuelve_el_usuario_autenticado(): void
    {
        $usuario = $this->comoUsuario('psicologo');

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('id', $usuario->id)->assertJsonPath('role', 'psicologo');
    }

    public function test_logout_revoca_el_token(): void
    {
        $usuario = $this->usuario();
        $token = $usuario->createToken('spa')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertSame(0, $usuario->tokens()->count());
    }

    public function test_actualizar_perfil(): void
    {
        $this->comoUsuario('psicologo');

        $this->putJson('/api/perfil', ['name' => 'Ps. Nuevo Nombre', 'especialidad' => 'Neuropsicología'])
            ->assertOk()
            ->assertJsonPath('name', 'Ps. Nuevo Nombre');
    }

    public function test_centro_suspendido_no_puede_usar_la_api(): void
    {
        $centro = $this->centro();
        $centro->update(['estado' => 'suspendido']);
        Sanctum::actingAs($this->usuario('admin', $centro));

        $this->getJson('/api/dashboard')->assertForbidden();
    }
}
