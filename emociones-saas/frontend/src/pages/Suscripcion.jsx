import { useEffect, useState } from 'react';
import api, { mensajeError } from '../services/api';
import { useAuth } from '../context/AuthContext';
import Aviso from '../components/Aviso';
import { formatoUsd } from '../utils/validaciones';

function textoPrecio(precio, ciclo) {
  if (Number(precio) === 0) return 'Gratis';
  const periodo = ciclo === 'anual' ? 'año' : 'mes';
  return `${formatoUsd(precio)} / ${periodo}`;
}

export function TarjetaPlan({ plan, actual, ciclo, onElegir }) {
  const precio = ciclo === 'anual' ? plan.precio_anual : plan.precio_mensual;
  return (
    <article className={`plan ${actual ? 'plan--actual' : ''} plan--${plan.codigo}`}>
      <h3>{plan.nombre}</h3>
      <p className="plan__precio">{textoPrecio(precio, ciclo)}</p>
      <ul>
        <li>Hasta {plan.max_psicologos} psicólogos</li>
        <li>Hasta {plan.max_pacientes.toLocaleString('es-PE')} pacientes</li>
        <li>{plan.evaluaciones ? '✓' : '✗'} Evaluaciones PHQ-9 / GAD-7</li>
        <li>{plan.analisis_emociones ? '✓' : '✗'} Análisis de emociones con IA</li>
      </ul>
      {actual ? <span className="chip">Plan actual</span> : <button type="button" className="btn" onClick={() => onElegir(plan)}>Elegir {plan.nombre}</button>}
    </article>
  );
}

export default function Suscripcion() {
  const { usuario, setUsuario } = useAuth();
  const [planes, setPlanes] = useState([]);
  const [estado, setEstado] = useState(null);
  const [ciclo, setCiclo] = useState('mensual');
  const [aviso, setAviso] = useState({});

  const cargar = () => api.get('/suscripcion').then(({ data }) => setEstado(data));
  useEffect(() => { api.get('/planes').then(({ data }) => setPlanes(data)); cargar(); }, []);

  const elegir = async (plan) => {
    try {
      await api.post('/suscripcion/cambiar', { plan: plan.codigo, ciclo });
      const { data } = await api.get('/auth/me');
      setUsuario(data);
      setAviso({ ok: `Ahora tienes el plan ${plan.nombre}.` });
      cargar();
    } catch (err) { setAviso({ error: mensajeError(err) }); }
  };

  return (
    <>
      <header className="cabecera"><h1>Mi suscripción</h1>
        {estado && <p>Uso: {estado.uso.psicologos}/{estado.uso.max_psicologos} psicólogos · {estado.uso.pacientes}/{estado.uso.max_pacientes} pacientes</p>}
      </header>
      <Aviso>{aviso.ok}</Aviso><Aviso tipo="error">{aviso.error}</Aviso>
      <div className="pestanas">
        <button type="button" className={ciclo === 'mensual' ? 'activa' : ''} onClick={() => setCiclo('mensual')}>Mensual</button>
        <button type="button" className={ciclo === 'anual' ? 'activa' : ''} onClick={() => setCiclo('anual')}>Anual (2 meses gratis)</button>
      </div>
      <div className="planes">
        {planes.map((p) => <TarjetaPlan key={p.id} plan={p} ciclo={ciclo} actual={usuario.centro?.plan.codigo === p.codigo} onElegir={elegir} />)}
      </div>
      <p className="ayuda">El cobro se procesa en la pasarela de pago; la plataforma no almacena datos de tarjeta.</p>
    </>
  );
}
