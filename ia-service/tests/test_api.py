import pytest
from fastapi.testclient import TestClient

from app.main import app

LLAVE = {"X-API-Key": "llave-test"}


@pytest.fixture(autouse=True)
def llave(monkeypatch):
    monkeypatch.setenv("IA_SERVICE_KEY", "llave-test")


cliente = TestClient(app)


def test_health_es_publico():
    assert cliente.get("/health").json()["status"] == "ok"


def test_rechaza_peticion_sin_llave():
    assert cliente.post("/emociones/analizar", json={"texto": "hola mundo"}).status_code == 401


def test_analizar_emociones_por_api():
    r = cliente.post("/emociones/analizar", json={"texto": "siento mucha angustia"}, headers=LLAVE)
    assert r.status_code == 200
    assert r.json()["emocion_dominante"] == "ansiedad"


def test_texto_muy_corto_es_422():
    assert cliente.post("/emociones/analizar", json={"texto": "a"}, headers=LLAVE).status_code == 422


def test_puntuar_por_api():
    r = cliente.post("/evaluaciones/puntuar", json={"instrumento": "gad7", "respuestas": [3] * 7}, headers=LLAVE)
    assert r.status_code == 200
    assert r.json()["severidad"] == "severa"


def test_puntuar_invalido_es_422():
    r = cliente.post("/evaluaciones/puntuar", json={"instrumento": "gad7", "respuestas": [1]}, headers=LLAVE)
    assert r.status_code == 422


def test_chatbot_por_api():
    r = cliente.post("/chatbot/responder", json={"pregunta": "como cobrar con yape"}, headers=LLAVE)
    assert "Cobrar" in r.json()["respuesta"]
