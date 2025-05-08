<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización #{{ $cotizacion->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .logo {
            width: 140px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="width: 40%;">
            <img src="{{ $logo }}" class="logo" alt="Logo">
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div style="text-align: left;">
                
            <p style="margin: 0;">
                <strong>Empresa:</strong>
                {{ $cotizacion->empresa === 'global' ? 'GLOBAL BUSINESS JS GROUP S.A.S BIC' : 'SETASPLAST S.A.S BIC' }}
            </p>
            </div>
            <div style="text-align: right;">
                <strong>Fecha:</strong> {{ $cotizacion->created_at->format('d/m/Y') }}
            </div>
        </div>
        
    </div>
    
    <hr style="margin: 20px 0;">
    
    <p><strong>Cliente:</strong> {{ $cotizacion->cliente->nombre ?? 'N/A' }}</p>
    <p><strong>Asesor:</strong> {{ $cotizacion->user->name ?? 'N/A' }}</p>
    



    <div class="section-title">Detalles:</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Descripción</th>
                <th>Ancho</th>
                <th>Largo</th>
                <th>Calibre</th>
                <th># Bolsas</th>
                <th>Cantidad</th>
                <th>Precio Total</th>
                <th>Valor Unitario</th>
                <th>Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cotizacion->detalles as $item)
                <tr>
                    <td>{{ $item->item }}</td>
                    <td>{{ $item->descripcion }}</td>
                    <td>{{ $item->ancho_cm }}</td>
                    <td>{{ $item->largo_cm }}</td>
                    <td>{{ $item->calibre }}</td>
                    <td>{{ $item->numero_bolsas }}</td>
                    <td>{{ $item->cantidad }}</td>
                    <td class="text-right">$ {{ number_format($item->precio_total, 0, ',', '.') }}</td>
                    <td class="text-right">$ {{ number_format($item->valor_unitario, 0, ',', '.') }}</td>
                    <td class="text-right">$ {{ number_format($item->valor_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Observaciones:</div>
    <p>{{ $cotizacion->observaciones ?? 'Sin observaciones' }}</p>

    <div class="text-right" style="margin-top: 20px;">
        <strong>Valor Total: ${{ number_format($cotizacion->valor_total, 0, ',', '.') }}</strong>
    </div>
</body>
</html>
