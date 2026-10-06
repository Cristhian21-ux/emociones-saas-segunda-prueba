import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { rutaInicial } from '../utils/menu';

/** Protege una ruta por sesión y, opcionalmente, por rol. */
export default function RutaPrivada({ roles, children }) {
  const { usuario, cargando } = useAuth();

  if (cargando) return <div className="cargando">Cargando…</div>;
  if (!usuario) return <Navigate to="/login" replace />;
  if (roles && !roles.includes(usuario.role)) return <Navigate to={rutaInicial(usuario)} replace />;

  return children;
}
