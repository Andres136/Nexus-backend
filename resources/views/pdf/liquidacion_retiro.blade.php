<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Liquidación definitiva</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #243047; font-size: 11px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #64748b; }
        .box { border: 1px solid #cbd5e1; border-radius: 5px; padding: 12px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 7px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; text-align: left; }
        .right { text-align: right; }
        .total { font-weight: bold; background: #ecfdf5; }
    </style>
</head>
<body>
    <h1>Liquidación definitiva de contrato</h1>
    <div class="muted">{{ $empresa->nombre ?? 'Empresa' }}</div>

    <div class="box">
        <table>
            <tr><td>Empleado</td><td>{{ $liquidacion->empleado->name ?? '—' }}</td></tr>
            <tr><td>Documento</td><td>{{ $liquidacion->contratacion->numero_documento ?? '—' }}</td></tr>
            <tr><td>Fecha de retiro</td><td>{{ $liquidacion->fecha_retiro?->format('d/m/Y') }}</td></tr>
            <tr><td>Motivo</td><td>{{ str_replace('_', ' ', $liquidacion->motivo_retiro) }}</td></tr>
        </table>
    </div>

    <div class="box">
        <table>
            <thead><tr><th>Concepto</th><th class="right">Valor</th></tr></thead>
            <tbody>
                <tr><td>Salario y novedades pendientes</td><td class="right">$ {{ number_format($liquidacion->salario_pendiente, 0, ',', '.') }}</td></tr>
                <tr><td>Pago no prestacional pendiente</td><td class="right">$ {{ number_format($liquidacion->pago_no_prestacional, 0, ',', '.') }}</td></tr>
                <tr><td>Comisiones pendientes</td><td class="right">$ {{ number_format($liquidacion->comisiones_pendientes, 0, ',', '.') }}</td></tr>
                <tr><td>Cesantías</td><td class="right">$ {{ number_format($liquidacion->cesantias, 0, ',', '.') }}</td></tr>
                <tr><td>Intereses de cesantías</td><td class="right">$ {{ number_format($liquidacion->intereses_cesantias, 0, ',', '.') }}</td></tr>
                <tr><td>Prima de servicios</td><td class="right">$ {{ number_format($liquidacion->prima_servicios, 0, ',', '.') }}</td></tr>
                <tr><td>Vacaciones pendientes</td><td class="right">$ {{ number_format($liquidacion->vacaciones, 0, ',', '.') }}</td></tr>
                <tr><td>Indemnización</td><td class="right">$ {{ number_format($liquidacion->indemnizacion, 0, ',', '.') }}</td></tr>
                <tr><td>Total deducciones</td><td class="right">- $ {{ number_format($liquidacion->total_deducciones, 0, ',', '.') }}</td></tr>
                <tr class="total"><td>Neto a pagar</td><td class="right">$ {{ number_format($liquidacion->neto_pagar, 0, ',', '.') }}</td></tr>
            </tbody>
        </table>
    </div>
</body>
</html>
