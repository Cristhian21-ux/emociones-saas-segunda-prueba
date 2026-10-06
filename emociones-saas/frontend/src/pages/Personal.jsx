import { useCallback, useEffect, useState } from 'react';
import api, { mensajeError } from '../services/api';
import Tabla from '../components/Tabla';
import Campo from '../components/Campo';
import Aviso from '../components/Aviso';

const ROLES = { admin: 'Administración', recepcionista: 'Recepción', psicologo: 'Psicología' };
const VACIO = { name: '', email: '', role: 'psicologo', especialidad: '', password: '' };

export default function Personal() {
  const [lista, setLista] = useState([]);
  const [form, setForm] = useState(VACIO);
  const [aviso, setAviso] = useState({});

  const cargar = useCallback(() => api.get('/personal').then(({ data }) => setLista(data)), []);
  useEffect(() => { cargar(); }, [cargar]);

  const guardar = async (e) => {
    e.preventDefault();
    try {
      await api.post('/personal', form);
      setAviso({ ok: 'Personal agregado.' });
      setForm(VACIO);
      cargar();
    } catch (err) { setAviso({ error: mensajeError(err) }); }
  };

  const alternar = async (u) => {
    try { await api.patch(`/personal/${u.id}/activo`); cargar(); } catch (err) { setAviso({ error: mensajeError(err) }); }
  };
  const cambiar = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  return (
    <>
      <header className="cabecera"><h1>Personal</h1></header>
      <Aviso>{aviso.ok}</Aviso><Aviso tipo="error">{aviso.error}</Aviso>
      <div className="rejilla">
        <section className="tarjeta">
          <Tabla filas={lista} columnas={[
            { titulo: 'Nombre', valor: (u) => u.name },
            { titulo: 'Rol', valor: (u) => ROLES[u.role] },
            { titulo: 'Especialidad', valor: (u) => u.especialidad || '—' },
            { titulo: 'Estado', valor: (u) => <button type="button" className={`btn btn--mini ${u.activo ? '' : 'btn--peligro'}`} onClick={() => alternar(u)}>{u.activo ? 'Activo' : 'Inactivo'}</button> },
          ]} />
        </section>
        <form className="tarjeta formulario" onSubmit={guardar}>
          <h2>Agregar personal</h2>
          <Campo label="Nombre" nombre="name" value={form.name} onChange={cambiar} required />
          <Campo label="Correo" nombre="email" type="email" value={form.email} onChange={cambiar} required />
          <Campo as="select" label="Rol" nombre="role" value={form.role} onChange={cambiar}>
            {Object.entries(ROLES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </Campo>
          {form.role === 'psicologo' && <Campo label="Especialidad" nombre="especialidad" value={form.especialidad} onChange={cambiar} required />}
          <Campo label="Contraseña inicial" nombre="password" type="password" value={form.password} onChange={cambiar} required />
          <button className="btn">Agregar</button>
        </form>
      </div>
    </>
  );
}
