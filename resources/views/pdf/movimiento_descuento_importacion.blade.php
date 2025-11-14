<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Descuento masivo desde Excel</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 6px; }
        th { background: #f0f0f0; }
        .section-title { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<h1>Descuento Masivo de Inventario</h1>

<p><strong>Fecha:</strong> {{ $fecha }}</p>
<p><strong>Usuario:</strong> {{ $usuario->name }}</p>
<p><strong>Empresa:</strong> {{ $empresa->nombre }}</p>
<p><strong>Bodega:</strong> {{ $bodega->nombre }}</p>
<p><strong>Archivo origen:</strong> {{ $movimiento->detalle['archivo_origen'] }}</p>

<hr>

<h3 class="section-title">Resumen de operación</h3>
<table>
    <tr>
        <th>Total de líneas procesadas</th>
        <td>{{ $movimiento->detalle['total_lineas'] }}</td>
    </tr>
    <tr>
        <th>Total descontado (unidades)</th>
        <td>{{ $movimiento->cantidad }}</td>
    </tr>
    <tr>
        <th>Errores detectados</th>
        <td>{{ count($errores) }}</td>
    </tr>
</table>

<h3 class="section-title">Productos procesados</h3>
<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Producto</th>
            <th>Stock antes</th>
            <th>Descontado</th>
            <th>Stock después</th>
        </tr>
    </thead>
    <tbody>
        @foreach($procesados as $p)
        <tr>
            <td>{{ $p['codigo'] }}</td>
            <td>{{ $p['producto_nombre'] }}</td>
            <td>{{ $p['stock_antes'] }}</td>
            <td>{{ $p['cantidad_descontada'] }}</td>
            <td>{{ $p['stock_despues'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if(count($errores) > 0)
<h3 class="section-title">Errores</h3>
<table>
    <thead>
        <tr>
            <th>Fila</th>
            <th>Código</th>
            <th>Error</th>
        </tr>
    </thead>
    <tbody>
        @foreach($errores as $e)
        <tr>
            <td>{{ $e['fila'] }}</td>
            <td>{{ $e['code'] }}</td>
            <td>{{ $e['error'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
