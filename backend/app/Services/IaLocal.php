<?php

namespace App\Services;

/**
 * Versión local en PHP del chatbot y del análisis de emociones del
 * microservicio Python. Se usa cuando el servicio no está encendido, para
 * que el asistente siga respondiendo (RNF-12). Usa la misma base de
 * conocimiento y el mismo léxico que ia-service/app.
 */
class IaLocal
{
    private const STOPWORDS = ['el', 'la', 'los', 'las', 'de', 'del', 'un', 'una', 'y', 'o', 'a', 'en', 'que', 'como',
        'para', 'por', 'mi', 'me', 'se', 'es', 'con', 'al', 'lo', 'puedo', 'hago', 'quiero', 'hay'];

    private const BASE_CONOCIMIENTO = [
        [['hola', 'buenas', 'buenos', 'dias', 'tardes', 'noches', 'ayuda', 'saludos'],
            '¡Hola! Soy el asistente del sistema. Puedo ayudarte con citas, pagos, lista de espera, evaluaciones, historias clínicas, personal y planes.'],
        [['cancelar', 'cancelo', 'anular', 'reprogramar'],
            'Abre la cita y pulsa Cancelar o Reprogramar. Si cancelas, el cupo se ofrece automáticamente al primero de la lista de espera.'],
        [['espera', 'lista', 'cupo'],
            'En Lista de espera registras pacientes sin horario; cuando se libera un cupo compatible se les notifica.'],
        [['pago', 'pagos', 'cobrar', 'cobro', 'comprobante', 'yape', 'plin', 'caja'],
            'En la cita pulsa Cobrar, indica monto, método y número de comprobante. Sin pago no se puede atender.'],
        [['agendar', 'agendo', 'cita', 'citas', 'reservar', 'reserva', 'nueva'],
            'Para agendar ve a Citas > Nueva cita, elige paciente, psicólogo y un horario libre de la agenda.'],
        [['plan', 'planes', 'precio', 'suscripcion', 'premium', 'vip', 'gratuito', 'mejorar'],
            'Planes: Gratuito (2 psicólogos), VIP (USD 15/mes, evaluaciones PHQ-9/GAD-7) y Premium (USD 70/mes, análisis de emociones con IA). Cámbialo en Mi suscripción.'],
        [['evaluacion', 'evaluaciones', 'phq', 'gad', 'test', 'prueba', 'depresion', 'ansiedad'],
            'En Evaluaciones aplica PHQ-9 (depresión) o GAD-7 (ansiedad); el sistema califica al instante y alerta casos de riesgo.'],
        [['historia', 'historias', 'clinica', 'diagnostico'],
            'Las historias clínicas solo las ven el psicólogo tratante y el administrador. Se registran desde Historias clínicas.'],
        [['paciente', 'pacientes', 'registrar', 'dni'],
            'En Pacientes > Nuevo paciente registras nombre, DNI de 8 dígitos y celular. Puedes buscarlo por nombre o DNI.'],
        [['psicologo', 'personal', 'agregar', 'usuario', 'recepcionista'],
            'El administrador agrega personal en Personal > Agregar. El número de psicólogos depende de tu plan.'],
        [['contrasena', 'clave', 'password', 'perfil'],
            'Cambia tu nombre o contraseña desde Mi perfil.'],
        [['emergencia', 'crisis', 'suicidio', 'urgencia'],
            'Ante una crisis llama a la Línea 113 opción 5 (salud mental, Perú) o acude a emergencias. Este asistente no brinda atención clínica.'],
    ];

    public const RESPUESTA_POR_DEFECTO = 'No tengo información sobre eso. Puedo ayudarte con citas, pagos, lista de espera, '
        .'evaluaciones, historias clínicas, personal y planes.';

