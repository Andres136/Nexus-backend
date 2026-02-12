<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ítems Pendientes</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h2>Ítems Pendientes por Entregar</h2>
<p style="text-align: right;">Generado: {{ $generado }}</p>

<table>
    <thead>
        <tr>
            <th>OC</th>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Item</th>
            <th>Descripción</th>
            <th>Solicitado</th>
            <th>Entregado</th>
            <th>Pendiente</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item['numero_orden'] }}</td>
                <td>{{ \Carbon\Carbon::parse($item['fecha_orden'])->format('Y-m-d') }}</td>
                <td>{{ $item['proveedor'] }}</td>
                <td>{{ $item['item'] }}</td>
                <td>{{ $item['descripcion'] }}</td>
                <td>{{ $item['cantidad_solicitada'] }}</td>
                <td>{{ $item['cantidad_entregada'] }}</td>
                <td>{{ $item['pendiente'] }}</td>
                <td>
                    @foreach ($item['observaciones'] as $observacion)
                        <div>
                            <strong>Fecha:</strong> {{ $observacion['fecha'] }} <br>
                            <strong>Estado:</strong> {{ $observacion['estado'] }} <br>
                            <strong>Observación:</strong> {{ $observacion['observacion'] }} <br>
                            <strong>Usuario:</strong> {{ $observacion['usuario'] }} <br>
                            <strong>Proceso:</strong> {{ $observacion['proceso'] }} <br>
                            <strong>Proveedor:</strong> {{ $observacion['proveedor'] }} <br>
                        </div>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
