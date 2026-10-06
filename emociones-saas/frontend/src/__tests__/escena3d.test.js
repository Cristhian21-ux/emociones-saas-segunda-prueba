import { describe, expect, it } from 'vitest';
import { crearParticulas, EMOCIONES, soportaWebGL } from '../components/motorEscena3d';

describe('escena 3D del login', () => {
  it('genera partículas dentro de la cáscara esférica', () => {
    const pos = crearParticulas(200, 4, 18);
    expect(pos).toHaveLength(600);
    for (let i = 0; i < 200; i++) {
      const r = Math.hypot(pos[i * 3], pos[i * 3 + 1], pos[i * 3 + 2]);
      expect(r).toBeGreaterThanOrEqual(3.999);
      expect(r).toBeLessThanOrEqual(18.001);
    }
  });

  it('es determinista con un generador fijo', () => {
    const rand = () => 0.5;
    expect(Array.from(crearParticulas(2, 1, 3, rand))).toEqual(Array.from(crearParticulas(2, 1, 3, rand)));
  });

  it('incluye cinco emociones con color', () => {
    expect(EMOCIONES.map((e) => e.nombre)).toEqual(['alegría', 'calma', 'tristeza', 'ansiedad', 'ira']);
  });

  it('detecta que jsdom no tiene WebGL', () => {
    expect(soportaWebGL()).toBe(false);
  });
});
