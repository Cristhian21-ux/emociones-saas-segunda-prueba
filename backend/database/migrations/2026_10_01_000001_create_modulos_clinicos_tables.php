<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos del Centro Psicológico Emociones (BPM TO-BE): pacientes, citas,
 * lista de espera, notificaciones e historia clínica. Todas las tablas llevan
 * centro_id para aislar los datos de cada tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->constrained('centros')->cascadeOnDelete();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('dni', 8);
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('contacto_emergencia')->nullable();
            $table->text('antecedentes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['centro_id', 'dni']);
        });

        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->constrained('centros')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('psicologo_id')->constrained('users');
            $table->foreignId('recepcionista_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('hora', 5);
            $table->string('motivo');
            $table->enum('estado', ['pendiente', 'confirmada', 'atendida', 'cancelada', 'no_agendada'])->default('pendiente');
            $table->decimal('monto', 8, 2)->nullable();
            $table->boolean('pagado')->default(false);
            $table->string('comprobante_numero')->nullable();
            $table->string('metodo_pago')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['centro_id', 'comprobante_numero']);
            $table->index(['psicologo_id', 'fecha']);
        });

        Schema::create('lista_espera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->constrained('centros')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('psicologo_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_preferida')->nullable();
            $table->enum('franja', ['cualquiera', 'manana', 'tarde'])->default('cualquiera');
            $table->string('motivo')->nullable();
            $table->enum('estado', ['en_espera', 'notificado', 'agendado', 'cancelado'])->default('en_espera');
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->date('cupo_fecha')->nullable();
            $table->string('cupo_hora', 5)->nullable();
            $table->foreignId('cupo_psicologo_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notificado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->constrained('centros')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->cascadeOnDelete();
            $table->enum('tipo', ['confirmacion', 'recordatorio', 'cupo_liberado', 'cancelacion']);
            $table->enum('canal', ['whatsapp', 'sms', 'correo'])->default('whatsapp');
            $table->string('destino')->nullable();
            $table->text('mensaje');
            $table->enum('estado', ['pendiente', 'enviada', 'fallida', 'omitida'])->default('pendiente');
            $table->timestamp('programada_para');
            $table->timestamp('enviada_at')->nullable();
            $table->timestamps();
        });

        Schema::create('historias_clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->constrained('centros')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->foreignId('psicologo_id')->constrained('users');
            $table->text('diagnostico');
            $table->text('observaciones')->nullable();
            $table->date('proxima_cita_recomendada')->nullable();
            // Resultado del microservicio Python de análisis de emociones (plan Premium).
            $table->string('emocion_detectada')->nullable();
            $table->string('nivel_riesgo')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->constrained('centros')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('psicologo_id')->constrained('users');
            $table->enum('instrumento', ['phq9', 'gad7']);
            $table->json('respuestas');
            $table->unsignedTinyInteger('puntaje');
            $table->string('severidad');
            $table->boolean('alerta')->default(false);
            $table->text('interpretacion');
            $table->enum('fuente', ['ia', 'local'])->default('ia');
            $table->timestamps();
        });

        Schema::create('chatbot_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->nullable()->constrained('centros')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('pregunta');
            $table->text('respuesta');
            $table->string('fuente');
            $table->timestamps();
        });

        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->nullable()->constrained('centros')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion');
            $table->string('entidad')->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->json('detalle')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['auditoria', 'chatbot_mensajes', 'evaluaciones', 'historias_clinicas', 'notificaciones', 'lista_espera', 'citas', 'pacientes'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
