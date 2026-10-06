import { useState } from 'react';
import api, { mensajeError } from '../services/api';

/** Asistente de ayuda conectado al microservicio Python. */
export default function Chatbot() {
  const [abierto, setAbierto] = useState(false);
  const [pregunta, setPregunta] = useState('');
  const [mensajes, setMensajes] = useState([{ de: 'bot', texto: '¡Hola! Pregúntame sobre citas, pagos, planes o evaluaciones.' }]);
  const [enviando, setEnviando] = useState(false);

  const enviar = async (e) => {
    e.preventDefault();
    const texto = pregunta.trim();
    if (!texto) return;
    setMensajes((m) => [...m, { de: 'yo', texto }]);
    setPregunta('');
    setEnviando(true);
    try {
      const { data } = await api.post('/chatbot', { pregunta: texto });
      setMensajes((m) => [...m, { de: 'bot', texto: data.respuesta }]);
    } catch (error) {
      setMensajes((m) => [...m, { de: 'bot', texto: mensajeError(error) }]);
    } finally {
      setEnviando(false);
    }
  };

  return (
    <div className={`chatbot ${abierto ? 'chatbot--abierto' : ''}`}>
      {abierto && (
        <div className="chatbot__panel" role="dialog" aria-label="Asistente">
          <div className="chatbot__mensajes">
            {mensajes.map((m, i) => <p key={i} className={`burbuja burbuja--${m.de}`}>{m.texto}</p>)}
            {enviando && <p className="burbuja burbuja--bot">Escribiendo…</p>}
          </div>
          <form onSubmit={enviar} className="chatbot__form">
            <input value={pregunta} onChange={(e) => setPregunta(e.target.value)} placeholder="Escribe tu pregunta" aria-label="Pregunta" />
            <button className="btn" disabled={enviando}>Enviar</button>
          </form>
        </div>
      )}
      <button type="button" className="chatbot__boton" onClick={() => setAbierto(!abierto)} aria-label="Abrir asistente">
        {abierto ? '×' : '✦'}
      </button>
    </div>
  );
}
