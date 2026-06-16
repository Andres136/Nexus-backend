<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>PUC Nómina</title>
<style>
* { box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; margin: 24px; }
h1 { font-size: 16px; margin: 0 0 4px; }
p { margin: 0 0 12px; color: #4b5563; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #d1d5db; padding: 6px; }
th { background: #111827; color: #ffffff; font-size: 8px; text-transform: uppercase; }
td.number { text-align: right; }
</style>
</head>
<body>
  <h1>PUC Nómina</h1>
  <p>Periodo: {{ $periodoInicio }} a {{ $periodoFin }}</p>

  <table>
    <thead>
      <tr>
        <th>Nómina</th>
        <th>Empleado</th>
        <th>Documento</th>
        <th>Inicio</th>
        <th>Fin</th>
        <th>Concepto</th>
        <th>Código</th>
        <th>Cuenta PUC</th>
        <th>Nombre cuenta</th>
        <th>Naturaleza</th>
        <th>Valor</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $row)
        <tr>
          <td>{{ $row['nomina_uuid'] }}</td>
          <td>{{ $row['empleado'] }}</td>
          <td>{{ $row['documento'] }}</td>
          <td>{{ $row['periodo_inicio'] }}</td>
          <td>{{ $row['periodo_fin'] }}</td>
          <td>{{ $row['concepto'] }}</td>
          <td>{{ $row['codigo'] }}</td>
          <td>{{ $row['cuenta_puc'] }}</td>
          <td>{{ $row['cuenta_nombre'] }}</td>
          <td>{{ $row['naturaleza'] }}</td>
          <td class="number">$ {{ number_format($row['valor'] ?? 0, 2, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