    /** Términos sin tildes; los que terminan en "*" son raíces (sufr* -> sufro, sufrimiento). */
    private const LEXICO = [
        'tristeza' => ['trist*', 'llant*', 'llor*', 'deprim*', 'depresi*', 'vacio', 'vacia', 'sola', 'soledad',
            'desanim*', 'desmotiv*', 'melancol*', 'duelo', 'perdida', 'sufr*', 'dolor*', 'duele', 'doli*',
            'extran*', 'desamor', 'ruptura', 'abandon*', 'decepci*', 'desilusi*', 'infeliz', 'amargur*',
            'nostalg*', 'culpa', 'culpable', 'rechaz*', 'herid*', 'destroz*', 'desesper*', 'miserable',
            'lament*'],
        'ansiedad' => ['ansi*', 'nervi*', 'preocup*', 'angusti*', 'panico', 'estres*', 'insomnio', 'inquiet*',
            'tension', 'agobi*', 'abrum*', 'intranquil*', 'taquicardia', 'sobrepens*'],
        'ira' => ['enoj*', 'rabia', 'iracund*', 'furi*', 'irrit*', 'molest*', 'frustra*', 'agresiv*', 'odio',
            'odia*', 'resentid*', 'resentimiento', 'colera', 'indign*', 'celos', 'celoso', 'celosa'],
        'miedo' => ['miedo*', 'temor*', 'asust*', 'terror*', 'fobi*', 'insegur*', 'amenaz*', 'aterr*',
            'espant*'],
        'alegria' => ['feliz', 'felices', 'felicidad', 'alegr*', 'content*', 'motivad*', 'entusias*',
            'satisfech*', 'orgullos*', 'esperanz*', 'mejor', 'tranquil*', 'calma', 'agradecid*', 'ilusionad*'],
    ];

    /** Frases y emoticones que también expresan una emoción. */
    private const FRASES = [
        'tristeza' => ['corazon roto', 'me dejo', 'me dejaron', 'me engano', 'terminamos', 'sin ganas',
            'mucha pena', 'me da pena', ':(', ':-(', ':\'(', "\u{1F622}", "\u{1F62D}", "\u{1F494}", "\u{2639}",
            "\u{1F61E}"],
        'ansiedad' => ['no puedo dormir', 'no duermo', "\u{1F630}", "\u{1F625}"],
        'ira' => ["\u{1F620}", "\u{1F621}", "\u{1F92C}"],
        'miedo' => ["\u{1F628}", "\u{1F631}"],
        'alegria' => [':)', ':-)', "\u{1F60A}", "\u{1F600}", "\u{1F604}", "\u{1F642}"],
    ];

    private const NEGACIONES = [
        'jamas', 'ni', 'no', 'nunca', 'tampoco',
    ];

    private const FRASES_RIESGO_ALTO = [
        'quitarme la vida', 'suicid', 'matarme', 'no quiero vivir', 'hacerme dano', 'autolesion', 'cortarme',
        'acabar con todo', 'desaparecer para siempre', 'quiero morir', 'me quiero morir',
        'no vale la pena vivir', 'mejor muerto', 'mejor muerta',
    ];

    private const EMOCIONES_DE_RIESGO = ['tristeza', 'ansiedad', 'miedo', 'ira'];

