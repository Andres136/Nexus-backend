<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Torre de Control VSM</title>

<style>
@page {
    size: A4 landscape;
    margin: 8mm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 9px;
    color: #333;
    margin: 0;
}

.header {
    display: flex;
    align-items: center;
    border-bottom: 2px solid #1f6fd2;
    padding-bottom: 4px;
    margin-bottom: 8px;
}
.header img {
    height: 28px;
}
.header h2 {
    flex: 1;
    text-align: center;
    margin: 0;
    font-size: 13px;
    color: #1f2d3d;
}
.header span {
    font-size: 8px;
    color: #6b7280;
}

.orden-box {
    border: 1px solid #d1d5db;
    margin-bottom: 8px;
    page-break-inside: avoid;
}
.orden-header {
    display: table;
    width: 100%;
    background: #f4f9ff;
    border-bottom: 1px solid #d1d5db;
    padding: 4px 6px;
}
.orden-header .col {
    display: table-cell;
    padding: 2px 6px;
    vertical-align: top;
}
.orden-header strong {
    display: block;
    font-size: 8px;
    color: #6b7280;
    text-transform: uppercase;
}
.orden-header span {
    font-size: 10px;
    font-weight: 600;
    color: #1f2d3d;
}
.badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 8px;
    font-size: 8px;
    font-weight: bold;
    color: #fff;
    background: #6b7280;
}

table.detalle {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
table.detalle th {
    background: #eef2f7;
    color: #1f2d3d;
    padding: 3px;
    border: 1px solid #e5e7eb;
    font-size: 8px;
}
table.detalle td {
    border: 1px solid #e5e7eb;
    padding: 3px;
    text-align: center;
    word-wrap: break-word;
    font-size: 8px;
}
table.detalle td.izq { text-align: left; }
</style>
</head>

<body>

<div class="header">
    <img src="{{ public_path('images/SETAS.png') }}">
    <h2>Torre de Control VSM</h2>
    <span>Generado: {{ $generadoEn->format('d/m/Y H:i') }}</span>
</div>

@forelse($ordenes as $orden)
<div class="orden-box">
    <div class="orden-header">
        <div class="col">
            <strong>OC / OT</strong>
            <span>{{ $orden['numero'] ?? ('OC #' . $orden['orden_id']) }} / OT #{{ $orden['orden_trabajo_id'] ?? 'N/A' }}</span>
        </div>
        <div class="col">
            <strong>Cliente</strong>
            <span>{{ $orden['cliente'] ?? 'N/A' }}</span>
        </div>
        <div class="col">
            <strong>Sede</strong>
            <span>{{ $orden['sede'] ?? 'N/A' }}</span>
        </div>
        <div class="col">
            <strong>Fecha entrega</strong>
            <span>{{ $orden['fecha_entrega'] ?? 'N/A' }}</span>
        </div>
        <div class="col">
            <strong>Estado VSM</strong>
            <span class="badge">{{ $orden['estado_vsm'] }}</span>
        </div>
        <div class="col">
            <strong>Req. / Stock / Alist.</strong>
            <span>{{ number_format($orden['total_requerido'], 1) }} / {{ number_format($orden['inventario']['stock_disponible'], 1) }} / {{ number_format($orden['alistamiento']['total_alistado'], 1) }} kg</span>
        </div>
    </div>

    <table class="detalle">
        <thead>
        <tr>
            <th style="width: 20%;">Producto</th>
            <th style="width: 10%;">Requerido</th>
            <th style="width: 10%;">Entregado</th>
            <th style="width: 10%;">Faltante</th>
            <th style="width: 10%;">Stock</th>
            <th style="width: 10%;">Estado</th>
            <th style="width: 30%;">Observaciones</th>
        </tr>
        </thead>
        <tbody>
        @foreach($orden['productos'] as $prod)
        <tr>
            <td class="izq">{{ $prod['producto'] ?? ('ID: ' . $prod['producto_id']) }}</td>
            <td>{{ number_format($prod['requerido'], 1) }}</td>
            <td>{{ number_format($prod['entregado'], 1) }}</td>
            <td>{{ number_format($prod['faltante'], 1) }}</td>
            <td>{{ number_format($prod['stock'], 1) }}</td>
            <td>{{ $prod['estado'] }}</td>
            <td class="izq">{{ $prod['observaciones'] ?? '-' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@empty
<p>No hay órdenes que cumplan los filtros seleccionados.</p>
@endforelse

</body>
</html>
