<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Traslado {{ $traslado->codigo }}</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .header {
            border-bottom: 2px solid #333;
            margin-bottom: 15px;
        }

        .header table {
            width: 100%;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
        }

        .box {
            border: 1px solid #333;
            padding: 8px;
            margin-bottom: 10px;
        }

        .box-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #333;
            padding: 6px;
            font-size: 11px;
        }

        table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .firma {
            height: 60px;
        }

        .footer {
            font-size: 10px;
            margin-top: 20px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
    </style>
</head>

<body>

{{-- ================= ENCABEZADO ================= --}}
<div class="header">
    <table>
        <tr>
            <td>
                <h1>TRASLADO INTERNO DE BODEGA</h1>
                <strong>Código:</strong> {{ $traslado->codigo }}<br>
                <strong>Fecha:</strong> {{ $fecha }}
            </td>
            <td class="text-right">
                <strong>Estado:</strong><br>
                {{ $traslado->estado }}
            </td>
        </tr>
    </table>
</div>

{{-- ================= DATOS GENERALES ================= --}}
<div class="box">
    <div class="box-title">Datos del Traslado</div>
    <table>
        <tr>
            <td><strong>Bodega Origen:</strong></td>
            <td>- {{ $traslado->bodegaOrigen->nombre ?? '—' }}</td>
            <td><strong>Bodega Destino:</strong></td>
            <td>+ {{ $traslado->bodegaDestino->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Creado por:</strong></td>
            <td>{{ $traslado->creador->name ?? '—' }}</td>
            <td><strong>Fecha creación:</strong></td>
            <td>{{ optional($traslado->created_at)->format('d/m/Y') }}</td>
        </tr>
    </table>
</div>

{{-- ================= DETALLE DE PRODUCTOS ================= --}}
<div class="box">
    <div class="box-title">Detalle de Productos</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th class="text-center">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalle as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item['producto'] }}</td>
                    <td class="text-center">{{ $item['cantidad'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ================= APROBACIONES ================= --}}
<div class="box">
    <div class="box-title">Aprobaciones</div>

    <table>
        <tr>
            <td><strong>Aprobado por Bodega:</strong></td>
            <td>{{ optional($traslado->aprobadorBodega)->name ?? 'Pendiente' }}</td>
            <td><strong>Fecha:</strong></td>
            <td>{{ optional($traslado->fecha_despacho)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Aprobado por Inventario:</strong></td>
            <td>{{ optional($traslado->aprobadorInventario)->name ?? 'Pendiente' }}</td>
            <td><strong>Fecha:</strong></td>
            <td>{{ optional($traslado->fecha_recepcion)->format('d/m/Y ') ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- ================= FIRMAS ================= --}}
<div class="box">
    <div class="box-title">Firmas</div>

    <table>
        <tr>
            <td class="firma">
                <strong>Responsable Bodega</strong><br><br>
                ____________________________
            </td>
            <td class="firma">
                <strong>Responsable Inventario</strong><br><br>
                ____________________________
            </td>
        </tr>
    </table>
</div>

{{-- ================= PIE ================= --}}
<div class="footer">
    Documento generado automáticamente por el sistema.<br>
    Código de referencia: {{ $traslado->codigo }} |
    Usuario emisor: {{ $usuario->name ?? 'Sistema' }}
</div>

</body>
</html>
