<?php

use Illuminate\Support\Facades\Route;

/*
| La interfaz React se compila dentro de public/spa (npm run build:laravel en frontend/).
| Así todo el sistema se abre con un solo comando: php artisan serve → http://localhost:8000
| Cualquier ruta que no sea /api devuelve la SPA y React Router decide qué pantalla mostrar.
*/
Route::get('/{ruta?}', function () {
    $spa = public_path('spa/index.html');

    if (! file_exists($spa)) {
        return response()->json([
            'servicio' => 'API Emociones SaaS',
            'mensaje' => 'Falta compilar la interfaz: en la carpeta frontend ejecuta "npm run build:laravel".',
        ]);
    }

    return response()->file($spa, ['Cache-Control' => 'no-cache']);
})->where('ruta', '^(?!api(/|$)).*$');
