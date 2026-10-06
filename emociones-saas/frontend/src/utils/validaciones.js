const SIN_ESPACIOS_NI_ARROBA = /^[^\s@]+$/;

/** usuario@dominio.ext: una sola arroba, sin espacios y con un punto dentro del dominio. */
export function esEmail(v) {
  const partes = String(v || '').trim().split('@');
  if (partes.length !== 2) return false;
  const [usuario, dominio] = partes;
  return SIN_ESPACIOS_NI_ARROBA.test(usuario) && SIN_ESPACIOS_NI_ARROBA.test(dominio) && dominio.slice(1, -1).includes('.');
}
export const esDni = (v) => /^\d{8}$/.test(String(v || ''));
export const esCelularPeru = (v) => /^9\d{8}$/.test(String(v || ''));
export const esClaveSegura = (v) => typeof v === 'string' && v.length >= 8 && /[a-zA-Z]/.test(v) && /\d/.test(v);

export function validarLogin({ email, password }) {
  const errores = {};
  if (!esEmail(email)) errores.email = 'Ingresa un correo válido.';
  if (!password) errores.password = 'Ingresa tu contraseña.';
  return errores;
}

export function validarRegistro(d) {
  const errores = {};
  if (!d.centro?.trim()) errores.centro = 'Ingresa el nombre del centro.';
  if (!d.name?.trim()) errores.name = 'Ingresa tu nombre.';
  if (!esEmail(d.email)) errores.email = 'Ingresa un correo válido.';
  if (!esClaveSegura(d.password)) errores.password = 'Mínimo 8 caracteres con letras y números.';
  if (d.password !== d.password_confirmation) errores.password_confirmation = 'Las contraseñas no coinciden.';
  return errores;
}

export function validarPaciente(d) {
  const errores = {};
  if (!d.nombres?.trim()) errores.nombres = 'Requerido.';
  if (!d.apellidos?.trim()) errores.apellidos = 'Requerido.';
  if (!esDni(d.dni)) errores.dni = 'El DNI tiene 8 dígitos.';
  if (d.telefono && !esCelularPeru(d.telefono)) errores.telefono = 'Celular de 9 dígitos que empieza con 9.';
  return errores;
}

export const formatoSoles = (monto) => `S/ ${Number(monto || 0).toFixed(2)}`;
export const formatoUsd = (monto) => `USD ${Number(monto || 0).toFixed(0)}`;
