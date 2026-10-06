/** Campo de formulario con etiqueta y mensaje de error accesible. */
export default function Campo({ label, nombre, error, as = 'input', children, ...props }) {
  const Control = as;
  return (
    <label className="campo">
      <span>{label}</span>
      <Control name={nombre} aria-invalid={Boolean(error)} data-cy={nombre} {...props}>{children}</Control>
      {error && <small className="campo__error" data-cy={`error-${nombre}`}>{error}</small>}
    </label>
  );
}
