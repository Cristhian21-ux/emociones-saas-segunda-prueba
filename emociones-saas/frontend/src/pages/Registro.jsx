import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Escena3D from '../components/Escena3D.jsx';
import Campo from '../components/Campo';
import Aviso from '../components/Aviso';
import { useAuth } from '../context/AuthContext';
import { mensajeError } from '../services/api';
import { validarRegistro } from '../utils/validaciones';

const VACIO = { centro: '', ruc: '', name: '', email: '', password: '', password_confirmation: '' };

export default function Registro() {
  const { registrar } = useAuth();
  const navegar = useNavigate();
  const [datos, setDatos] = useState(VACIO);
  const [errores, setErrores] = useState({});
  const [error, setError] = useState('');
  const [enviando, setEnviando] = useState(false);

  const cambiar = (e) => setDatos({ ...datos, [e.target.name]: e.target.value });

  const enviar = async (e) => {
    e.preventDefault();
    const encontrados = validarRegistro(datos);
    setErrores(encontrados);
    if (Object.keys(encontrados).length) return;
    setEnviando(true);
    try {
      await registrar({ ...datos, ruc: datos.ruc || null });
      navegar('/panel');
    } catch (err) {
      setError(mensajeError(err));
    } finally {
      setEnviando(false);
    }
  };

  return (
    <div className="acceso">
      <Escena3D />
      <section className="acceso__intro">
        <span className="chip chip--brillo">Plan Gratuito incluido</span>
        <h1>Registra tu<br /><em>centro psicológico.</em></h1>
        <p>Empieza gratis con 2 psicólogos y 50 pacientes. Mejora a VIP o Premium cuando lo necesites.</p>
      </section>
      <form className="tarjeta-vidrio" onSubmit={enviar} noValidate aria-label="Registro de centro">
        <h2>Crear cuenta</h2>
        <Aviso tipo="error">{error}</Aviso>
        <Campo label="Nombre del centro" nombre="centro" value={datos.centro} onChange={cambiar} error={errores.centro} />
        <Campo label="RUC (opcional)" nombre="ruc" value={datos.ruc} onChange={cambiar} />
        <Campo label="Tu nombre" nombre="name" value={datos.name} onChange={cambiar} error={errores.name} />
        <Campo label="Correo" nombre="email" type="email" value={datos.email} onChange={cambiar} error={errores.email} />
        <Campo label="Contraseña" nombre="password" type="password" value={datos.password} onChange={cambiar} error={errores.password} />
        <Campo label="Repite la contraseña" nombre="password_confirmation" type="password" value={datos.password_confirmation} onChange={cambiar} error={errores.password_confirmation} />
        <button className="btn btn--grande" disabled={enviando} data-cy="submit">{enviando ? 'Creando…' : 'Crear cuenta gratis'}</button>
        <p className="acceso__pie">¿Ya tienes cuenta? <Link to="/login">Inicia sesión</Link></p>
      </form>
    </div>
  );
}
