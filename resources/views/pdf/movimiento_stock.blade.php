@php
    use Carbon\Carbon;
    $fecha = Carbon::now()->format('d/m/Y H:i');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimiento de Stock - Orden de Trabajo #{{ $movimiento->orden_trabajo_id ?? 'N/A' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 20px;
        }
        h1, h2, h3 {
            color: #0056b3;
            margin-bottom: 0;
        }
        h1 {
            font-size: 18px;
            text-align: center;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 5px;
        }
        h2 { font-size: 15px; margin-top: 20px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }
        th {
            background: #f2f2f2;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-red { color: #c0392b; }
        .text-green { color: #27ae60; }
        .text-blue { color: #2980b9; }
        .bg-gray { background: #f9f9f9; }
        .section { margin-top: 25px; }
        .small { font-size: 11px; color: #555; }
        .bordeado { border: 1px solid #ccc; padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Movimiento de Stock Consolidado</h1>

    <table>
        <tr>
            <td><strong>Orden de Trabajo:</strong> #{{ $movimiento->orden_trabajo_id ?? 'N/A' }}</td>
            <td><strong>Orden de Compra:</strong> #{{ $movimiento->orden_compra_id ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Tipo de Movimiento:</strong> {{ strtoupper($movimiento->tipo ?? 'Descuento') }}</td>
            <td><strong>Usuario:</strong> {{ $usuario->name ?? 'Desconocido' }}</td>
        </tr>
        <tr>
            <td><strong>Fecha:</strong> {{ $fecha }}</td>
            <td><strong>Cantidad Total Descontada:</strong> {{ number_format($movimiento->cantidad ?? 0, 2, ',', '.') }}</td>
        </tr>
    </table>

    {{-- ===========================
         DETALLE DE PRODUCTOS
    ============================ --}}
    <div class="section">
        <h2>1. Detalle de Productos Descontados</h2>
        @php
            $bodegasPorProducto = collect($detalleOriginal ?? [])->groupBy('producto_id');
        @endphp

        @forelse($bodegasPorProducto as $productoId => $bodegas)
            @php
                $nombreProducto = $bodegas->first()['producto_nombre'] ?? "Producto #{$productoId}";
                $totalProducto = $bodegas->sum('cantidad_descontada');
            @endphp

            <h3 class="text-blue">{{ $nombreProducto }}</h3>

            <table>
                <thead>
                    <tr>
                        <th>Bodega</th>
                        <th>Cantidad Descontada</th>
                        <th>Stock Restante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bodegas as $b)
                        <tr>
                            <td>{{ $b['bodega_nombre'] ?? '—' }}</td>
                            <td class="text-right">{{ number_format($b['cantidad_descontada'] ?? 0, 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($b['stock_restante'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray">
                        <td><strong>Total Producto</strong></td>
                        <td class="text-right" colspan="2">
                            <strong>{{ number_format($totalProducto, 2, ',', '.') }}</strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        @empty
            <p class="small text-center text-red">No se registraron descuentos de productos.</p>
        @endforelse
    </div>

    {{-- ===========================
         EQUIVALENTES
    ============================ --}}
    <div class="section">
        <h2>2. Productos Equivalentes Utilizados</h2>
        @if(!empty($equivalentesResp))
            @foreach($equivalentesResp as $eq)
                <div class="bordeado" style="margin-bottom: 10px;">
                    <p><strong>{{ $eq['producto_nombre'] ?? 'Producto equivalente' }}</strong>
                        <span class="small">({{ $eq['razon'] ?? 'Uso de equivalente' }})</span>
                    </p>
                    <table>
                        <thead>
                            <tr>
                                <th>Bodega</th>
                                <th>Cantidad Descontada</th>
                                <th>Stock Restante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eq['bodegas'] ?? [] as $b)
                                <tr>
                                    <td>{{ $b['bodega_nombre'] ?? '—' }}</td>
                                    <td class="text-right">
                                        {{ isset($b['cantidad_descontada'])
                                            ? number_format($b['cantidad_descontada'], 2, ',', '.')
                                            : '—' }}
                                    </td>
                                    <td class="text-right">
                                        {{ isset($b['stock_restante'])
                                            ? number_format($b['stock_restante'], 2, ',', '.')
                                            : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @else
            <p class="small text-center text-gray">No se utilizaron productos equivalentes.</p>
        @endif
    </div>

    {{-- ===========================
         ERRORES Y OBSERVACIONES
    ============================ --}}
    <div class="section">
        <h2>3. Observaciones y Errores Detectados</h2>
        @php $erroresData = $detalleActual['errores'] ?? $errores ?? []; @endphp
        @if(!empty($erroresData))
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Bodega</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($erroresData as $err)
                        <tr>
                            <td>#{{ $err['producto_id'] ?? 'N/A' }}</td>
                            <td>{{ $err['bodega_nombre'] ?? '—' }}</td>
                            <td class="text-red">{{ $err['mensaje'] ?? $err['error'] ?? 'Error desconocido' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="small text-green text-center">No se presentaron errores durante el proceso.</p>
        @endif
    </div>

    {{-- ===========================
         FIRMAS
    ============================ --}}
    <div class="section" style="margin-top:40px;">
        <table>
            <tr>
                <td class="text-center">
                    <p>_____________________________</p>
                    <p class="small">Responsable del Descuento</p>
                    <p class="small">{{ $usuario->name ?? '—' }}</p>
                </td>
                <td class="text-center">
                    <p>_____________________________</p>
                    <p class="small">Verificado por</p>
                    <p class="small">_________________________</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
