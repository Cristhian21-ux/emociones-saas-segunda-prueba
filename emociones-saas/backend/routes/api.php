<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvaluacionController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HistoriaClinicaController;
use App\Http\Controllers\Api\IaController;
use App\Http\Controllers\Api\ListaEsperaController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\PersonalController;
use App\Http\Controllers\Api\PlataformaController;
use App\Http\Controllers\Api\SuscripcionController;
use Illuminate\Support\Facades\Route;

// Públicas
Route::get('/health', HealthController::class);
Route::get('/planes', [SuscripcionController::class, 'planes']);
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/registro', [AuthController::class, 'registrarCentro']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'centro.activo'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/perfil', [AuthController::class, 'actualizarPerfil']);
    Route::post('/chatbot', [IaController::class, 'chatbot'])->middleware('throttle:30,1');

    // Plataforma SaaS (dueño del servicio)
    Route::middleware('rol:superadmin')->prefix('plataforma')->group(function () {
        Route::get('/centros', [PlataformaController::class, 'centros']);
        Route::get('/metricas', [PlataformaController::class, 'metricas']);
        Route::patch('/centros/{centro}/estado', [PlataformaController::class, 'cambiarEstado']);
    });

    // Todo el personal del centro
    Route::middleware('rol:admin,recepcionista,psicologo')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/agenda/disponibilidad', [CitaController::class, 'disponibilidad']);
        Route::get('/pacientes', [PacienteController::class, 'index']);
        Route::get('/pacientes/{paciente}', [PacienteController::class, 'show']);
        Route::get('/citas', [CitaController::class, 'index']);
        Route::get('/citas/{cita}', [CitaController::class, 'show']);
        Route::get('/personal', [PersonalController::class, 'index']);
    });

    // Recepción / admisión (Lane 2 del BPM)
    Route::middleware('rol:admin,recepcionista')->group(function () {
        Route::post('/pacientes', [PacienteController::class, 'store']);
        Route::put('/pacientes/{paciente}', [PacienteController::class, 'update']);
        Route::delete('/pacientes/{paciente}', [PacienteController::class, 'destroy']);
        Route::post('/citas', [CitaController::class, 'store']);
        Route::put('/citas/{cita}', [CitaController::class, 'update']);
        Route::post('/citas/{cita}/cancelar', [CitaController::class, 'cancelar']);
        Route::post('/citas/{cita}/no-agendar', [CitaController::class, 'noAgendar']);
        Route::post('/citas/{cita}/cobrar', [CitaController::class, 'cobrar']);
        Route::get('/lista-espera', [ListaEsperaController::class, 'index']);
        Route::post('/lista-espera', [ListaEsperaController::class, 'store']);
        Route::delete('/lista-espera/{listaEspera}', [ListaEsperaController::class, 'destroy']);
        Route::get('/notificaciones', [NotificacionController::class, 'index']);
        Route::post('/notificaciones/enviar-pendientes', [NotificacionController::class, 'enviarPendientes']);
    });

    // Psicología (Lane 3 del BPM)
    Route::middleware('rol:admin,psicologo')->group(function () {
        Route::post('/citas/{cita}/atender', [CitaController::class, 'atender']);
        Route::get('/historias', [HistoriaClinicaController::class, 'index']);
        Route::post('/historias', [HistoriaClinicaController::class, 'store']);
        Route::get('/historias/{historia}', [HistoriaClinicaController::class, 'show']);
        Route::middleware('plan:evaluaciones')->group(function () {
            Route::get('/evaluaciones', [EvaluacionController::class, 'index']);
            Route::post('/evaluaciones', [EvaluacionController::class, 'store']);
        });
        Route::post('/ia/emociones', [IaController::class, 'emociones'])->middleware('plan:analisis_emociones');
    });

    // Administración del centro
    Route::middleware('rol:admin')->group(function () {
        Route::post('/personal', [PersonalController::class, 'store']);
        Route::patch('/personal/{usuario}/activo', [PersonalController::class, 'alternarActivo']);
        Route::get('/suscripcion', [SuscripcionController::class, 'actual']);
        Route::post('/suscripcion/cambiar', [SuscripcionController::class, 'cambiar']);
        Route::post('/suscripcion/cancelar', [SuscripcionController::class, 'cancelar']);
        Route::get('/auditoria', [DashboardController::class, 'auditoria']);
    });
});
