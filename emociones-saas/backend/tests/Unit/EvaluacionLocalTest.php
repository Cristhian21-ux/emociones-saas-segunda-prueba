<?php

namespace Tests\Unit;

use App\Services\EvaluacionLocal;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EvaluacionLocalTest extends TestCase
{
    public function test_phq9_sin_sintomas_es_minima(): void
    {
        $r = EvaluacionLocal::puntuar('phq9', array_fill(0, 9, 0));

        $this->assertSame(0, $r['puntaje']);
        $this->assertSame('minima', $r['severidad']);
        $this->assertFalse($r['alerta']);
    }

    public function test_phq9_puntaje_moderado(): void
    {
        $r = EvaluacionLocal::puntuar('phq9', [2, 2, 2, 2, 2, 1, 1, 0, 0]);

        $this->assertSame(12, $r['puntaje']);
        $this->assertSame('moderada', $r['severidad']);
    }

    public function test_phq9_item_nueve_activa_alerta_aunque_el_puntaje_sea_bajo(): void
    {
        $r = EvaluacionLocal::puntuar('phq9', [0, 0, 0, 0, 0, 0, 0, 0, 1]);

        $this->assertTrue($r['alerta']);
        $this->assertSame('minima', $r['severidad']);
    }

    public function test_gad7_puntaje_maximo_es_severo(): void
    {
        $r = EvaluacionLocal::puntuar('gad7', array_fill(0, 7, 3));

        $this->assertSame(21, $r['puntaje']);
        $this->assertSame('severa', $r['severidad']);
        $this->assertTrue($r['alerta']);
    }

    public function test_cortes_de_severidad_gad7(): void
    {
        $this->assertSame('minima', EvaluacionLocal::severidad('gad7', 4));
        $this->assertSame('leve', EvaluacionLocal::severidad('gad7', 5));
        $this->assertSame('moderada', EvaluacionLocal::severidad('gad7', 10));
        $this->assertSame('severa', EvaluacionLocal::severidad('gad7', 15));
    }

    public function test_rechaza_cantidad_incorrecta_de_respuestas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EvaluacionLocal::puntuar('gad7', [1, 2, 3]);
    }

    public function test_rechaza_valores_fuera_de_rango(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EvaluacionLocal::puntuar('gad7', [0, 0, 0, 0, 0, 0, 4]);
    }

    public function test_rechaza_instrumento_desconocido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EvaluacionLocal::puntuar('beck', [1]);
    }
}
