import { useCallback, useEffect, useState } from 'react';
import api, { mensajeError } from '../services/api';
import Tabla from '../components/Tabla';
import Campo from '../components/Campo';
import Aviso from '../components/Aviso';

const FRANJAS = { cualquiera: 'Cualquier horario', manana: 'Mañana', tarde: 'Tarde' };

export default function ListaEspera() {
  const [lista, setLista] = useState([]);
  const [pacientes, setPacientes] = useState([]);
  const [form, setForm] = useState({ paciente_id: '', franja: 'cualquiera', motivo: '' });
  const [aviso, setAviso] = useState({});

  const cargar = useCallback(() => api.get('/lista-espera').then(({ data }) => setLista(data)), []);
  useEffect(() => { cargar(); api.get('/pacientes').then(({ data }) => setPacientes(data.data)); }, [cargar]);

  const agregar = async (e) => {
    e.preventDefault();
    try {
      await api.post('/lista-espera', form);
      setAviso({ ok: 'Paciente agregado a la lista de espera.' });
      cargar();
    } catch (err) { setAviso({ error: mensajeError(err) }); }
  };

  const retirar = async (id) => { await api.delete(`/lista-espera/${id}`); cargar(); };
  const cambiar = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  return (
    <>
      <header className="cabecera"><h1>Lista de espera</h1><p>Al cancelarse una cita, el cupo se ofrece automáticamente al primero compatible.</p></header>
      <Aviso>{aviso.ok}</Aviso><Aviso tipo="error">{aviso.error}</Aviso>
      <div className="rejilla">
        <section className="tarjeta">
          <Tabla filas={lista} columnas={[
            { titulo: 'Paciente', valor: (e) => `${e.paciente?.nombres} ${e.paciente?.apellidos}` },
            { titulo: 'Franja', valor: (e) => FRANJAS[e.franja] },
            { titulo: 'Estado', valor: (e) => (e.estado === 'notificado' ? `Cupo ofrecido ${e.cupo_fecha} ${e.cupo_hora}` : 'En espera') },
            { titulo: '', valor: (e) => <button type="button" className="btn btn--mini btn--peligro" onClick={() => retirar(e.id)}>Retirar</button> },
          ]} />
        </section>
        <form className="tarjeta formulario" onSubmit={agregar}>
          <h2>Agregar</h2>
          <Campo as="select" label="Paciente" nombre="paciente_id" value={form.paciente_id} onChange={cambiar} required>
            <option value="">Selecciona…</option>
            {pacientes.map((p) => <option key={p.id} value={p.id}>{p.nombre_completo}</option>)}
          </Campo>
          <Campo as="select" label="Franja" nombre="franja" value={form.franja} onChange={cambiar}>
            {Object.entries(FRANJAS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </Campo>
          <Campo label="Motivo" nombre="motivo" value={form.motivo} onChange={cambiar} />
          <button className="btn">Agregar</button>
        </form>
      </div>
    </>
  );
}
