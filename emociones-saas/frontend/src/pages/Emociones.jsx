import { useState } from 'react';
import api, { mensajeError } from '../services/api';
import Aviso from '../components/Aviso';

export default function Emociones() {
  const [texto, setTexto] = useState('');
  const [r, setR] = useState(null);
  const [error, setError] = useState('');

  const analizar = async (e) => {
    e.preventDefault();
    setError('');
    try {
      const { data } = await api.post('/ia/emociones', { texto });
      setR(data);
    } catch (err) { setError(mensajeError(err)); }
  };

  return (
    <>
      <header className="cabecera"><h1>Análisis de emociones con IA</h1><p>Apoyo al criterio clínico. Plan Premium.</p></header>
      <Aviso tipo="error">{error}</Aviso>
      <div className="rejilla">
        <form className="tarjeta formulario" onSubmit={analizar}>
          <textarea rows={8} value={texto} onChange={(e) => setTexto(e.target.value)} placeholder="Pega aquí las notas de la sesión…" aria-label="Texto a analizar" />
          <button className="btn" disabled={texto.trim().length < 3}>Analizar</button>
        </form>
        {r && (
          <section className="tarjeta">
            {r.fuente === 'no_disponible' ? <p>El servicio de IA no está disponible en este momento.</p> : (
              <>
                <h2>Emoción dominante: {r.emocion_dominante}</h2>
                <p className={`estado estado--riesgo-${r.nivel_riesgo}`}>Riesgo {r.nivel_riesgo}</p>
                <div className="barras">
                  {Object.entries(r.puntajes).map(([emo, v]) => (
                    <div className="barra" key={emo}><span>{emo}</span><div><i style={{ width: `${v * 100}%` }} /></div><b>{Math.round(v * 100)}%</b></div>
                  ))}
                </div>
                <p className="ayuda">{r.recomendacion}</p>
              </>
            )}
          </section>
        )}
      </div>
    </>
  );
}
