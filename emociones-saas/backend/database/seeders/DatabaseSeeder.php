<?php

namespace Database\Seeders;

use App\Models\Centro;
use App\Models\Paciente;
use App\Models\Plan;
use App\Models\User;
use App\Services\SuscripcionService;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración. Contraseña de todos los usuarios: Emociones2026
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlanSeeder::class);
        $clave = 'Emociones2026';

        User::create(['name' => 'Dueño de la plataforma', 'email' => 'plataforma@emociones.test', 'password' => $clave, 'role' => 'superadmin']);

        $centro = Centro::create([
            'nombre' => 'Centro Psicológico Emociones',
            'slug' => 'emociones-huancayo',
            'ruc' => '20123456789',
            'telefono' => '064123456',
            'email' => 'contacto@emociones.test',
            'plan_id' => Plan::where('codigo', 'premium')->value('id'),
        ]);
        SuscripcionService::activar($centro, $centro->plan, 'anual');

        $base = ['centro_id' => $centro->id, 'password' => $clave];
        User::create($base + ['name' => 'Administración Emociones', 'email' => 'admin@emociones.test', 'role' => 'admin']);
        User::create($base + ['name' => 'Rosa Quispe', 'email' => 'recepcion@emociones.test', 'role' => 'recepcionista']);
        User::create($base + ['name' => 'Ps. Carlos Mendoza', 'email' => 'psicologo@emociones.test', 'role' => 'psicologo', 'especialidad' => 'Psicología clínica']);
        User::create($base + ['name' => 'Ps. Lucía Huamán', 'email' => 'psicologa@emociones.test', 'role' => 'psicologo', 'especialidad' => 'Terapia cognitivo-conductual']);

        Paciente::factory()->count(12)->create(['centro_id' => $centro->id]);

        // Segundo tenant en plan gratuito para demostrar el aislamiento de datos.
        $otro = Centro::create(['nombre' => 'Consultorio Bienestar', 'slug' => 'bienestar', 'plan_id' => Plan::where('codigo', 'gratuito')->value('id')]);
        SuscripcionService::activar($otro, $otro->plan);
        User::create(['centro_id' => $otro->id, 'name' => 'Admin Bienestar', 'email' => 'admin@bienestar.test', 'password' => $clave, 'role' => 'admin']);
    }
}
