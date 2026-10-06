<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Calificación local de PHQ-9 y GAD-7. Se usa como respaldo cuando el
 * microservicio Python no responde (RNF-12: degradar sin perder datos).
 * Los cortes siguen a Kroenke et al. (2001) y Spitzer et al. (2006).
 */
class EvaluacionLocal
{
    public const ITEMS = ['phq9' => 9, 'gad7' => 7];

    private const CORTES = [
        'phq9' => [[4, 'minima'], [9, 'leve'], [14, 'moderada'], [19, 'moderadamente_severa'], [27, 'severa']],
        'gad7' => [[4, 'minima'], [9, 'leve'], [14, 'moderada'], [21, 'severa']],
    ];

    public static function puntuar(string $instrumento, array $respuestas): array
    {
        if (! isset(self::ITEMS[$instrumento])) {
            throw new InvalidArgumentException("Instrumento no soportado: $instrumento");
        }
        if (count($respuestas) !== self::ITEMS[$instrumento]) {
            throw new InvalidArgumentException('Cantidad de respuestas incorrecta.');
        }
        foreach ($respuestas as $valor) {
            if (! is_int($valor) || $valor < 0 || $valor > 3) {
                throw new InvalidArgumentException('Cada respuesta debe ser un entero entre 0 y 3.');
            }
        }

        $puntaje = array_sum($respuestas);
        $severidad = self::severidad($instrumento, $puntaje);
        // PHQ-9 ítem 9 (ideación de autolesión) > 0 exige atención inmediata.
        $alerta = ($instrumento === 'phq9' && $respuestas[8] > 0) || in_array($severidad, ['moderadamente_severa', 'severa'], true);

        return [
            'puntaje' => $puntaje,
            'severidad' => $severidad,
            'alerta' => $alerta,
            'interpretacion' => strtoupper($instrumento).": puntaje $puntaje (".str_replace('_', ' ', $severidad).').'
                .($alerta ? ' Requiere seguimiento prioritario por el psicólogo.' : ''),
            'fuente' => 'local',
        ];
    }

    public static function severidad(string $instrumento, int $puntaje): string
    {
        foreach (self::CORTES[$instrumento] as [$max, $nivel]) {
            if ($puntaje <= $max) {
                return $nivel;
            }
        }

        return 'severa';
    }
}
