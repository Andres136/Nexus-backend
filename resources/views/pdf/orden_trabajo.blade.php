<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Orden de Trabajo</title>

<style>
@page {
    size: A4;
    margin: 10mm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10px;
    color: #333;
    margin: 0;
}

/* HEADER */
.header {
    display: flex;
    align-items: center;
    border-bottom: 2px solid #1f6fd2;
    padding-bottom: 4px;
    margin-bottom: 10px;
}
.header img {
    height: 32px;
}
.header h2 {
    flex: 1;
    text-align: center;
    margin: 0;
    font-size: 14px;
    color: #1f2d3d;
}

/* INFO GRID */
.info {
    display: table;
    width: 100%;
    margin-bottom: 8px;
}
.info div {
    display: table-cell;
    padding: 3px 5px;
    vertical-align: top;
}
.info strong {
    display: block;
    font-size: 9px;
    color: #6b7280;
}
.info span {
    font-size: 10px;
    font-weight: 600;
}

/* OBSERVACIONES OC */
.alert {
    border: 1px solid #f0ad4e;
    background: #fff7e6;
    padding: 6px;
    margin-bottom: 10px;
}
.alert h3 {
    margin: 0 0 3px;
    font-size: 11px;
    color: #d9822b;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 9px;
}

tbody {
    page-break-inside: avoid;
}
th {
    background: #1f6fd2;
    color: #fff;
    padding: 3px;
    border: 1px solid #ddd;
}

td {
    border: 1px solid #ddd;
    padding: 3px;
    text-align: center;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    page-break-inside: avoid;
}

td:first-child { max-width: 60px; }
.descripcion { max-width: 120px; text-align: left; }



/* ENTREGAS */
.entregas {
    font-size: 8px;
    background: #f9fafb;
    text-align: left;
    padding: 3px;
}

/* TOTALES */
.resumen {
    margin-top: 10px;
    border: 1px solid #1f6fd2;
    background: #f4f9ff;
    padding: 6px;
}
.resumen p {
    margin: 2px 0;
    font-size: 10px;
}
.resumen strong {
    color: #1f2d3d;
}

/* OBSERVACIONES FINALES */
.obs-final {
    margin-top: 10px;
    padding-top: 5px;
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
        <span>{{ $orden->ordenCompra->fecha_entrega ?? 'N/A' }}</span>
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
<!-- Dirección -->
@if($orden->ordenCompra && $orden->ordenCompra->ubicacion_entrega)
<div class="alert">
    <h3>Dirección de Entrega</h3>
    {{ $orden->ordenCompra->ubicacion_entrega }}
</div>
@endif


<h3>Detalles</h3>

<table>
<thead>
<tr>
    <th>Producto</th>
    <th>Referencia</th>

    <th>Cl.Clb</th>
    <th>Descripción</th>
    <th>Emb.</th>
    <th>Kg Req</th>
    <th>Cant</th>
    <th>Alistamiento</th>
  
<th>Env / Falt</th>
    <th>V. Unit</th>
    <th>Total</th>
</tr>
</thead>
@foreach($detalles as $d)
<tbody style="page-break-inside: avoid;">

<tr>
    <td>{{ $d->product->code ?? '-' }}</td>
    <td style="text-align:left;">
        <div><strong>Ancho:</strong> {{ number_format($d->ancho_cm, 0) }}</div>
        <div><strong>Largo:</strong> {{ number_format($d->largo_cm, 0) }}</div>
        <div><strong>Calibre:</strong> {{ number_format($d->calibre, 0) }}</div>
    </td>
    <td>{{ $d->cliente_clb }}</td>
    <td class="descripcion">{{ $d->descripcion }}</td>
    <td>{{ ucfirst($d->tipo_embalaje) }}</td>
    <td>{{ number_format($d->cantidad_requerida_kg, 2) }}</td>
    <td>{{ $d->cantidad }}</td>
    <td>
        {{ $alistamientos->where('orden_compra_detalle_id', $d->id)->count() }} alistamientos
    </td>
    <td>
        <div>
            <span style="color:green;">
                {{ $d->cantidad_enviada }}
            </span>
            /
            <span style="color:{{ $d->faltantes > 0 ? 'red' : 'green' }};">
                {{ $d->faltantes }}
            </span>
        </div>
    </td>
    <td>${{ number_format($d->valor_unitario, 2, ',', '.') }}</td>
    <td>${{ number_format($d->valor_total, 2, ',', '.') }}</td>
</tr>

{{-- FILA EXPANDIDA PARA ALISTAMIENTOS --}}
@php
    $alistamientosDetalle = $alistamientos->where('orden_compra_detalle_id', $d->id);
    $agrupado = $alistamientosDetalle->groupBy(fn($i) => $i->producto_id . '-' . $i->bodega_id);
@endphp

@if($agrupado->count())
<tr>
    <td colspan="11" style="font-size:7px; text-align:left; background:#f9fafb;">
        <strong>Alistamientos:</strong>
        @foreach($agrupado as $grupo)
            @php
                $item = $grupo->first();
                $total = $grupo->sum('cantidad');
            @endphp
            <div>
                {{ $item->producto->name }}
                ({{ $item->bodega->nombre }}
                @if($item->bodega->sede)
                    - {{ $item->bodega->sede->nombre }}
                @endif
                )
                <span style="color:green;">{{ number_format($total, 2) }}</span>
                @if($item->observacion)
                    <span style="color:#d9822b;"> | {{ $item->observacion }}</span>
                @endif
            </div>
        @endforeach
    </td>
</tr>
@endif

{{-- ENTREGAS --}}
@if($d->entregas->count())
<tr>
    <td colspan="11" class="entregas">
        <strong>Entregas:</strong><br>
        @foreach($d->entregas as $e)
            • {{ $e->cantidad }} — {{ \Carbon\Carbon::parse($e->fecha_entrega)->format('d/m/Y') }} — {{ $e->usuario->name }}<br>
        @endforeach
    </td>
</tr>
@endif

</tbody>
@endforeach
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

