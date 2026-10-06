<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Núcleo SaaS: cada centro psicológico es un "tenant" que contrata un plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // gratuito, vip, premium
            $table->string('nombre');
            $table->decimal('precio_mensual', 8, 2)->default(0);
            $table->decimal('precio_anual', 8, 2)->default(0);
            $table->unsignedInteger('max_psicologos');
            $table->unsignedInteger('max_pacientes');
            $table->boolean('evaluaciones')->default(false);
            $table->boolean('analisis_emociones')->default(false);
            $table->unsignedTinyInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('centros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('ruc', 11)->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('plan_id')->constrained('planes');
            $table->enum('estado', ['activo', 'suspendido'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->constrained('centros')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('planes');
            $table->enum('ciclo', ['mensual', 'anual'])->default('mensual');
            $table->decimal('monto', 8, 2)->default(0);
            $table->enum('estado', ['activa', 'cancelada', 'vencida'])->default('activa');
            $table->timestamp('inicia_at');
            $table->timestamp('vence_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
        Schema::dropIfExists('centros');
        Schema::dropIfExists('planes');
    }
};
