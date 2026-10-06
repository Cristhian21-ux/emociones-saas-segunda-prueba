import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { modulosPara } from '../utils/menu';
import Chatbot from './Chatbot';
import logo from '../assets/logo-emociones.png';

export default function Layout() {
  const { usuario, cerrarSesion } = useAuth();
  const navegar = useNavigate();

  const salir = async () => {
    await cerrarSesion();
    navegar('/login');
  };

  return (
    <div className="app">
      <aside className="lateral">
        <div className="marca">
          <img className="marca__logo" src={logo} alt="Logo del Centro Psicológico Emociones" />
          <div>
            <strong>Emociones</strong>
            <small>{usuario.centro?.nombre || 'Plataforma SaaS'}</small>
          </div>
        </div>
        <nav>
          {modulosPara(usuario).map((m) => (
            <NavLink key={m.ruta} to={m.ruta} className="lateral__link">
              <span aria-hidden="true">{m.icono}</span> {m.etiqueta}
            </NavLink>
          ))}
        </nav>
        <div className="lateral__pie">
          {usuario.centro && <span className={`chip chip--${usuario.centro.plan.codigo}`}>Plan {usuario.centro.plan.nombre}</span>}
          <NavLink to="/perfil" className="lateral__link">⚙ {usuario.name}</NavLink>
          <button type="button" className="btn btn--fantasma" onClick={salir}>Cerrar sesión</button>
        </div>
      </aside>
      <main className="contenido"><Outlet /></main>
      <Chatbot />
    </div>
  );
}
