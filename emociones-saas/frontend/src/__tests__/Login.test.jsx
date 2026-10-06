import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import Login from '../pages/Login';

const iniciarSesion = vi.fn();
vi.mock('../context/AuthContext', () => ({ useAuth: () => ({ iniciarSesion }) }));
const navegar = vi.fn();
vi.mock('react-router-dom', async (orig) => ({ ...(await orig()), useNavigate: () => navegar }));

const montar = () => render(<MemoryRouter><Login /></MemoryRouter>);

describe('Login con escena 3D', () => {
  beforeEach(() => { iniciarSesion.mockReset(); navegar.mockReset(); });

  it('muestra el formulario y el fondo 3D (con respaldo sin WebGL)', () => {
    montar();
    expect(screen.getByRole('heading', { name: 'Iniciar sesión' })).toBeInTheDocument();
    expect(screen.getByTestId('escena-3d')).toHaveAttribute('data-webgl', 'no');
  });

  it('valida los campos antes de enviar', async () => {
    montar();
    await userEvent.click(screen.getByRole('button', { name: 'Ingresar' }));
    expect(screen.getByText('Ingresa un correo válido.')).toBeInTheDocument();
    expect(iniciarSesion).not.toHaveBeenCalled();
  });

  it('inicia sesión y navega al panel', async () => {
    iniciarSesion.mockResolvedValue({ role: 'admin' });
    montar();
    await userEvent.type(screen.getByLabelText('Correo'), 'admin@emociones.test');
    await userEvent.type(screen.getByLabelText('Contraseña'), 'Emociones2026');
    await userEvent.click(screen.getByRole('button', { name: 'Ingresar' }));
    expect(iniciarSesion).toHaveBeenCalledWith('admin@emociones.test', 'Emociones2026');
    expect(navegar).toHaveBeenCalledWith('/panel');
  });

  it('muestra el error del servidor', async () => {
    iniciarSesion.mockRejectedValue({ response: { data: { message: 'Credenciales incorrectas.' } } });
    montar();
    await userEvent.type(screen.getByLabelText('Correo'), 'x@y.pe');
    await userEvent.type(screen.getByLabelText('Contraseña'), 'mala');
    await userEvent.click(screen.getByRole('button', { name: 'Ingresar' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('Credenciales incorrectas.');
  });

  it('permite mostrar la contraseña', async () => {
    montar();
    const clave = screen.getByLabelText('Contraseña');
    expect(clave).toHaveAttribute('type', 'password');
    await userEvent.click(screen.getByLabelText('Mostrar contraseña'));
    expect(clave).toHaveAttribute('type', 'text');
  });
});
