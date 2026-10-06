import { useState } from 'react';
import api, { mensajeError } from '../services/api';
import { useAuth } from '../context/AuthContext';
import Campo from '../components/Campo';
import Aviso from '../components/Aviso';

export default function Perfil() {
  const { usuario, setUsuario } = useAuth();
  const [form, setForm] = useState({ name: usuario.name, especialidad: usuario.especialidad || '', password: '', password_confirmation: '' });
  const [aviso, setAviso] = useState({});

  const guardar = async (e) => {
    e.preventDefault();
    try {
      const { data } = await api.put('/perfil', form);
      setUsuario(data);
      setAviso({ ok: 'Perfil actualizado.' });
    } catch (err) { setAviso({ error: mensajeError(err) }); }
  };
  const cambiar = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  return (
    <>
      <header className="cabecera"><h1>Mi perfil</h1><p>{usuario.email}</p></header>
      <Aviso>{aviso.ok}</Aviso><Aviso tipo="error">{aviso.error}</Aviso>
      <form className="tarjeta formulario angosto" onSubmit={guardar}>
        <Campo label="Nombre" nombre="name" value={form.name} onChange={cambiar} required />
        {usuario.role === 'psicologo' && <Campo label="Especialidad" nombre="especialidad" value={form.especialidad} onChange={cambiar} />}
        <Campo label="Nueva contraseña (opcional)" nombre="password" type="password" value={form.password} onChange={cambiar} />
        <Campo label="Repite la contraseña" nombre="password_confirmation" type="password" value={form.password_confirmation} onChange={cambiar} />
        <button className="btn">Guardar</button>
      </form>
    </>
  );
}
