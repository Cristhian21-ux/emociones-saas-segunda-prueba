import { useCallback, useEffect, useState } from 'react';
import api, { mensajeError } from '../services/api';
import { useAuth } from '../context/AuthContext';
import Campo from '../components/Campo';
import Tabla from '../components/Tabla';
import Aviso from '../components/Aviso';
import { validarPaciente } from '../utils/validaciones';

const VACIO = { nombres: '', apellidos: '', dni: '', telefono: '', email: '', fecha_nacimiento: '' };

export default function Pacientes() {
  const { usuario } = useAuth();
  const puedeEditar = ['admin', 'recepcionista'].includes(usuario.role);
  const [lista, setLista] = useState([]);
  const [buscar, setBuscar] = useState('');
  const [form, setForm] = useState(VACIO);
  const [errores, setErrores] = useState({});
  const [aviso, setAviso] = useState({});

  const cargar = useCallback(() => {
    api.get('/pacientes', { params: { buscar } }).then(({ data }) => setLista(data.data));
  }, [buscar]);

  useEffect(() => { cargar(); }, [cargar]);

  const guardar = async (e) => {
    e.preventDefault();
    const encontrados = validarPaciente(form);
    setErrores(encontrados);
    if (Object.keys(encontrados).length) return;
    try {
      const limpio = Object.fromEntries(Object.entries(form).filter(([, v]) => v !== ''));
      await api.post('/pacientes', limpio);
      setForm(VACIO);
      setAviso({ ok: 'Paciente registrado.' });
      cargar();
    } catch (err) {
      setAviso({ error: mensajeError(err) });
    }
  };

  const cambiar = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  return (
    <>
      <header className="cabecera"><h1>Pacientes</h1></header>
      <Aviso>{aviso.ok}</Aviso><Aviso tipo="error">{aviso.error}</Aviso>
      <div className="rejilla">
        <section className="tarjeta">
          <input className="buscador" placeholder="Buscar por nombre, apellido o DNI" value={buscar} onChange={(e) => setBuscar(e.target.value)} />
          <Tabla
            filas={lista}
            columnas={[
              { titulo: 'Paciente', valor: (p) => p.nombre_completo },
              { titulo: 'DNI', valor: (p) => p.dni },
              { titulo: 'Teléfono', valor: (p) => p.telefono || '—' },
            ]}
          />
        </section>
        {puedeEditar && (
          <form className="tarjeta formulario" onSubmit={guardar} noValidate>
            <h2>Nuevo paciente</h2>
            <Campo label="Nombres" nombre="nombres" value={form.nombres} onChange={cambiar} error={errores.nombres} />
            <Campo label="Apellidos" nombre="apellidos" value={form.apellidos} onChange={cambiar} error={errores.apellidos} />
            <Campo label="DNI" nombre="dni" inputMode="numeric" maxLength={8} value={form.dni} onChange={cambiar} error={errores.dni} />
            <Campo label="Celular" nombre="telefono" value={form.telefono} onChange={cambiar} error={errores.telefono} />
            <Campo label="Correo" nombre="email" type="email" value={form.email} onChange={cambiar} />
            <Campo label="Fecha de nacimiento" nombre="fecha_nacimiento" type="date" value={form.fecha_nacimiento} onChange={cambiar} />
            <button className="btn">Registrar</button>
          </form>
        )}
      </div>
    </>
  );
}