    private const RECOMENDACIONES = [
        'tristeza' => 'Explorar síntomas depresivos; considerar aplicar PHQ-9.',
        'ansiedad' => 'Explorar síntomas ansiosos; considerar aplicar GAD-7 y técnicas de respiración.',
        'ira' => 'Trabajar regulación emocional y manejo de la frustración.',
        'miedo' => 'Evaluar posibles fobias o experiencias traumáticas.',
        'alegria' => 'Reforzar los recursos y avances del paciente.',
        'neutral' => 'Sin carga emocional predominante en el texto.',
    ];

    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return preg_replace('/\s+/u', ' ', $texto);
    }

    public static function tokens(string $texto): array
    {
        preg_match_all('/[a-zñ]+/u', self::normalizar($texto), $m);

        return $m[0];
    }

    public static function responder(string $pregunta): array
    {
        $palabras = array_diff(array_unique(self::tokens($pregunta)), self::STOPWORDS);
        $mejor = null;
        $mejorPuntaje = 0;
        foreach (self::BASE_CONOCIMIENTO as [$claves, $respuesta]) {
            $coincidencias = count(array_intersect($palabras, $claves));
            if ($coincidencias > $mejorPuntaje) {
                [$mejor, $mejorPuntaje] = [$respuesta, $coincidencias];
            }
        }

        return [
            'respuesta' => $mejor ?? self::RESPUESTA_POR_DEFECTO,
            'confianza' => round(min(1, $mejorPuntaje / 2), 2),
        ];
    }

    private static function coincide(string $palabra, array $terminos): bool
    {
        foreach ($terminos as $t) {
            if (str_ends_with($t, '*') ? str_starts_with($palabra, substr($t, 0, -1)) : $palabra === $t) {
                return true;
            }
        }

        return false;
    }

    public static function analizar(string $texto): array
    {
        $plano = self::normalizar($texto);
        $conteo = self::contarEmociones(self::tokens($texto), $plano);
        $dominante = self::emocionDominante($conteo);
        $riesgo = self::nivelRiesgo($plano, $dominante);
        if ($riesgo === 'alto' && $dominante === 'neutral') {
            $dominante = 'tristeza'; // una frase de riesgo suicida nunca es "neutral"
        }

        return [
            'emocion_dominante' => $dominante,
            'puntajes' => self::puntajes($conteo),
            'nivel_riesgo' => $riesgo,
            'recomendacion' => $riesgo === 'alto'
                ? 'RIESGO ALTO: activar protocolo de crisis y contacto de emergencia de inmediato.'
                : self::RECOMENDACIONES[$dominante],
        ];
    }

    /** Suma las palabras del léxico y las frases/emoticones de cada emoción. */
    private static function contarEmociones(array $palabras, string $plano): array
    {
        $conteo = array_fill_keys(array_keys(self::LEXICO), 0);

        foreach ($palabras as $i => $palabra) {
            if (self::estaNegada($palabras, $i)) {
                continue; // "no estoy triste" no cuenta como tristeza
            }
            $emocion = self::emocionDeLaPalabra($palabra);
            if ($emocion !== null) {
                $conteo[$emocion]++;
            }
        }

        foreach (self::FRASES as $emocion => $frases) {
            foreach ($frases as $frase) {
                $conteo[$emocion] += substr_count($plano, $frase);
            }
        }

        return $conteo;
    }

    /** True si una negación aparece en las 3 palabras anteriores a la posición $i. */
    private static function estaNegada(array $palabras, int $i): bool
    {
        $anteriores = array_slice($palabras, max(0, $i - 3), min($i, 3));

        return array_intersect($anteriores, self::NEGACIONES) !== [];
    }

    private static function emocionDeLaPalabra(string $palabra): ?string
    {
        foreach (self::LEXICO as $emocion => $terminos) {
            if (self::coincide($palabra, $terminos)) {
                return $emocion;
            }
        }

        return null;
    }

    private static function emocionDominante(array $conteo): string
    {
        return array_sum($conteo) > 0 ? array_search(max($conteo), $conteo, true) : 'neutral';
    }

    /** Proporción de cada emoción sobre el total detectado. */
    private static function puntajes(array $conteo): array
    {
        $total = array_sum($conteo);

        return array_map(fn ($n) => $total ? round($n / $total, 3) : 0.0, $conteo);
    }

    private static function nivelRiesgo(string $plano, string $dominante): string
    {
        foreach (self::FRASES_RIESGO_ALTO as $frase) {
            if (str_contains($plano, $frase)) {
                return 'alto';
            }
        }

        return in_array($dominante, self::EMOCIONES_DE_RIESGO, true) ? 'medio' : 'bajo';
    }
}
