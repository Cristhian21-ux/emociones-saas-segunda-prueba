import { useEffect, useState } from 'react';
import api from '../services/api';
import Tabla from '../components/Tabla';

export default function Auditoria() {
  const [lista, setLista] = useState([]);
  useEffect(() => { api.get('/auditoria').then(({ data }) => setLista(data.data)); }, []);

  return (
    <>
      <header className="cabecera"><h1>Auditoría</h1><p>Registro de acciones críticas (ISO/IEC 27001).</p></header>
      <section className="tarjeta">
        <Tabla filas={lista} columnas={[
          { titulo: 'Fecha', valor: (a) => a.created_at?.replace('T', ' ').slice(0, 19) },
          { titulo: 'Usuario', valor: (a) => a.usuario?.name || '—' },
          { titulo: 'Acción', valor: (a) => <code>{a.accion}</code> },
          { titulo: 'Entidad', valor: (a) => (a.entidad ? `${a.entidad} #${a.entidad_id}` : '—') },
          { titulo: 'IP', valor: (a) => a.ip },
        ]} />
      </section>
    </>
  );
}
