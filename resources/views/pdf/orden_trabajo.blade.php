<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Orden de Trabajo</title>

<style>
@page {
    size: A4;
    margin: 20mm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
    color: #333;
    margin: 0;
}

/* HEADER */
.header {
    display: flex;
    align-items: center;
    border-bottom: 2px solid #1f6fd2;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
.header img {
    height: 45px;
}
.header h2 {
    flex: 1;
    text-align: center;
    margin: 0;
    font-size: 18px;
    color: #1f2d3d;
}

/* INFO GRID */
.info {
    display: table;
    width: 100%;
    margin-bottom: 15px;
}
.info div {
    display: table-cell;
    padding: 6px 10px;
    vertical-align: top;
}
.info strong {
    display: block;
    font-size: 10px;
    color: #6b7280;
}
.info span {
    font-size: 12px;
    font-weight: 600;
}

/* OBSERVACIONES OC */
.alert {
    border: 1px solid #f0ad4e;
    background: #fff7e6;
    padding: 10px;
    margin-bottom: 20px;
}
.alert h3 {
    margin: 0 0 5px;
    font-size: 13px;
    color: #d9822b;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 10px;
}
th {
    background: #1f6fd2;
    color: #fff;
    padding: 6px;
    border: 1px solid #ddd;
}
td {
    border: 1px solid #ddd;
    padding: 5px;
    text-align: center;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

td:first-child { max-width: 70px; }
.descripcion { max-width: 160px; text-align: left; }

tr { page-break-inside: avoid; }

/* ENTREGAS */
.entregas {
    font-size: 9px;
    background: #f9fafb;
    text-align: left;
    padding: 6px;
}

/* TOTALES */
.resumen {
    margin-top: 20px;
    border: 1px solid #1f6fd2;
    background: #f4f9ff;
    padding: 12px;
}
.resumen p {
    margin: 4px 0;
    font-size: 13px;
}
.resumen strong {
    color: #1f2d3d;
}

/* OBSERVACIONES FINALES */
.obs-final {
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px dashed #ccc;
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <img src="{{ public_path('images/SETAS.png') }}">
    <h2>Orden de Trabajo #{{ $orden->id }}</h2>
    <span></span>
</div>

<!-- INFO -->
<div class="info">
    <div>
        <strong>Cliente</strong>
        <span>{{ $orden->cliente->nombre ?? 'N/A' }}</span>
    </div>
    <div>
        <strong>Fecha Entrega</strong>
        <span>{{ $orden->fecha_entrega }}</span>
    </div>
    <div>
        <strong>Asesor Comercial</strong>
        <span>{{ $orden->ordenCompra->user->name ?? 'N/A' }}</span>
    </div>
    <div>
        <strong>Empresa</strong>
        <span>{{ $orden->ordenCompra->empresa->nombre ?? 'N/A' }}</span>
    </div>
</div>

@if($orden->ordenCompra && $orden->ordenCompra->observaciones)
<div class="alert">
    <h3>Observaciones de la Orden de Compra</h3>
    {{ $orden->ordenCompra->observaciones }}
</div>
@endif

<h3>Detalles</h3>

<table>
<thead>
<tr>
    <th>Producto</th>
    <th>Ancho</th>
    <th>Largo</th>
    <th>Cal.</th>
    <th>Cal. Cl</th>
    <th>Descripción</th>
    <th>Emb.</th>
    <th>Kg Req</th>
    <th>Cant</th>
    <th>Env</th>
    <th>Falt</th>
    <th>V. Unit</th>
    <th>Total</th>
</tr>
</thead>
<tbody>
@foreach($detalles as $d)
<tr>
    <td>{{ $d->product->code ?? '-' }}</td>
    <td>{{ $d->ancho_cm }}</td>
    <td>{{ $d->largo_cm }}</td>
    <td>{{ $d->calibre }}</td>
    <td>{{ $d->cliente_clb }}</td>
    <td class="descripcion">{{ $d->descripcion }}</td>
    <td>{{ ucfirst($d->tipo_embalaje) }}</td>
    <td>{{ number_format($d->cantidad_requerida_kg, 2) }}</td>
    <td>{{ $d->cantidad }}</td>
    <td>{{ $d->cantidad_enviada }}</td>
    <td>{{ $d->faltantes }}</td>
    <td>${{ number_format($d->valor_unitario, 2, ',', '.') }}</td>
    <td>${{ number_format($d->valor_total, 2, ',', '.') }}</td>
</tr>

@if($d->entregas->count())
<tr>
    <td colspan="13" class="entregas">
        <strong>Entregas:</strong><br>
        @foreach($d->entregas as $e)
            • {{ $e->cantidad }} — {{ \Carbon\Carbon::parse($e->fecha_entrega)->format('d/m/Y') }} — {{ $e->usuario->name }}<br>
        @endforeach
    </td>
</tr>
@endif
@endforeach
</tbody>
</table>

<!-- RESUMEN -->
<div class="resumen">
    <p><strong>Total Kg:</strong> {{ number_format($totalKg, 2) }} Kg</p>
    <p><strong>Valor Total:</strong> ${{ number_format($valorTotal, 2, ',', '.') }}</p>
</div>

<!-- OBSERVACIONES -->
<div class="obs-final">
    <h3>Observaciones</h3>
    <p>{{ $observaciones ?: 'Sin observaciones adicionales.' }}</p>
</div>

</body>
</html>

