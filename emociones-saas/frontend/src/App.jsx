import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import RutaPrivada from './routes/RutaPrivada';
import Layout from './components/Layout';
import Login from './pages/Login';
import Registro from './pages/Registro';
import Panel from './pages/Panel';
import Pacientes from './pages/Pacientes';
import Citas from './pages/Citas';
import ListaEspera from './pages/ListaEspera';
import Historias from './pages/Historias';
import Evaluaciones from './pages/Evaluaciones';
import Emociones from './pages/Emociones';
import Personal from './pages/Personal';
import Suscripcion from './pages/Suscripcion';
import Auditoria from './pages/Auditoria';
import Plataforma from './pages/Plataforma';
import Perfil from './pages/Perfil';
import { rutaInicial } from './utils/menu';

const CENTRO = ['admin', 'recepcionista', 'psicologo'];

export default function App() {
  const { usuario } = useAuth();
  const conRol = (roles, elemento) => <RutaPrivada roles={roles}>{elemento}</RutaPrivada>;

  return (
    <Routes>
      <Route path="/login" element={usuario ? <Navigate to={rutaInicial(usuario)} replace /> : <Login />} />
      <Route path="/registro" element={usuario ? <Navigate to="/panel" replace /> : <Registro />} />
      <Route element={<RutaPrivada><Layout /></RutaPrivada>}>
        <Route path="/panel" element={conRol(CENTRO, <Panel />)} />
        <Route path="/citas" element={conRol(CENTRO, <Citas />)} />
        <Route path="/pacientes" element={conRol(CENTRO, <Pacientes />)} />
        <Route path="/espera" element={conRol(['admin', 'recepcionista'], <ListaEspera />)} />
        <Route path="/historias" element={conRol(['admin', 'psicologo'], <Historias />)} />
        <Route path="/evaluaciones" element={conRol(['admin', 'psicologo'], <Evaluaciones />)} />
        <Route path="/emociones" element={conRol(['admin', 'psicologo'], <Emociones />)} />
        <Route path="/personal" element={conRol(['admin'], <Personal />)} />
        <Route path="/suscripcion" element={conRol(['admin'], <Suscripcion />)} />
        <Route path="/auditoria" element={conRol(['admin'], <Auditoria />)} />
        <Route path="/plataforma" element={conRol(['superadmin'], <Plataforma />)} />
        <Route path="/perfil" element={<Perfil />} />
      </Route>
      <Route path="*" element={<Navigate to={usuario ? rutaInicial(usuario) : '/login'} replace />} />
    </Routes>
  );
}
