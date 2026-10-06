from app import emociones


def test_detecta_tristeza():
    r = emociones.analizar("Me siento muy triste, con llanto y soledad")
    assert r["emocion_dominante"] == "tristeza"
    assert r["nivel_riesgo"] == "medio"


def test_detecta_ansiedad_sin_tildes_ni_mayusculas():
    r = emociones.analizar("ESTRÉS, angustia y PÁNICO antes del examen")
    assert r["emocion_dominante"] == "ansiedad"


def test_texto_neutral():
    r = emociones.analizar("Vino a la consulta el martes")
    assert r["emocion_dominante"] == "neutral"
    assert r["nivel_riesgo"] == "bajo"


def test_frase_de_riesgo_es_riesgo_alto():
    r = emociones.analizar("Dice que ya no quiere vivir y piensa en quitarme la vida")
    assert r["nivel_riesgo"] == "alto"
    assert "RIESGO ALTO" in r["recomendacion"]


def test_alegria_es_riesgo_bajo():
    r = emociones.analizar("Hoy se siente feliz, motivado y con esperanza")
    assert r["emocion_dominante"] == "alegria"
    assert r["nivel_riesgo"] == "bajo"


def test_puntajes_suman_uno():
    p = emociones.puntajes("triste y ansioso y con miedo")
    assert abs(sum(p.values()) - 1.0) < 0.01


def test_normalizar_quita_tildes():
    assert emociones.normalizar("Angustia Pánico") == "angustia panico"


def test_sufrir_por_amor_es_tristeza():
    r = emociones.analizar("Hola sufro por amor :(")
    assert r["emocion_dominante"] == "tristeza"


def test_emoticon_triste_cuenta():
    assert emociones.analizar("hoy :'(")["emocion_dominante"] == "tristeza"


def test_negacion_no_cuenta():
    assert emociones.analizar("Ya no estoy triste")["emocion_dominante"] == "neutral"


def test_conjugaciones_por_raiz():
    assert emociones.analizar("Me preocupa todo y me siento agobiada")["emocion_dominante"] == "ansiedad"


def test_ira_el_verbo_no_es_ira_la_emocion():
    assert emociones.analizar("Irá a la cita el lunes")["emocion_dominante"] == "neutral"


def test_frase_de_riesgo_sola_no_es_neutral():
    r = emociones.analizar("Me quiero morir")
    assert r["nivel_riesgo"] == "alto" and r["emocion_dominante"] == "tristeza"
