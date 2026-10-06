import { useEffect, useState } from 'react';
import api, { mensajeError } from '../services/api';
import Campo from '../components/Campo';
import Aviso from '../components/Aviso';
import Tabla from '../components/Tabla';

export const PREGUNTAS = {
  phq9: [
    'Poco interés o placer en hacer cosas', 'Sentirse decaído, deprimido o sin esperanza',
    'Problemas para dormir o dormir demasiado', 'Sentirse cansado o con poca energía',
    'Poco apetito o comer en exceso', 'Sentirse mal consigo mismo o que es un fracaso',
    'Dificultad para concentrarse', 'Moverse o hablar muy lento, o estar muy inquieto',
    'Pensamientos de que estaría mejor muerto o de hacerse daño',
  ],
  gad7: [
    'Sentirse nervioso, ansioso o con los nervios de punta', 'No poder dejar de preocuparse',
    'Preocuparse demasiado por diferentes cosas', 'Dificultad para relajarse',
    'Estar tan inquieto que es difícil quedarse quieto', 'Molestarse o irritarse fácilmente',
    'Sentir miedo como si algo terrible fuera a pasar',
  ],
};
const OPCIONES = ['Nunca', 'Varios días', 'Más de la mitad', 'Casi todos los días'];

export default function Evaluaciones() {
  const [instrumento, setInstrumento] = useState('phq9');
  const [respuestas, setRespuestas] = useState(Array(9).fill(0));
  const [pacienteId, setPacienteId] = useState('');
  const [pacientes, setPacientes] = useState([]);
  const [historial, setHistorial] = useState([]);
  const [resultado, setResultado] = useState(null);
  const [error, setError] = useState('');

  const cargar = () => api.get('/evaluaciones').then(({ data }) => setHistorial(data.data));
  useEffect(() => { cargar(); api.get('/pacientes').then(({ data }) => setPacientes(data.data)); }, []);

  const elegir = (inst) => { setInstrumento(inst); setRespuestas(Array(PREGUNTAS[inst].length).fill(0)); setResultado(null); };

  const enviar = async (e) => {
    e.preventDefault();
    setError('');
    try {
      const { data } = await api.post('/evaluaciones', { paciente_id: pacienteId, instrumento, respuestas });
      setResultado(data);
      cargar();
    } catch (err) { setError(mensajeError(err)); }
  };

  return (
    <>
      <header className="cabecera"><h1>Evaluaciones psicológicas</h1><p>Calificación automática por el servicio de IA en Python.</p></header>
      <Aviso tipo="error">{error}</Aviso>
      {resultado && (
        <Aviso tipo={resultado.alerta ? 'error' : 'ok'}>
          {resultado.interpretacion} (calificado por: {resultado.fuente === 'ia' ? 'servicio IA' : 'respaldo local'})
        </Aviso>
      )}
      <div className="rejilla">
        <form className="tarjeta formulario" onSubmit={enviar}>
          <div className="pestanas">
            <button type="button" className={instrumento === 'phq9' ? 'activa' : ''} onClick={() => elegir('phq9')}>PHQ-9 Depresión</button>
            <button type="button" className={instrumento === 'gad7' ? 'activa' : ''} onClick={() => elegir('gad7')}>GAD-7 Ansiedad</button>
          </div>
          <Campo as="select" label="Paciente" nombre="paciente_id" value={pacienteId} onChange={(e) => setPacienteId(e.target.value)} required>
            <option value="">Selecciona…</option>
            {pacientes.map((p) => <option key={p.id} value={p.id}>{p.nombre_completo}</option>)}
          </Campo>
          <p className="ayuda">En las últimas 2 semanas, ¿con qué frecuencia…</p>
          {PREGUNTAS[instrumento].map((texto, i) => (
            <label key={texto} className="pregunta">
              <span>{i + 1}. {texto}</span>
              <select value={respuestas[i]} onChange={(e) => setRespuestas(respuestas.map((r, j) => (j === i ? Number(e.target.value) : r)))}>
                {OPCIONES.map((o, v) => <option key={o} value={v}>{o}</option>)}
              </select>
            </label>
          ))}
          <button className="btn">Calificar</button>
        </form>
        <section className="tarjeta">
          <h2>Historial</h2>
          <Tabla filas={historial} columnas={[
            { titulo: 'Paciente', valor: (e) => `${e.paciente?.nombres} ${e.paciente?.apellidos}` },
            { titulo: 'Prueba', valor: (e) => e.instrumento.toUpperCase() },
            { titulo: 'Puntaje', valor: (e) => e.puntaje },
            { titulo: 'Severidad', valor: (e) => <span className={`estado ${e.alerta ? 'estado--cancelada' : 'estado--confirmada'}`}>{e.severidad.replace('_', ' ')}</span> },
          ]} />
        </section>
      </div>
    </>
  );
}
