/** Tabla simple: columnas = [{ titulo, valor: (fila) => nodo }]. */
export default function Tabla({ columnas, filas, vacio = 'Sin registros.' }) {
  if (!filas?.length) return <p className="vacio">{vacio}</p>;
  return (
    <div className="tabla-scroll">
      <table className="tabla">
        <thead><tr>{columnas.map((c) => <th key={c.titulo}>{c.titulo}</th>)}</tr></thead>
        <tbody>
          {filas.map((f) => (
            <tr key={f.id}>{columnas.map((c) => <td key={c.titulo}>{c.valor(f)}</td>)}</tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
