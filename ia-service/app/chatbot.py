"""Chatbot de ayuda de la plataforma.

Responde solo desde una base de conocimiento controlada (no inventa ni usa
datos clínicos), como recomienda Panduwiyasa (2025) para reducir alucinaciones.
La recuperación usa similitud por palabras clave (bolsa de palabras).
"""

from .emociones import tokens

STOPWORDS = {"el", "la", "los", "las", "de", "del", "un", "una", "y", "o", "a", "en", "que", "como",
             "para", "por", "mi", "me", "se", "es", "con", "al", "lo", "puedo", "hago", "quiero", "hay"}

BASE_CONOCIMIENTO = [
    {"claves": {"hola", "buenas", "buenos", "dias", "tardes", "noches", "ayuda", "saludos"},
     "respuesta": "¡Hola! Soy el asistente del sistema. Puedo ayudarte con citas, pagos, lista de espera, evaluaciones, historias clínicas, personal y planes."},
    {"claves": {"cancelar", "cancelo", "anular", "reprogramar"},
     "respuesta": "Abre la cita y pulsa Cancelar o Reprogramar. Si cancelas, el cupo se ofrece automáticamente al primero de la lista de espera."},
    {"claves": {"espera", "lista", "cupo"},
     "respuesta": "En Lista de espera registras pacientes sin horario; cuando se libera un cupo compatible se les notifica."},
    {"claves": {"pago", "pagos", "cobrar", "cobro", "comprobante", "yape", "plin", "caja"},
     "respuesta": "En la cita pulsa Cobrar, indica monto, método y número de comprobante. Sin pago no se puede atender."},
    {"claves": {"agendar", "agendo", "cita", "citas", "reservar", "reserva", "nueva"},
     "respuesta": "Para agendar ve a Citas > Nueva cita, elige paciente, psicólogo y un horario libre de la agenda."},
    {"claves": {"plan", "planes", "precio", "suscripcion", "premium", "vip", "gratuito", "mejorar"},
     "respuesta": "Planes: Gratuito (2 psicólogos), VIP (USD 15/mes, evaluaciones PHQ-9/GAD-7) y Premium (USD 70/mes, análisis de emociones con IA). Cámbialo en Mi suscripción."},
    {"claves": {"evaluacion", "evaluaciones", "phq", "gad", "test", "prueba", "depresion", "ansiedad"},
     "respuesta": "En Evaluaciones aplica PHQ-9 (depresión) o GAD-7 (ansiedad); el sistema califica al instante y alerta casos de riesgo."},
    {"claves": {"historia", "historias", "clinica", "diagnostico"},
     "respuesta": "Las historias clínicas solo las ven el psicólogo tratante y el administrador. Se registran desde Historias clínicas."},
    {"claves": {"paciente", "pacientes", "registrar", "dni"},
     "respuesta": "En Pacientes > Nuevo paciente registras nombre, DNI de 8 dígitos y celular. Puedes buscarlo por nombre o DNI."},
    {"claves": {"psicologo", "personal", "agregar", "usuario", "recepcionista"},
     "respuesta": "El administrador agrega personal en Personal > Agregar. El número de psicólogos depende de tu plan."},
    {"claves": {"contrasena", "clave", "password", "perfil"},
     "respuesta": "Cambia tu nombre o contraseña desde Mi perfil."},
    {"claves": {"emergencia", "crisis", "suicidio", "urgencia"},
     "respuesta": "Ante una crisis llama a la Línea 113 opción 5 (salud mental, Perú) o acude a emergencias. Este asistente no brinda atención clínica."},
]

RESPUESTA_POR_DEFECTO = (
    "No tengo información sobre eso. Puedo ayudarte con citas, pagos, lista de espera, "
    "evaluaciones, historias clínicas, personal y planes."
)


def responder(pregunta: str) -> dict:
    palabras = {t for t in tokens(pregunta) if t not in STOPWORDS}
    mejor, mejor_puntaje = None, 0
    for entrada in BASE_CONOCIMIENTO:
        coincidencias = len(palabras & entrada["claves"])
        if coincidencias > mejor_puntaje:
            mejor, mejor_puntaje = entrada, coincidencias
    if mejor is None:
        return {"respuesta": RESPUESTA_POR_DEFECTO, "confianza": 0.0}
    return {"respuesta": mejor["respuesta"], "confianza": round(min(1.0, mejor_puntaje / 2), 2)}
