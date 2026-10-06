"""Análisis de emociones en texto clínico en español (léxico + reglas).

Es un modelo explicable y liviano: cuenta términos de un léxico por emoción,
normaliza los puntajes y detecta frases de riesgo. No reemplaza el criterio
del psicólogo; sirve como apoyo (Panduwiyasa, 2025).
"""

import re
import unicodedata

# Términos por emoción, ya sin tildes. Los que terminan en "*" son raíces que
# cubren conjugaciones y derivados (sufr* -> sufro, sufriendo, sufrimiento).
LEXICO = {
    "tristeza": ["trist*", "llant*", "llor*", "deprim*", "depresi*", "vacio", "vacia", "sola", "soledad",
                 "desanim*", "desmotiv*", "melancol*", "duelo", "perdida", "sufr*", "dolor*", "duele", "doli*",
                 "extran*", "desamor", "ruptura", "abandon*", "decepci*", "desilusi*",
                 "infeliz", "amargur*", "nostalg*", "culpa", "culpable", "rechaz*", "herid*", "destroz*",
                 "desesper*", "miserable", "lament*"],
    "ansiedad": ["ansi*", "nervi*", "preocup*", "angusti*", "panico", "estres*", "insomnio", "inquiet*",
                 "tension", "agobi*", "abrum*", "intranquil*", "taquicardia", "sobrepens*"],
    "ira": ["enoj*", "rabia", "iracund*", "furi*", "irrit*", "molest*", "frustra*", "agresiv*", "odio", "odia*",
            "resentid*", "resentimiento", "colera", "indign*", "celos", "celoso", "celosa"],
    "miedo": ["miedo*", "temor*", "asust*", "terror*", "fobi*", "insegur*", "amenaz*", "aterr*", "espant*"],
    "alegria": ["feliz", "felices", "felicidad", "alegr*", "content*", "motivad*", "entusias*", "satisfech*",
                "orgullos*", "esperanz*", "mejor", "tranquil*", "calma", "agradecid*", "ilusionad*"],
}

# Frases y emoticones que también expresan una emoción.
FRASES = {
    "tristeza": ["corazon roto", "me dejo", "me dejaron", "me engano", "terminamos", "sin ganas", "mucha pena", "me da pena",
                 ":(", ":-(", ":'(", "\U0001F622", "\U0001F62D", "\U0001F494", "\u2639", "\U0001F61E"],
    "ansiedad": ["no puedo dormir", "no duermo", "\U0001F630", "\U0001F625"],
    "ira": ["\U0001F620", "\U0001F621", "\U0001F92C"],
    "miedo": ["\U0001F628", "\U0001F631"],
    "alegria": [":)", ":-)", "\U0001F60A", "\U0001F600", "\U0001F604", "\U0001F642"],
}

NEGACIONES = {"no", "nunca", "jamas", "ni", "tampoco"}
EMOCIONES_DE_RIESGO = ("tristeza", "ansiedad", "miedo", "ira")

FRASES_RIESGO_ALTO = [
    "quitarme la vida", "suicid", "matarme", "no quiero vivir", "hacerme dano", "autolesion",
    "cortarme", "acabar con todo", "desaparecer para siempre", "quiero morir", "me quiero morir",
    "no vale la pena vivir", "mejor muerto", "mejor muerta",
]

RECOMENDACIONES = {
    "tristeza": "Explorar síntomas depresivos; considerar aplicar PHQ-9.",
    "ansiedad": "Explorar síntomas ansiosos; considerar aplicar GAD-7 y técnicas de respiración.",
    "ira": "Trabajar regulación emocional y manejo de la frustración.",
    "miedo": "Evaluar posibles fobias o experiencias traumáticas.",
    "alegria": "Reforzar los recursos y avances del paciente.",
    "neutral": "Sin carga emocional predominante en el texto.",
}


def normalizar(texto: str) -> str:
    sin_tildes = unicodedata.normalize("NFKD", texto.lower())
    sin_tildes = "".join(c for c in sin_tildes if not unicodedata.combining(c))
    return re.sub(r"\s+", " ", sin_tildes)


def tokens(texto: str) -> list[str]:
    return re.findall(r"[a-zñ]+", normalizar(texto))


def coincide(palabra: str, termino: str) -> bool:
    return palabra.startswith(termino[:-1]) if termino.endswith("*") else palabra == termino


def esta_negada(palabras: list[str], i: int) -> bool:
    """True si hay una negación en las 3 palabras anteriores a la posición i."""
    return bool(NEGACIONES & set(palabras[max(0, i - 3):i]))


def emocion_de_la_palabra(palabra: str) -> str | None:
    for emocion, terminos in LEXICO.items():
        if any(coincide(palabra, t) for t in terminos):
            return emocion
    return None


def contar_emociones(texto: str) -> dict[str, int]:
    """Suma las palabras del léxico y las frases/emoticones de cada emoción."""
    palabras = tokens(texto)
    plano = normalizar(texto)
    conteo = {emocion: 0 for emocion in LEXICO}
    for i, palabra in enumerate(palabras):
        if esta_negada(palabras, i):
            continue  # "no estoy triste" no cuenta como tristeza
        emocion = emocion_de_la_palabra(palabra)
        if emocion is not None:
            conteo[emocion] += 1
    for emocion, frases in FRASES.items():
        conteo[emocion] += sum(plano.count(f) for f in frases)
    return conteo


def puntajes(texto: str) -> dict[str, float]:
    """Proporción de cada emoción sobre el total detectado."""
    conteo = contar_emociones(texto)
    total = sum(conteo.values())
    if total == 0:
        return {emocion: 0.0 for emocion in LEXICO}
    return {emocion: round(n / total, 3) for emocion, n in conteo.items()}


def nivel_riesgo(texto: str, dominante: str) -> str:
    plano = normalizar(texto)
    if any(frase in plano for frase in FRASES_RIESGO_ALTO):
        return "alto"
    if dominante in EMOCIONES_DE_RIESGO:
        return "medio"
    return "bajo"


def analizar(texto: str) -> dict:
    p = puntajes(texto)
    dominante = max(p, key=p.get) if any(p.values()) else "neutral"
    riesgo = nivel_riesgo(texto, dominante)
    if riesgo == "alto" and dominante == "neutral":
        dominante = "tristeza"  # una frase de riesgo suicida nunca es "neutral"
    recomendacion = RECOMENDACIONES[dominante]
    if riesgo == "alto":
        recomendacion = "RIESGO ALTO: activar protocolo de crisis y contacto de emergencia de inmediato."
    return {
        "emocion_dominante": dominante,
        "puntajes": p,
        "nivel_riesgo": riesgo,
        "recomendacion": recomendacion,
    }
