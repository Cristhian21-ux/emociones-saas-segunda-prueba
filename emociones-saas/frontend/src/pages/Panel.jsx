import { useEffect, useState } from 'react';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';
import { formatoSoles } from '../utils/validaciones';

export function Indicador({ titulo, valor, tono = 'violeta' }) {
  return (
    <div className={`indicador indicador--${tono}`}>
      <span>{titulo}</span>
      <strong data-testid={`indicador-${titulo}`}>{valor}</strong>
    </div>
  );
}

export default function Panel() {
  const { usuario } = useAuth();
  const [m, setM] = useState(null);

  useEffect(() => { api.get('/dashboard').then(({ data }) => setM(data)); }, []);

  return (
    <>
      <header className="cabecera">
        <h1>Hola, {usuario.name.split(' ')[0]}</h1>
        <p>Resumen de {usuario.centro?.nombre}</p>
      </header>
      {!m ? <p>Cargando…</p> : (
        <>
          <div className="indicadores">
            <Indicador titulo="Citas hoy" valor={m.citas_hoy} tono="cian" />
            <Indicador titulo="Pacientes" valor={m.pacientes} />
            <Indicador titulo="Por cobrar" valor={m.citas_pendientes_pago} tono="ambar" />
            <Indicador titulo="Ingresos del mes" valor={formatoSoles(m.ingresos_mes)} tono="verde" />
            <Indicador titulo="En espera" valor={m.en_espera} tono="rosa" />
            <Indicador titulo="Alertas clínicas" valor={m.alertas_evaluacion} tono="rojo" />
          </div>
          <section className="tarjeta">
            <h2>Citas por estado</h2>
            <div className="barras">
              {Object.entries(m.citas_por_estado || {}).map(([estado, total]) => (
                <div key={estado} className="barra">
                  <span>{estado.replace('_', ' ')}</span>
                  <div><i style={{ width: `${Math.min(100, total * 10)}%` }} /></div>
                  <b>{total}</b>
                </div>
              ))}
            </div>
          </section>
        </>
      )}
    </>
  );
}
