import { describe, expect, it } from 'vitest';
import { esCelularPeru, esClaveSegura, esDni, esEmail, formatoSoles, validarLogin, validarPaciente, validarRegistro } from '../utils/validaciones';

describe('validaciones', () => {
  it('reconoce correos válidos e inválidos', () => {
    expect(esEmail('ana@centro.pe')).toBe(true);
    expect(esEmail('no-es-correo')).toBe(false);
  });

  it('rechaza correos mal formados', () => {
    const invalidos = ['', null, 'a@b', 'a@.com', 'a@b.', '@b.pe', 'a@@b.pe', 'a b@c.pe', 'a@b@c.pe', 'a@b .pe'];
    invalidos.forEach((v) => expect(esEmail(v)).toBe(false));
    expect(esEmail('  ana@centro.com.pe  ')).toBe(true);
  });

  it('valida DNI de 8 dígitos', () => {
    expect(esDni('12345678')).toBe(true);
    expect(esDni('1234567')).toBe(false);
    expect(esDni('1234567a')).toBe(false);
  });

  it('valida celular peruano', () => {
    expect(esCelularPeru('987654321')).toBe(true);
    expect(esCelularPeru('087654321')).toBe(false);
  });

  it('exige contraseña con letras y números', () => {
    expect(esClaveSegura('Clave12345')).toBe(true);
    expect(esClaveSegura('soloLetras')).toBe(false);
    expect(esClaveSegura('1234567')).toBe(false);
  });

  it('validarLogin reporta campos vacíos', () => {
    expect(validarLogin({ email: '', password: '' })).toEqual({ email: expect.any(String), password: expect.any(String) });
    expect(validarLogin({ email: 'a@b.pe', password: 'x' })).toEqual({});
  });

  it('validarRegistro detecta contraseñas distintas', () => {
    const errores = validarRegistro({ centro: 'C', name: 'N', email: 'a@b.pe', password: 'Clave12345', password_confirmation: 'Otra12345' });
    expect(errores.password_confirmation).toBeDefined();
  });

  it('validarPaciente acepta teléfono vacío pero no uno inválido', () => {
    const base = { nombres: 'A', apellidos: 'B', dni: '12345678' };
    expect(validarPaciente(base)).toEqual({});
    expect(validarPaciente({ ...base, telefono: '123' }).telefono).toBeDefined();
  });

  it('formatea soles', () => {
    expect(formatoSoles(80)).toBe('S/ 80.00');
  });
});
