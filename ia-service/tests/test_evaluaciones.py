import pytest

from app import evaluaciones


@pytest.mark.parametrize(
    "puntaje,esperado",
    [(0, "minima"), (5, "leve"), (10, "moderada"), (15, "moderadamente_severa"), (20, "severa")],
)
def test_cortes_phq9(puntaje, esperado):
    assert evaluaciones.severidad("phq9", puntaje) == esperado


def test_gad7_leve():
    r = evaluaciones.puntuar("gad7", [1, 1, 1, 1, 1, 1, 1])
    assert r["puntaje"] == 7
    assert r["severidad"] == "leve"
    assert r["alerta"] is False


def test_phq9_item9_genera_alerta():
    r = evaluaciones.puntuar("phq9", [0, 0, 0, 0, 0, 0, 0, 0, 2])
    assert r["alerta"] is True
    assert "autolesión" in r["interpretacion"]


def test_rechaza_numero_de_respuestas():
    with pytest.raises(evaluaciones.EvaluacionInvalida):
        evaluaciones.puntuar("phq9", [1, 1])


def test_rechaza_valor_fuera_de_rango():
    with pytest.raises(evaluaciones.EvaluacionInvalida):
        evaluaciones.puntuar("gad7", [0, 0, 0, 0, 0, 0, 5])


def test_rechaza_instrumento_desconocido():
    with pytest.raises(evaluaciones.EvaluacionInvalida):
        evaluaciones.puntuar("bdi", [1])
