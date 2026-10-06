import axios from 'axios';

const CLAVE_TOKEN = 'emociones_token';

export const tokenStore = {
  get: () => localStorage.getItem(CLAVE_TOKEN),
  set: (token) => localStorage.setItem(CLAVE_TOKEN, token),
  clear: () => localStorage.removeItem(CLAVE_TOKEN),
};

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: { Accept: 'application/json' },
});

// Adjunta el token Sanctum en cada petición.
api.interceptors.request.use((config) => {
  const token = tokenStore.get();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Ante un 401 la sesión expiró: se limpia y se vuelve al login.
api.interceptors.response.use(
  (respuesta) => respuesta,
  (error) => {
    if (error.response?.status === 401 && tokenStore.get()) {
      tokenStore.clear();
      window.location.assign('/login');
    }
    return Promise.reject(error);
  },
);

/** Mensaje legible a partir de un error de la API (validación 422, plan 402, etc.). */
export function mensajeError(error) {
  const data = error?.response?.data;
  if (data?.errors) return Object.values(data.errors).flat()[0];
  return data?.message || 'No se pudo conectar con el servidor.';
}

export default api;
