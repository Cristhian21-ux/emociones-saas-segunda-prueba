"""Calificación automática de pruebas psicológicas estandarizadas.

PHQ-9 (depresión, Kroenke et al., 2001) y GAD-7 (ansiedad, Spitzer et al., 2006).
La calificación web inmediata evita los errores de transcripción y cálculo
manual descritos por Morales-Ramírez et al. (2012).
"""

INSTRUMENTOS = {
    "phq9": {
        "nombre": "PHQ-9",
        "items": 9,
        "cortes": [(4, "minima"), (9, "leve"), (14, "moderada"), (19, "moderadamente_severa"), (27, "severa")],
    },
    "gad7": {
        "nombre": "GAD-7",
        "items": 7,
        "cortes": [(4, "minima"), (9, "leve"), (14, "moderada"), (21, "severa")],
    },
}


class EvaluacionInvalida(ValueError):
    pass


def severidad(instrumento: str, puntaje: int) -> str:
    for maximo, nivel in INSTRUMENTOS[instrumento]["cortes"]:
        if puntaje <= maximo:
            return nivel
    return "severa"


def validar(instrumento: str, respuestas: list[int]) -> dict:
    """Devuelve la ficha del instrumento o lanza EvaluacionInvalida."""
    if instrumento not in INSTRUMENTOS:
        raise EvaluacionInvalida(f"Instrumento no soportado: {instrumento}")
    info = INSTRUMENTOS[instrumento]
    if len(respuestas) != info["items"]:
        raise EvaluacionInvalida(f"{info['nombre']} requiere {info['items']} respuestas.")
    if any((not isinstance(r, int)) or r < 0 or r > 3 for r in respuestas):
        raise EvaluacionInvalida("Cada respuesta debe ser un entero entre 0 y 3.")
    return info


def interpretar(nombre: str, puntaje: int, nivel: str, ideacion: bool, alerta: bool) -> str:
    texto = f"{nombre}: puntaje {puntaje} ({nivel.replace('_', ' ')})."
    if ideacion:
        texto += " El ítem 9 indica pensamientos de autolesión: evaluar riesgo de inmediato."
    elif alerta:
        texto += " Requiere seguimiento prioritario por el psicólogo."
    return texto


def puntuar(instrumento: str, respuestas: list[int]) -> dict:
    info = validar(instrumento, respuestas)
    puntaje = sum(respuestas)
    nivel = severidad(instrumento, puntaje)
    ideacion = instrumento == "phq9" and respuestas[8] > 0
    alerta = ideacion or nivel in ("moderadamente_severa", "severa")
    return {
        "puntaje": puntaje,
        "severidad": nivel,
        "alerta": alerta,
        "interpretacion": interpretar(info["nombre"], puntaje, nivel, ideacion, alerta),
    }
