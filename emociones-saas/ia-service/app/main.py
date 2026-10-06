"""Microservicio de IA emocional del SaaS Centro Psicológico Emociones (FastAPI)."""

import os

from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

from . import chatbot, emociones, evaluaciones

app = FastAPI(title="Emociones IA", version="1.0.0")


def verificar_llave(x_api_key: str | None = Header(default=None)) -> None:
    esperada = os.getenv("IA_SERVICE_KEY", "cambia-esta-llave")
    if x_api_key != esperada:
        raise HTTPException(status_code=401, detail="Llave de servicio inválida")


class TextoIn(BaseModel):
    texto: str = Field(min_length=3, max_length=5000)


class EvaluacionIn(BaseModel):
    instrumento: str
    respuestas: list[int]


class PreguntaIn(BaseModel):
    pregunta: str = Field(min_length=2, max_length=500)
    rol: str | None = None


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "servicio": "ia-emociones"}


@app.post("/emociones/analizar", dependencies=[Depends(verificar_llave)])
def analizar_emociones(datos: TextoIn) -> dict:
    return emociones.analizar(datos.texto)


@app.post("/evaluaciones/puntuar", dependencies=[Depends(verificar_llave)])
def puntuar_evaluacion(datos: EvaluacionIn) -> dict:
    try:
        return evaluaciones.puntuar(datos.instrumento, datos.respuestas)
    except evaluaciones.EvaluacionInvalida as error:
        raise HTTPException(status_code=422, detail=str(error)) from error


@app.post("/chatbot/responder", dependencies=[Depends(verificar_llave)])
def responder_chatbot(datos: PreguntaIn) -> dict:
    return chatbot.responder(datos.pregunta)
