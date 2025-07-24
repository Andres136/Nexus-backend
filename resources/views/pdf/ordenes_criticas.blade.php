<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Órdenes Críticas</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h2>Órdenes Críticas del {{ $fecha }}</h2>


@foreach (['vencidas' => '🔴 Órdenes Vencidas', 'faltantes' => '🟡 Órdenes con Faltantes', 'hoy' => '🟢 Órdenes Entregar Hoy'] as $tipo => $titulo)
    @php $grupo = $$tipo; @endphp
    @if($grupo->isNotEmpty())
        <h3>{{ $titulo }}</h3>
        @foreach ($grupo as $orden)
            <p><strong>OC #{{ $orden->id }}</strong> - Cliente: {{ $orden->cliente->nombre ?? 'N/A' }}</p>
            <table>
                <thead>
                    <tr>
                        <th>Referencia</th>
                        <th>Descripción</th>
                        <th>Cant.</th>
                        <th>Enviada</th>
                        <th>Faltantes</th>
                        <th>Largo x Ancho</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orden->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->cliente_clb ?? 'N/A' }}</td>
                            <td>{{ $detalle->descripcion }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>{{ $detalle->cantidad_enviada ?? 0 }}</td>
                            <td>{{ $detalle->faltantes ?? 0 }}</td>
                            <td>{{ $detalle->largo_cm }} x {{ $detalle->ancho_cm }} cm</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
@endforeach

</body>
</html>


