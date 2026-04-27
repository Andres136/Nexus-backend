<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #334155; 
            font-size: 11px; 
            line-height: 1.4;
            margin: 0;
        }
        
        /* Encabezado Estilo Dashboard */
        .header-container { width: 100%; margin-bottom: 25px; }
        .column { display: inline-block; vertical-align: top; width: 49%; }
        
        .logo { height: 50px; margin-bottom: 8px; }
        .invoice-title { 
            text-align: right; 
            color: #1e293b; 
            text-transform: uppercase; 
            font-size: 18px; 
            margin: 0; 
            letter-spacing: 1px;
        }
        .invoice-number { text-align: right; font-size: 13px; color: #64748b; margin-top: 4px; }

        /* Contenedores de Información */
        .info-grid { width: 100%; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; background-color: #fcfcfc; }
        .info-item { display: inline-block; width: 24%; vertical-align: top; }
        .info-label { font-size: 8px; text-transform: uppercase; color: #94a3b8; font-weight: bold; display: block; margin-bottom: 3px; }
        .info-value { font-size: 10px; color: #1e293b; font-weight: 600; }

        /* Tabla de Productos - Estilo Moderno */
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; border-radius: 8px; overflow: hidden; }
        .table thead th { 
            background-color: #f8fafc; 
            color: #475569; 
            text-transform: uppercase; 
            font-size: 9px; 
            padding: 12px 10px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        .table tbody td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .right { text-align: right; }
        .center { text-align: center; }

        /* Sección Inferior: Observaciones y Totales */
        .bottom-section { margin-top: 30px; width: 100%; }
        .observations-box { 
            display: inline-block; 
            width: 60%; 
            vertical-align: top; 
            border: 1px solid #e2e8f0; 
            border-radius: 8px; 
            padding: 10px;
            min-height: 80px;
        }
        
        /* Cuadro de Totales Estilo UI (Fondo Oscuro) */
        .totals-wrapper { 
            display: inline-block; 
            width: 35%; 
            float: right; 
            background-color: #ffffff /* Azul muy oscuro como en tu imagen */
            color: #000000; 
            border-radius: 8px; 
            border: 1px solid #e2e8f0;
            padding: 0;
            overflow: hidden;
        }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 8px 15px; font-size: 11px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .totals-table .label { color: #94a3b8; }
        .totals-table .value { text-align: right; font-weight: bold; }
        .total-row { background-color: rgba(255,255,255,0.05); font-size: 13px !important; }
        .total-row td { border-bottom: none; padding: 12px 15px; }

        .footer-note { margin-top: 40px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="column">
            @if($logoPath)
                <img src="{{ $logoPath }}" class="logo">
            @endif
            <div style="font-weight: bold; font-size: 12px; color: #1e293b;">{{ $factura->empresa->nombre }}</div>
        </div>
        <div class="column">
            <h1 class="invoice-title">Factura de Compra</h1>
            <div class="invoice-number">No. Registro: <strong>{{ $factura->numero_factura }}</strong></div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Proveedor</span>
            <span class="info-value">{{ $factura->proveedor->nombre }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Fecha Emisión</span>
            <span class="info-value">{{ $factura->fecha_emision }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Vencimiento</span>
            <span class="info-value">{{ $factura->fecha_vencimiento ?? 'N/A' }}</span>
        </div>
        <div class="info-item" style="width: 20%;">
            <span class="info-label">Forma de Pago</span>
            <span class="info-value">{{ $factura->forma_pago ?? 'CRÉDITO' }}</span>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Descripción del Producto</th>
                <th class="center" width="50">Cant.</th>
                <th class="right" width="100">Precio Unit.</th>
                <th class="right" width="100">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($factura->detalles as $det)
            <tr>
                <td>{{ $det->producto->name?? 'N/A' }}</td>
                <td class="center">{{ $det->cantidad }}</td>
                <td class="center">{{ number_format($det->precio_unitario, 2) }}</td>
                <td class="center">{{ number_format($det->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bottom-section">
        <div class="observations-box">
            <span class="info-label">Observaciones</span>
            <div style="font-size: 10px; color: #475569;">
                {{ $factura->observaciones ?? 'Sin observaciones adicionales.' }}
            </div>
        </div>

        <div class="totals-wrapper">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">${{ number_format($factura->subtotal, 2) }}</td>
                </tr>
                
                {{-- Impuestos Generales de la Factura --}}
          @php
    $impuestosAgrupados = [];
@endphp

{{-- 🔥 IMPUESTOS DESDE DETALLES --}}
@foreach($factura->detalles as $det)
    @foreach($det->impuestos as $imp)
        @php
            $nombre = $imp->nombre;
            $monto = $imp->pivot->monto ?? 0;

            $impuestosAgrupados[$nombre] = ($impuestosAgrupados[$nombre] ?? 0) + $monto;
        @endphp
    @endforeach
@endforeach

{{-- 🔥 IMPUESTOS GENERALES (SI EXISTEN) --}}
@foreach($factura->impuestos as $imp)
    @php
        $nombre = $imp->nombre;
        $monto = $imp->pivot->monto ?? 0;

        $impuestosAgrupados[$nombre] = ($impuestosAgrupados[$nombre] ?? 0) + $monto;
    @endphp
@endforeach

{{-- 🔥 RENDER FINAL --}}
@foreach($impuestosAgrupados as $nombre => $monto)
<tr>
    <td class="label">{{ $nombre }}</td>
    <td class="value">${{ number_format($monto, 2) }}</td>
</tr>
@endforeach

                <tr class="total-row">
                    <td style="font-weight: normal;">Total</td>
                    <td class="value">${{ number_format($factura->total, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div style="clear: both;"></div>

    <div class="footer-note">
        Generado por el sistema de gestión SETASPLAST. <br>
        Este documento es una constancia de compra para fines contables internos.
    </div>

</body>
</html>