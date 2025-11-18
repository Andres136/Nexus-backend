<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Trabajo</title>
    <style>
        body { 
            font-family: sans-serif; 
            font-size: 12px; 
            color: #333; 
            margin: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header img {
            width: 70px;
            height: auto;
        }

        .header h2 {
            flex: 1;
            text-align: center;
            color: #2c3e50;
            font-size: 20px;
            margin: 0;
        }

        h3 { 
            margin-top: 20px; 
            margin-bottom: 10px; 
            color: #2c3e50;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
            font-size: 11px; 
        }

        th, td { 
            border: 1px solid #ddd; 
            padding: 6px; 
        }

        th { 
            background: #3498db; 
            color: white; 
            text-align: center; 
        }

        td { 
            text-align: center; 
        }

        td:first-child, .descripcion { 
            text-align: left; 
        }

        .totales { 
            margin-top: 20px; 
            padding: 12px; 
            border: 1px solid #3498db;
            border-radius: 6px;
            background: #f4f9ff;
        }

        .totales p {
            margin: 5px 0;
            font-size: 13px;
        }

        .totales strong {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <img src="{{ public_path('images/SETAS.png') }}" alt="SETAS">
        <h2>Orden de Trabajo #{{ $orden->id }}</h2>
        <span></span> <!-- placeholder para centrar el título -->
    </div>

    <p><strong>Cliente:</strong> {{ $orden->cliente->nombre ?? 'N/A' }}</p>
    <p><strong>Fecha de entrega:</strong> {{ $orden->fecha_entrega }}</p>
    <p><strong>Generado por:</strong> {{ $orden->user->name ?? 'N/A' }}</p>

    <h3>Detalles</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Ancho</th>
                <th>Largo</th>
                <th>Calibre</th>
                <th>Calibre Cl</th>
                <th>Descripción</th>
                <th>Kg Req.</th>
                <th>Cantidad</th>
                <th>Enviada</th>
                <th>Faltantes</th>
                <th>Valor Unit.</th>
                <th>Valor Total</th>
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
                    <td>{{ number_format($d->cantidad_requerida_kg, 2) }}</td>
                    <td>{{ $d->cantidad }}</td>
                    <td>{{ $d->cantidad_enviada }}</td>
                    <td>{{ $d->faltantes }}</td>
                    <td>${{ number_format($d->valor_unitario, 2, ',', '.') }}</td>
                    <td>${{ number_format($d->valor_total, 2, ',', '.') }}</td>
                </tr>
                @if($d->entregas->count())
                    <tr>
                        <td colspan="11">
                            <strong>Entregas:</strong><br>
                            @foreach($d->entregas as $e)
                                - Cantidad: {{ $e->cantidad }} |
                                Fecha: {{ \Carbon\Carbon::parse($e->fecha_entrega)->format('d/m/Y') }} |
                                Usuario: {{ $e->usuario->name }}
                                <br>
                            @endforeach
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totales">
        <p><strong>Total Kg:</strong> {{ number_format($totalKg, 2) }} Kg</p>
        <p><strong>Valor Total:</strong> ${{ number_format($valorTotal, 2, ',', '.') }}</p>
    </div>

    <h3>Observaciones</h3>
    <p>{{ $observaciones ?: 'Sin observaciones adicionales.' }}</p>
</body>
</html>
