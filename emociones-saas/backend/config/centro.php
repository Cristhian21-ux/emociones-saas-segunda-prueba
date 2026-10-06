<?php

return [
    // Horario de atención. "dias" usa ISO (1 = lunes ... 7 = domingo).
    'horario' => [
        'inicio' => '08:00',
        'fin' => '18:00',
        'duracion' => 60,
        'dias' => [1, 2, 3, 4, 5, 6],
        'descanso' => ['13:00'],
    ],

    'canal_predeterminado' => env('CENTRO_CANAL', 'whatsapp'),

    'recordatorio_horas_antes' => 24,

    'especialidades' => [
        'Psicología clínica',
        'Psicología infantil y del adolescente',
        'Psicología de pareja y familia',
        'Neuropsicología',
        'Psicología educativa',
        'Terapia cognitivo-conductual',
    ],
];
