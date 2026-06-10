<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>PUC Nómina</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; margin: 24px; }
  h1 { font-size: 16px; margin: 0; color: #312e81; }
  .muted { color: #6b7280; }
  .header { margin-bottom: 14px; border-bottom: 2px solid #4f46e5; padding-bottom: 8px; }
  .totals { margin: 10px 0 14px; width: 100%; }
  .totals td { padding: 6px 8px; background: #eef2ff; font-weight: bold; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #111827; color: #fff; padding: 6px 5px; text-align: left; font-size: 8px; }
  td { padding: 5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  .tr { text-align: right; }
  .tc { text-align: center; }
  .footer { margin-top: 12px; text-align: center; font-size: 8px; color: #6b7280; }
</style>
</head>
<body>
  <div class="header">
    <h1>Comprobante PUC de Nómina</h1>
    <div class="muted">
      Período: {{ \Carbon\Carbon::parse($periodo_inicio)->format('d/m/Y') }}
      - {{ \Carbon\Carbon::parse($periodo_fin)->format('d/m/Y') }}
      · Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
  </div>

  <table class="totals">
    <tr>
      <td>Total débito: $ {{ number_format($totales['debito'] ?? 0, 0, ',', '.') }}</td>
      <td>Total crédito: $ {{ number_format($totales['credito'] ?? 0, 0, ',', '.') }}</td>
      <td class="tr">Líneas: {{ count($lineas ?? []) }}</td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>Cuenta</th>
        <th>Concepto</th>
        <th>Tercero</th>
        <th>Centro costo</th>
        <th class="tr">Débito</th>
        <th class="tr">Crédito</th>
      </tr>
    </thead>
    <tbody>
      @foreach($lineas as $linea)
        <tr>
          <td>{{ $linea['cuenta'] }}</td>
          <td>{{ $linea['concepto'] }}</td>
          <td>{{ $linea['tercero'] }}</td>
          <td>{{ $linea['centro_costo'] }}</td>
          <td class="tr">$ {{ number_format($linea['debito'] ?? 0, 0, ',', '.') }}</td>
          <td class="tr">$ {{ number_format($linea['credito'] ?? 0, 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="footer">
    Generado por Nexus · Control contable de nómina
  </div>
</body>
</html>
