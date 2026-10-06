from app import chatbot


def test_responde_como_agendar():
    assert "Nueva cita" in chatbot.responder("¿Cómo agendo una cita?")["respuesta"]


def test_responde_sobre_planes():
    assert "Premium" in chatbot.responder("¿Cuánto cuesta el plan premium?")["respuesta"]


def test_crisis_deriva_a_linea_de_ayuda():
    assert "113" in chatbot.responder("Tengo una emergencia, es una crisis")["respuesta"]


def test_pregunta_desconocida_no_inventa():
    r = chatbot.responder("¿Quién ganó el mundial?")
    assert r["respuesta"] == chatbot.RESPUESTA_POR_DEFECTO
    assert r["confianza"] == 0.0
