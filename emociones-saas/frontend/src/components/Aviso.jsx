export default function Aviso({ tipo = 'ok', children }) {
  if (!children) return null;
  return <div className={`aviso aviso--${tipo}`} role={tipo === 'error' ? 'alert' : 'status'}>{children}</div>;
}
