import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Escena3D from '../components/Escena3D.jsx';
import Campo from '../components/Campo';
import Aviso from '../components/Aviso';
import { useAuth } from '../context/AuthContext';
import { mensajeError } from '../services/api';
import { validarLogin } from '../utils/validaciones';
import { rutaInicial } from '../utils/menu';
import logo from '../assets/logo-emociones.png';

export default function Login() {
  const { iniciarSesion } = useAuth();
  const navegar = useNavigate();
  const [datos, setDatos] = useState({ email: '', password: '' });
  const [errores, setErrores] = useState({});
  const [error, setError] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [verClave, setVerClave] = useState(false);

  const cambiar = (e) => setDatos({ ...datos, [e.target.name]: e.target.value });

  const enviar = async (e) => {
    e.preventDefault();
    const encontrados = validarLogin(datos);
    setErrores(encontrados);
    setError('');
    if (Object.keys(encontrados).length) return;
    setEnviando(true);
    try {
      const usuario = await iniciarSesion(datos.email, datos.password);
      navegar(rutaInicial(usuario));
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
        <span className="chip chip--brillo">SaaS para centros psicológicos</span>
        <h1>Cada emoción,<br /><em>en su lugar.</em></h1>
        <p>Agenda, historias clínicas, evaluaciones PHQ-9/GAD-7 y análisis de emociones con IA, en una sola plataforma.</p>
      </section>
      <form className="tarjeta-vidrio" onSubmit={enviar} noValidate aria-label="Iniciar sesión">
        <div className="tarjeta-vidrio__cabecera">
          <img className="marca__logo marca__logo--grande" src={logo} alt="Logo del Centro Psicológico Emociones" />
          <h2>Iniciar sesión</h2>
          <p>Centro Psicológico Emociones</p>
        </div>
        <Aviso tipo="error">{error}</Aviso>
        <Campo label="Correo" nombre="email" type="email" autoComplete="email" value={datos.email} onChange={cambiar} error={errores.email} />
        <Campo label="Contraseña" nombre="password" type={verClave ? 'text' : 'password'} autoComplete="current-password" value={datos.password} onChange={cambiar} error={errores.password} />
        <label className="check">
          <input type="checkbox" checked={verClave} onChange={() => setVerClave(!verClave)} /> Mostrar contraseña
        </label>
        <button className="btn btn--grande" disabled={enviando} data-cy="submit">{enviando ? 'Ingresando…' : 'Ingresar'}</button>
        <p className="acceso__pie">¿Tu centro aún no usa Emociones? <Link to="/registro">Crea tu cuenta gratis</Link></p>
      </form>
    </div>
  );
}
