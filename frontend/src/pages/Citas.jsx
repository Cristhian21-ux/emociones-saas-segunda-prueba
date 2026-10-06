import { useCallback, useEffect, useState } from 'react';
import api, { mensajeError } from '../services/api';
import { useAuth } from '../context/AuthContext';
import Campo from '../components/Campo';
import Tabla from '../components/Tabla';
import Aviso from '../components/Aviso';

export const ESTADOS = {
  pendiente: 'Pendiente de pago', confirmada: 'Confirmada', atendida: 'Atendida',
  cancelada: 'Cancelada', no_agendada: 'No agendada',
};

const manana = () => new Date(Date.now() + 86400000).toISOString().slice(0, 10);

export default function Citas() {
  const { usuario } = useAuth();
  const esRecepcion = ['admin', 'recepcionista'].includes(usuario.role);
  const esPsicologo = ['admin', 'psicologo'].includes(usuario.role);
  const [citas, setCitas] = useState([]);
  const [pacientes, setPacientes] = useState([]);
  const [psicologos, setPsicologos] = useState([]);
  const [form, setForm] = useState({ paciente_id: '', psicologo_id: '', fecha: manana(), hora: '', motivo: '' });
  const [libres, setLibres] = useState([]);
  const [aviso, setAviso] = useState({});

  const cargar = useCallback(() => api.get('/citas').then(({ data }) => setCitas(data.data)), []);

  useEffect(() => {
    cargar();
    api.get('/pacientes').then(({ data }) => setPacientes(data.data));
    api.get('/personal', { params: { role: 'psicologo' } }).then(({ data }) => setPsicologos(data.filter((p) => p.activo)));
  }, [cargar]);

  useEffect(() => {
    if (!form.psicologo_id || !form.fecha) return;
    api.get('/agenda/disponibilidad', { params: { psicologo_id: form.psicologo_id, fecha: form.fecha } })
      .then(({ data }) => setLibres(data.libres))
      .catch(() => setLibres([]));
  }, [form.psicologo_id, form.fecha]);

  const cambiar = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  const agendar = async (e) => {
    e.preventDefault();
    try {
      await api.post('/citas', form);
      setAviso({ ok: 'Cita registrada y confirmación enviada.' });
      setForm({ ...form, hora: '', motivo: '' });
      cargar();
    } catch (err) {
      const alternativas = err.response?.data?.alternativas;
      setAviso({ error: mensajeError(err) + (alternativas?.length ? ` Libres: ${alternativas.join(', ')}` : '') });
    }
  };

  const accion = async (cita, ruta, cuerpo, ok) => {
    try {
      const { data } = await api.post(`/citas/${cita.id}/${ruta}`, cuerpo);
      setAviso({ ok: ok + (data.cupo_ofrecido_a ? ` Cupo ofrecido a ${data.cupo_ofrecido_a}.` : '') });
      cargar();
    } catch (err) {
      setAviso({ error: mensajeError(err) });
    }
  };

  const cobrar = (cita) => {
    const monto = window.prompt('Monto en soles', '80');
    const comprobante = monto && window.prompt('N.º de comprobante', `B001-${String(cita.id).padStart(5, '0')}`);
    if (comprobante) accion(cita, 'cobrar', { monto, comprobante_numero: comprobante, metodo_pago: 'yape' }, 'Pago registrado.');
  };

  return (
    <>
      <header className="cabecera"><h1>Citas y agenda</h1></header>
      <Aviso>{aviso.ok}</Aviso><Aviso tipo="error">{aviso.error}</Aviso>
      <div className="rejilla">
        <section className="tarjeta">
          <Tabla
            filas={citas}
            columnas={[
              { titulo: 'Fecha', valor: (c) => `${c.fecha} ${c.hora}` },
              { titulo: 'Paciente', valor: (c) => `${c.paciente?.nombres} ${c.paciente?.apellidos}` },
              { titulo: 'Psicólogo', valor: (c) => c.psicologo?.name },
              { titulo: 'Estado', valor: (c) => <span className={`estado estado--${c.estado}`}>{ESTADOS[c.estado]}</span> },
              {
                titulo: 'Acciones',
                valor: (c) => (
                  <div className="acciones">
                    {esRecepcion && !c.pagado && c.estado === 'pendiente' && <button type="button" className="btn btn--mini" onClick={() => cobrar(c)}>Cobrar</button>}
                    {esPsicologo && c.estado === 'confirmada' && <button type="button" className="btn btn--mini" onClick={() => accion(c, 'atender', {}, 'Cita atendida.')}>Atender</button>}
                    {esRecepcion && ['pendiente', 'confirmada'].includes(c.estado) && <button type="button" className="btn btn--mini btn--peligro" onClick={() => accion(c, 'cancelar', {}, 'Cita cancelada.')}>Cancelar</button>}
                  </div>
                ),
              },
            ]}
          />
        </section>
        {esRecepcion && (
          <form className="tarjeta formulario" onSubmit={agendar}>
            <h2>Nueva cita</h2>
            <Campo as="select" label="Paciente" nombre="paciente_id" value={form.paciente_id} onChange={cambiar} required>
              <option value="">Selecciona…</option>
              {pacientes.map((p) => <option key={p.id} value={p.id}>{p.nombre_completo}</option>)}
            </Campo>
            <Campo as="select" label="Psicólogo" nombre="psicologo_id" value={form.psicologo_id} onChange={cambiar} required>
              <option value="">Selecciona…</option>
              {psicologos.map((p) => <option key={p.id} value={p.id}>{p.name} · {p.especialidad}</option>)}
            </Campo>
            <Campo label="Fecha" nombre="fecha" type="date" min={manana()} value={form.fecha} onChange={cambiar} required />
            <div className="horas" role="group" aria-label="Horas libres">
              {libres.length === 0 && <small>Elige psicólogo y fecha para ver horarios libres.</small>}
              {libres.map((h) => (
                <button type="button" key={h} className={`hora ${form.hora === h ? 'hora--activa' : ''}`} onClick={() => setForm({ ...form, hora: h })}>{h}</button>
              ))}
            </div>
            <Campo label="Motivo" nombre="motivo" value={form.motivo} onChange={cambiar} required />
            <button className="btn" disabled={!form.hora}>Agendar</button>
          </form>
        )}
      </div>
    </>
  );
}
