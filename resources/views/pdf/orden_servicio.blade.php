<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orden de Servicio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            padding: 20px;
        }

        .header {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            width: 120px;
            vertical-align: middle;
        }

        .logo {
            height: 60px;
            max-width: 100px;
        }

        .title-section {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }

        .title-section h1 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .orden-number {
            font-size: 14px;
            color: #e74c3c;
            font-weight: bold;
        }

        .info-container {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .info-box:last-child {
            padding-right: 0;
            padding-left: 15px;
        }

        .info-box h3 {
            font-size: 11px;
            color: #fff;
            background: #2c3e50;
            padding: 6px 10px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-row {
            padding: 4px 0;
            border-bottom: 1px dotted #ddd;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 100px;
        }

        .info-value {
            color: #333;
        }

        .section-title {
            background: #34495e;
            color: #fff;
            padding: 8px 12px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #ecf0f1;
            color: #2c3e50;
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #bdc3c7;
        }

        tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: top;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        tbody tr:hover {
            background: #f5f5f5;
        }

        .item-number {
            text-align: center;
            font-weight: bold;
            color: #7f8c8d;
        }

        .product-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 3px;
        }

        .product-desc {
            font-size: 9px;
            color: #7f8c8d;
            font-style: italic;
        }

        .cantidad {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            color: #2c3e50;
        }

        .process-box {
            background: #f8f9fa;
            border-left: 3px solid #3498db;
            padding: 6px 8px;
            margin-bottom: 6px;
            font-size: 10px;
        }

        .process-box:last-child {
            margin-bottom: 0;
        }

        .process-name {
            font-weight: bold;
            color: #2980b9;
            margin-bottom: 2px;
        }

        .process-obs {
            color: #555;
        }

        .footer {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 30px;
        }

        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding-top: 40px;
        }

        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 0 auto 5px;
        }

        .signature-label {
            font-size: 10px;
            color: #555;
        }

        .date-generated {
            text-align: right;
            font-size: 9px;
            color: #999;
            margin-top: 20px;
        }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-content">
        <div class="logo-section">
            @php
                $logoPath = public_path('storage/' . $ordenServicio->empresa->logo);
                $logoBase64 = null;

                if($ordenServicio->empresa->logo && file_exists($logoPath)){
                    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                }
            @endphp

            @if($logoBase64)
                <img src="{{ $logoBase64 }}" class="logo">
            @endif
        </div>
        <div class="title-section">
            <h1>Orden de Servicio</h1>
            <span class="orden-number">N° {{ $ordenServicio->numero_os }}</span>
        </div>
        <div class="logo-section"></div>
    </div>
</div>

{{-- Información General --}}
<div class="info-container">
    <div class="info-box">
        <h3>Datos de la Empresa</h3>
        <div class="info-row">
            <span class="info-label">Empresa:</span>
            <span class="info-value">{{ $ordenServicio->empresa->nombre ?? '' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">NIT:</span>
            <span class="info-value">{{ $ordenServicio->empresa->nit ?? '' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Dirección:</span>
            <span class="info-value">{{ $ordenServicio->empresa->direccion ?? '' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Teléfono:</span>
            <span class="info-value">{{ $ordenServicio->empresa->telefono ?? '' }}</span>
        </div>
    </div>
    <div class="info-box">
        <h3>Datos del Proveedor</h3>
        <div class="info-row">
            <span class="info-label">Proveedor:</span>
            <span class="info-value">{{ $ordenServicio->proveedor->nombre ?? '' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Contacto:</span>
            <span class="info-value">{{ $ordenServicio->proveedor->telefono ?? '' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ $ordenServicio->fecha }}</span>
        </div>
          <div class="info-row">
            <span class="info-label">Observaciones:</span>
            <span class="info-value">{{ $ordenServicio->observaciones ?? '' }}</span>
        </div>
    </div>
</div>

{{-- Detalles --}}
<h3 class="section-title">Detalle de la Orden</h3>

<table>
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 15%;">Orden Compra</th>
            <th style="width: 25%;">Producto</th>
            <th style="width: 10%;">Cantidad</th>
            <th style="width: 45%;">Procesos Requeridos</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ordenServicio->detalles as $detalle)
        <tr>
            <td class="item-number">{{ $loop->iteration }}</td>

            <td>
                {{ $detalle->ordenCompraDetalle->orden->numero_orden ?? '' }}
            </td>

            <td>
                <div class="product-name">
                    {{ $detalle->ordenCompraDetalle->producto->name ?? '' }}
                </div>
                <div class="product-desc">
                    {{ $detalle->ordenCompraDetalle->producto->description ?? '' }}
                </div>
            </td>

            <td class="cantidad">
                {{ $detalle->cantidad }}
            </td>

            <td>
                @foreach($detalle->ordenCompraDetalle->observaciones as $obs)
                    <div class="process-box">
                        <div class="process-name">
                            {{ $obs->proceso->nombre ?? 'N/A' }}
                        </div>
                        <div class="process-obs">
                            {{ $obs->observacion }}
                        </div>
                    </div>
                @endforeach
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Footer --}}
<div class="footer">
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Elaborado por</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Recibido por</div>
        </div>
    </div>

    <div class="date-generated">
        Documento generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

</body>
</html>