<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra #{{ $orden->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .logo {
            width: 120px;
        }
        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        .info {
            margin-bottom: 15px;
        }
        table {
            font-size: 9px; /* 👈 Tamaño de tabla más pequeño */
        table-layout: fixed;
        width: 100%;
        border-collapse: collapse;
        word-wrap: break-word;

        }
        th {
            background-color: #eee;
        }
        th, td {
            border: 1px solid #999;
            padding: 6px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="encabezado">
        <img src="{{ public_path('images/SETAS.png') }}" class="logo" alt="Logo SETAS">
        <h2>Orden de Compra #{{ $orden->id }}</h2>
    </div>

    <div class="info">
        <p><strong>Cliente:</strong> {{ $orden->cliente->nombre }}</p>
        <p><strong>Fecha de Entrega:</strong> {{ $orden->fecha_entrega }}</p>
        <p><strong>Ubicación de Entrega:</strong> {{ $orden->ubicacion_entrega }}</p>
        <p><strong>Observaciones:</strong> {{ $orden->observaciones }}</p>
        <p><strong>Orden creada por:</strong> {{ $orden->usuario->name }}</p>

     
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Ancho</th>
                <th>Largo</th>
                <th>Calibre</th>
                <th>Peso Bolsa</th>
                <th>Numero de Bolsas</th>
                <th>Cliente CLB</th>
                <th>Cantidad (Kg)</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Valor Unitario</th>
                <th>Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orden->detalles as $index => $detalle)
                <tr>
                    <td>{{ $detalle->observaciones }}</td>
                    <td>{{ $detalle->ancho_cm }}</td>
                    <td>{{ $detalle->largo_cm}}</td>
                    <td>{{ $detalle->calibre }}</td>
                    <td>{{ $detalle->peso_bolsa }}</td>
                    <td>{{ $detalle->numero_bolsas }}</td>
                    <td>{{ $detalle->cliente_clb }}</td>
                    <td>{{ $detalle->cantidad_requerida_kg }}</td>
                    <td>{{ $detalle->descripcion }}</td>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>${{ number_format($detalle->valor_unitario, 2, ',', '.') }}</td>
                    <td>${{ number_format($detalle->valor_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 15px;"><strong>Valor Total Orden:</strong> ${{ number_format($orden->valor_total, 0, ',', '.') }}</p>

</body>
</html>
