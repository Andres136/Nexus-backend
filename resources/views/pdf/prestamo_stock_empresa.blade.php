<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Préstamo de Stock entre Empresas - Movimiento {{ str_pad($movimiento->id ?? 0, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 25px; }
        h1 { color: #b45309; text-align: center; border-bottom: 2px solid #b45309; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: center; }
        th { background-color: #fef3c7; }
        .info p { margin: 3px 0; }
        .footer { text-align: center; font-size: 11px; color: #777; margin-top: 40px; border-top: 1px solid #ccc; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>Préstamo de Stock entre Empresas - Movimiento #{{ $movimiento->id }}</h1>

    <div class="info">
        <p><strong>Tipo de movimiento:</strong> {{ str_replace('_', ' ', $movimiento->tipo) }}</p>
        <p><strong>Usuario:</strong> {{ $movimiento->usuario->name ?? 'No registrado' }}</p>
        @if($movimiento->orden_trabajo_id)
            <p><strong>Orden de Trabajo:</strong> #{{ $movimiento->orden_trabajo_id }}</p>
        @endif
        @if($movimiento->ordenCompra)
            <p><strong>Orden de Compra:</strong> #{{ $movimiento->ordenCompra->id }} {{ $movimiento->ordenCompra->orden_compra_cliente ? '(' . $movimiento->ordenCompra->orden_compra_cliente . ')' : '' }}</p>
        @endif
        <p><strong>Fecha:</strong> {{ $movimiento->created_at->format('Y-m-d H:i') }}</p>
    </div>

    <h3>Items prestados</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Bodega</th>
                <th>Empresa prestamista</th>
                <th>Empresa prestataria</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->producto->name ?? 'ID ' . $item->producto_id }}</td>
                    <td>{{ $item->bodega->nombre ?? 'ID ' . $item->bodega_id }}</td>
                    <td>{{ $item->empresaPrestamista->nombre ?? '—' }}</td>
                    <td>{{ $item->empresaPrestataria->nombre ?? '—' }}</td>
                    <td>{{ number_format($item->cantidad, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"><strong>Total</strong></td>
                <td><strong>{{ number_format($items->sum('cantidad'), 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Documento generado automáticamente por SIG-SetasPlast BIC<br>
        © {{ date('Y') }} SetasPlast S.A.S BIC
    </div>
</body>
</html>
