import { useEffect, useRef, useState } from 'react';
import { montarEscena, soportaWebGL } from './motorEscena3d';

/** Fondo 3D del login. Si el navegador no soporta WebGL muestra un degradado animado. */
export default function Escena3D() {
  const ref = useRef(null);
  const [webgl] = useState(soportaWebGL);

  useEffect(() => {
    if (!webgl || !ref.current) return undefined;
    const reducir = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    return montarEscena(ref.current, { reducirMovimiento: reducir });
  }, [webgl]);

  return (
    <div
      ref={ref}
      className={`escena-3d ${webgl ? '' : 'escena-3d--respaldo'}`}
      data-testid="escena-3d"
      data-webgl={webgl ? 'si' : 'no'}
      aria-hidden="true"
    />
  );
}
