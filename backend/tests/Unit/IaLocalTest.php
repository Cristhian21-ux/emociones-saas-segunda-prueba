<?php

namespace Tests\Unit;

use App\Services\IaLocal;
use PHPUnit\Framework\TestCase;

class IaLocalTest extends TestCase
{
    public function test_saluda(): void
    {
        $this->assertStringStartsWith('¡Hola!', IaLocal::responder('hola')['respuesta']);
    }

    public function test_responde_sobre_pagos_sin_tildes_ni_mayusculas(): void
    {
        $this->assertStringContainsString('Cobrar', IaLocal::responder('COMO REGISTRO UN PAGO CON YAPE')['respuesta']);
    }

    public function test_cambio_de_contrasena_con_enie(): void
    {
        $this->assertStringContainsString('Mi perfil', IaLocal::responder('Olvidé mi contraseña')['respuesta']);
    }

    public function test_cobrar_una_cita_no_se_confunde_con_agendar(): void
    {
        $this->assertStringContainsString('Cobrar', IaLocal::responder('como cobro una cita')['respuesta']);
        $this->assertStringContainsString('Cancelar', IaLocal::responder('quiero cancelar una cita')['respuesta']);
        $this->assertStringContainsString('Nueva cita', IaLocal::responder('¿Cómo agendo una cita?')['respuesta']);
    }

    public function test_crisis_deriva_a_linea_113(): void
    {
        $this->assertStringContainsString('113', IaLocal::responder('Tengo una crisis')['respuesta']);
    }

    public function test_pregunta_desconocida_no_inventa(): void
    {
        $r = IaLocal::responder('¿Quién ganó el mundial?');
        $this->assertSame(IaLocal::RESPUESTA_POR_DEFECTO, $r['respuesta']);
        $this->assertSame(0.0, (float) $r['confianza']);
    }

    public function test_detecta_tristeza(): void
    {
        $r = IaLocal::analizar('Me siento muy triste y sola, lloro todas las noches');
        $this->assertSame('tristeza', $r['emocion_dominante']);
        $this->assertSame('medio', $r['nivel_riesgo']);
    }

    public function test_frase_de_riesgo_con_enie_es_riesgo_alto(): void
    {
        $this->assertSame('alto', IaLocal::analizar('A veces pienso en hacerme daño')['nivel_riesgo']);
        $this->assertSame('tristeza', IaLocal::analizar('Me quiero morir')['emocion_dominante']);
    }

    public function test_texto_neutral(): void
    {
        $r = IaLocal::analizar('Hoy hablamos sobre el trabajo y la familia');
        $this->assertSame('neutral', $r['emocion_dominante']);
        $this->assertSame('bajo', $r['nivel_riesgo']);
    }

    public function test_sufrir_por_amor_con_emoticon_es_tristeza(): void
    {
        $this->assertSame('tristeza', IaLocal::analizar('Hola sufro por amor :(')['emocion_dominante']);
        $this->assertSame('tristeza', IaLocal::analizar('Mi pareja me dejó y me duele mucho')['emocion_dominante']);
    }

    public function test_negacion_y_verbo_ir_no_cuentan(): void
    {
        $this->assertSame('neutral', IaLocal::analizar('Ya no estoy triste')['emocion_dominante']);
        $this->assertSame('neutral', IaLocal::analizar('Irá a la cita el lunes')['emocion_dominante']);
    }

    public function test_conjugaciones_y_emoji(): void
    {
        $this->assertSame('ansiedad', IaLocal::analizar('Me preocupa todo y me siento agobiada')['emocion_dominante']);
        $this->assertSame('alegria', IaLocal::analizar("Hoy estoy contenta \u{1F60A}")['emocion_dominante']);
    }
}
