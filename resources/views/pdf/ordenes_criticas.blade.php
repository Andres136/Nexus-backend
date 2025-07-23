<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Órdenes Críticas</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Órdenes Críticas del {{ $fecha }}</h2>

    @foreach ($ordenes as $orden)
        <p><strong>Orden #{{ $orden->id }} - Cliente:</strong> {{ $orden->cliente->nombre ?? 'Sin cliente' }}</p>

        <table>
            <thead>
                <tr>
                    <th>Referencia</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Enviada</th>
                    <th>Faltantes</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orden->detalles as $detalle)
                    @php
                        $enviada = $detalle->cantidad_enviada ?? 0;
                        $faltantes = $detalle->faltantes ?? 0;
                        $estado = 'Programada para hoy';
                        if ($faltantes > 0 && $enviada > 0) {
                            $estado = 'Con faltantes';
                        } elseif ($faltantes > 0 && $enviada == 0) {
                            $estado = \Carbon\Carbon::parse($orden->fecha_entrega)->lt($fecha) ? 'Vencida' : 'Pendiente';
                        } elseif ($faltantes == 0 && $enviada > 0) {
                            $estado = 'Completa';
                        }
                    @endphp
                    <tr>
                      <td>{{ $detalle->largo_cm }} x {{ $detalle->ancho_cm }} cm</td>

                        <td>{{ $detalle->descripcion }}</td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>{{ $enviada }}</td>
                        <td>{{ $faltantes }}</td>
                        <td>{{ $estado }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <br>
    @endforeach
</body>
</html>

