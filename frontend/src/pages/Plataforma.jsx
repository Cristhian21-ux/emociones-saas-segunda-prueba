import { useEffect, useState } from 'react';
import api from '../services/api';
import Tabla from '../components/Tabla';
import { Indicador } from './Panel';

export default function Plataforma() {
  const [centros, setCentros] = useState([]);
  const [m, setM] = useState(null);

  const cargar = () => {
    api.get('/plataforma/centros').then(({ data }) => setCentros(data));
    api.get('/plataforma/metricas').then(({ data }) => setM(data));
  };
  useEffect(cargar, []);

  const alternar = async (c) => {
    await api.patch(`/plataforma/centros/${c.id}/estado`, { estado: c.estado === 'activo' ? 'suspendido' : 'activo' });
    cargar();
  };

  return (
    <>
      <header className="cabecera"><h1>Centros suscritos</h1><p>Panel del proveedor SaaS.</p></header>
      {m && (
        <div className="indicadores">
          <Indicador titulo="Centros" valor={m.centros} />
          <Indicador titulo="Activos" valor={m.activos} tono="verde" />
          <Indicador titulo="MRR (USD)" valor={m.mrr} tono="cian" />
        </div>
      )}
      <section className="tarjeta">
        <Tabla filas={centros} columnas={[
          { titulo: 'Centro', valor: (c) => c.nombre },
          { titulo: 'Plan', valor: (c) => <span className={`chip chip--${c.plan.codigo}`}>{c.plan.nombre}</span> },
          { titulo: 'Usuarios', valor: (c) => c.usuarios_count },
          { titulo: 'Estado', valor: (c) => <button type="button" className={`btn btn--mini ${c.estado === 'activo' ? '' : 'btn--peligro'}`} onClick={() => alternar(c)}>{c.estado}</button> },
        ]} />
      </section>
    </>
  );
}
