import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import api, { tokenStore } from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [usuario, setUsuario] = useState(null);
  const [cargando, setCargando] = useState(Boolean(tokenStore.get()));

  useEffect(() => {
    if (!tokenStore.get()) return;
    api.get('/auth/me')
      .then(({ data }) => setUsuario(data))
      .catch(() => tokenStore.clear())
      .finally(() => setCargando(false));
  }, []);

  const iniciarSesion = useCallback(async (email, password) => {
    const { data } = await api.post('/auth/login', { email, password });
    tokenStore.set(data.token);
    setUsuario(data.usuario);
    return data.usuario;
  }, []);

  const registrar = useCallback(async (datos) => {
    const { data } = await api.post('/auth/registro', datos);
    tokenStore.set(data.token);
    setUsuario(data.usuario);
    return data.usuario;
  }, []);

  const cerrarSesion = useCallback(async () => {
    try { await api.post('/auth/logout'); } catch { /* el token ya puede haber expirado */ }
    tokenStore.clear();
    setUsuario(null);
  }, []);

  const valor = useMemo(
    () => ({ usuario, setUsuario, cargando, iniciarSesion, registrar, cerrarSesion }),
    [usuario, cargando, iniciarSesion, registrar, cerrarSesion],
  );

  return <AuthContext.Provider value={valor}>{children}</AuthContext.Provider>;
}

export const useAuth = () => useContext(AuthContext);
