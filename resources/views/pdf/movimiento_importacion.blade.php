<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimiento de Inventario - Importación Excel</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1, h2, h3 { color: #208040; margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h2>Resumen de Carga Masiva de Inventario</h2>
    <p><strong>Fecha:</strong> {{ $fecha }}</p>
    <p><strong>Usuario:</strong> {{ $usuario->name }}</p>
    <p><strong>Empresa :</strong> {{ $movimiento->detalle['empresa_nombre'] ?? 'N/A' }}</p>
    <p><strong>Bodega :</strong> {{ $movimiento->detalle['bodega_nombre'] ?? 'N/A' }}</p>
    <p><strong>Total de registros procesados:</strong> {{ $movimiento->detalle['total_registros'] }}</p>

    <h3>Inventarios actualizados</h3>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Producto</th>
                <th>Bodega</th>
                <th>Empresa</th>
                <th>Stock</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventarios as $inv)
                <tr>
                    <td>{{ $inv->producto->code ?? 'N/A' }}</td>
                    <td>{{ $inv->producto->name ?? 'N/A' }}</td>
                    <td>{{ $inv->bodega->nombre ?? 'N/A' }}</td>
                    <td>{{ $inv->empresa->nombre ?? 'N/A' }}</td>
                    <td>{{ $inv->stock }}</td>
                    <td>{{ $inv->wasRecentlyCreated ? 'Nuevo' : 'Actualizado' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@if (!empty($errores) && count($errores) > 0)
    <h3>Errores Detectados</h3>
    <ul>
        @foreach ($errores as $indice => $error)
            @if (is_array($error))
                <li>
                    <strong>{{ is_numeric($indice) ? 'Fila '.$indice : ucfirst($indice) }}:</strong>
                    <ul>
                        @foreach ($error as $detalle)
                            <li>{{ $detalle }}</li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li>{{ $error }}</li>
            @endif
        @endforeach
    </ul>
@endif

</body>
</html>
