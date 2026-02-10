<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimiento de Stock - OT {{ str_pad($orden->id ?? 0, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 25px; }
        h1 { color: #198754; text-align: center; border-bottom: 2px solid #198754; padding-bottom: 8px; }
        h2 { color: #198754; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: center; }
        th { background-color: #f2f2f2; }
        .footer { text-align: center; font-size: 11px; color: #777; margin-top: 40px; border-top: 1px solid #ccc; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>Movimiento de Stock - OT {{ str_pad($orden->id ?? 0, 4, '0', STR_PAD_LEFT) }}</h1>
    <p><strong>Cliente:</strong> {{ $orden->cliente->nombre ?? 'No registrado' }}</p>
    <p><strong>Usuario:</strong> {{ $usuario->name }}</p>
    <p><strong>Fecha:</strong> {{ $fecha }}</p>

    @foreach($resultados as $res)
        @php
            $producto = \App\Models\Crm\product::find($res['producto_id']);
        @endphp

  

        {{-- Detalle original --}}
        @if(!empty($res['detalle_original']))
            <h3>Detalle de Bodegas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Bodega</th>
                        <th>Producto</th>
                        <th>Cant. descontada</th>
                        <th>Stock restante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($res['detalle_original'] as $d)
                        @php
                            $bod = \App\Models\Crm\bodega::find($d['bodega_id']);
                        @endphp
                        <tr>
                            <td>{{ $bod->nombre ?? 'ID '.$d['bodega_id'] }}</td>
                            <td>{{ $producto->name ?? 'Code '.$res['producto_id'] }}</td>
                            <td>{{ number_format($d['cantidad_descontada'] ?? 0, 2) }}</td>
                            <td>{{ number_format($d['stock_restante'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Equivalentes --}}
        @if(!empty($res['equivalentes']))
            <h3>Equivalentes Utilizados</h3>
            <p style="font-size:11px; color:#555;">
                Los siguientes productos fueron utilizados como equivalentes debido a faltantes en el producto original <strong>{{ $producto->name ?? 'ID '.$res['producto_id'] }}</strong>.
            </p>
            <table>
                <thead>
                    <tr><th>Bodega</th>
                        <th>Producto Equivalente</th>
                        <th>Usado como reemplazo de</th>
                    
                        <th>Cant. descontada</th>
                        <th>Stock restante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($res['equivalentes'] as $eq)
                        @php
                            $prodEq = \App\Models\Crm\product::find($eq['producto_id']);
                            $prodOri = \App\Models\Crm\product::find($eq['producto_origen_id'] ?? $res['producto_id']);
                        @endphp
                        @foreach($eq['bodegas'] ?? [] as $b)
                            @php
                                $bodEq = \App\Models\Crm\bodega::find($b['bodega_id']);
                            @endphp
                            <tr>  <td>{{ $bodEq->nombre ?? 'ID '.$b['bodega_id'] }}</td>
                                <td>{{ $prodEq->name ?? 'ID '.$eq['producto_id'] }}</td>
                                <td>{{ $prodOri->name ?? 'ID '.$res['producto_id'] }}</td>
                              
                                <td>{{ number_format($b['cantidad_descontada'] ?? 0, 2) }}</td>
                                <td>{{ number_format($b['stock_restante'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Errores --}}
        @if(!empty($res['errores']))
            <h3 style="color:#c00;">Errores</h3>
            <ul>
                @foreach($res['errores'] as $e)
                    <li>{{ $e['mensaje'] ?? 'Error desconocido' }}</li>
                @endforeach
            </ul>
        @endif
    @endforeach

    <div class="footer">
        Documento generado automáticamente por SIG-SetasPlast BIC<br>
        © {{ date('Y') }} SetasPlast S.A.S BIC 
    </div>
</body>
</html>
