import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import Tabla from '../components/Tabla';
import { TarjetaPlan } from '../pages/Suscripcion';
import { mensajeError } from '../services/api';

let auth = { usuario: null, cargando: false };
vi.mock('../context/AuthContext', () => ({ useAuth: () => auth }));
const { default: RutaPrivada } = await import('../routes/RutaPrivada');

const plan = { codigo: 'vip', nombre: 'VIP', precio_mensual: '15.00', precio_anual: '150.00', max_psicologos: 10, max_pacientes: 1000, evaluaciones: true, analisis_emociones: false };

describe('componentes', () => {
  it('Tabla muestra mensaje cuando no hay filas', () => {
    render(<Tabla columnas={[]} filas={[]} vacio="Nada aún" />);
    expect(screen.getByText('Nada aún')).toBeInTheDocument();
  });

  it('Tabla pinta filas y columnas', () => {
    render(<Tabla columnas={[{ titulo: 'Nombre', valor: (f) => f.n }]} filas={[{ id: 1, n: 'Ana' }, { id: 2, n: 'Luis' }]} />);
    expect(screen.getAllByRole('row')).toHaveLength(3);
  });

  it('TarjetaPlan muestra precio anual y permite elegir', async () => {
    const elegir = vi.fn();
    render(<TarjetaPlan plan={plan} ciclo="anual" actual={false} onElegir={elegir} />);
    expect(screen.getByText('USD 150 / año')).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Elegir VIP' }));
    expect(elegir).toHaveBeenCalledWith(plan);
  });

  it('TarjetaPlan marca el plan actual', () => {
    render(<TarjetaPlan plan={plan} ciclo="mensual" actual onElegir={() => {}} />);
    expect(screen.getByText('Plan actual')).toBeInTheDocument();
  });

  it('mensajeError prioriza errores de validación', () => {
    expect(mensajeError({ response: { data: { message: 'x', errors: { dni: ['DNI inválido'] } } } })).toBe('DNI inválido');
    expect(mensajeError({})).toBe('No se pudo conectar con el servidor.');
  });

  it('RutaPrivada redirige al login sin sesión', () => {
    auth = { usuario: null, cargando: false };
    render(
      <MemoryRouter initialEntries={['/panel']}>
        <Routes>
          <Route path="/login" element={<p>pantalla login</p>} />
          <Route path="/panel" element={<RutaPrivada><p>panel</p></RutaPrivada>} />
        </Routes>
      </MemoryRouter>,
    );
    expect(screen.getByText('pantalla login')).toBeInTheDocument();
  });

  it('RutaPrivada bloquea un rol no permitido', () => {
    auth = { usuario: { role: 'recepcionista' }, cargando: false };
    render(
      <MemoryRouter initialEntries={['/personal']}>
        <Routes>
          <Route path="/panel" element={<p>panel</p>} />
          <Route path="/personal" element={<RutaPrivada roles={['admin']}><p>personal</p></RutaPrivada>} />
        </Routes>
      </MemoryRouter>,
    );
    expect(screen.getByText('panel')).toBeInTheDocument();
  });
});
