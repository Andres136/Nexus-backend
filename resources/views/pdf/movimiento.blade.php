<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimiento de Stock Consolidado</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 25px; }
        h1 { color: #198754; font-size: 20px; text-align: center; border-bottom: 2px solid #198754; padding-bottom: 8px; }
        h2 { color: #198754; margin-top: 25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: center; }
        th { background-color: #f2f2f2; }
        .section { page-break-inside: avoid; margin-bottom: 20px; }
        .footer { text-align: center; font-size: 11px; color: #777; margin-top: 40px; border-top: 1px solid #ccc; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>Movimiento de Stock Consolidado</h1>
    <p><strong>Usuario:</strong> {{ $usuario->name ?? 'Sistema' }}  
       <br><strong>Fecha:</strong> {{ $fecha }}</p>

    @foreach($resultados as $res)
        <div class="section">
            <h2>Producto ID: {{ $res['producto_id'] }}</h2>
            <p><strong>Cantidad requerida:</strong> {{ $res['cantidad_requerida'] }}</p>
            <p><strong>Resultado:</strong> {{ $res['message'] }}</p>

            {{-- Detalles originales --}}
            @if(!empty($res['detalle_original']))
                <h3>Detalle de Bodegas</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Bodega</th>
                            <th>Cant. descontada</th>
                            <th>Stock restante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($res['detalle_original'] as $d)
                            @php
                                $prod = \App\Models\Crm\product::find($d['producto_id']);
                                $bod  = \App\Models\Crm\bodega::find($d['bodega_id']);
                            @endphp
                            <tr>
                                <td>{{ $prod->name ?? 'ID '.$d['producto_id'] }}</td>
                                <td>{{ $bod->nombre ?? 'ID '.$d['bodega_id'] }}</td>
                                <td>{{ number_format($d['cantidad_descontada'], 2) }}</td>
                                <td>{{ number_format($d['stock_restante'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Equivalentes --}}
            @if(!empty($res['equivalentes']))
                <h3>Equivalentes Utilizados</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Producto Equivalente</th>
                            <th>Bodega</th>
                            <th>Cant. descontada</th>
                            <th>Stock restante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($res['equivalentes'] as $eq)
                            @foreach($eq['bodegas'] ?? [] as $b)
                                @php
                                    $prodEq = \App\Models\Crm\product::find($eq['producto_id']);
                                    $bodEq  = \App\Models\Crm\bodega::find($b['bodega_id']);
                                @endphp
                                <tr>
                                    <td>{{ $prodEq->name ?? 'ID '.$eq['producto_id'] }}</td>
                                    <td>{{ $bodEq->nombre ?? 'ID '.$b['bodega_id'] }}</td>
                                    <td>{{ number_format($b['cantidad_descontada'], 2) }}</td>
                                    <td>{{ number_format($b['stock_restante'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(!empty($res['errores']))
                <h3 style="color:#c00;">Errores</h3>
                <ul>
                    @foreach($res['errores'] as $e)
                        <li>{{ $e['mensaje'] ?? 'Error desconocido' }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Documento consolidado generado automáticamente por SIG-SetasPlast BIC<br>
        © {{ date('Y') }} SetasPlast S.A.S BIC — ISO 9001 / 14001 / 45001
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimiento de Stock Consolidado</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 25px; }
        h1 { color: #198754; font-size: 20px; text-align: center; border-bottom: 2px solid #198754; padding-bottom: 8px; }
        h2 { color: #198754; margin-top: 25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: center; }
        th { background-color: #f2f2f2; }
        .section { page-break-inside: avoid; margin-bottom: 20px; }
        .footer { text-align: center; font-size: 11px; color: #777; margin-top: 40px; border-top: 1px solid #ccc; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>Movimiento de Stock Consolidado</h1>
    <p><strong>Usuario:</strong> {{ $usuario->name ?? 'Sistema' }}  
       <br><strong>Fecha:</strong> {{ $fecha }}</p>

    @foreach($resultados as $res)
        <div class="section">
          @php
    $producto = \App\Models\Crm\product::find($res['producto_id']);
@endphp

<h2>Producto: {{ $producto->name ?? 'ID '.$res['producto_id'] }}</h2>

            <p><strong>Cantidad requerida:</strong> {{ $res['cantidad_requerida'] }}</p>
            <p><strong>Resultado:</strong> {{ $res['message'] }}</p>

            {{-- Detalles originales --}}
            @if(!empty($res['detalle_original']))
                <h3>Detalle de Bodegas</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Bodega</th>
                            <th>Cant. descontada</th>
                            <th>Stock restante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($res['detalle_original'] as $d)
                            @php
                                $prod = \App\Models\Crm\product::find($d['producto_id']);
                                $bod  = \App\Models\Crm\bodega::find($d['bodega_id']);
                            @endphp
                            <tr>
                                <td>{{ $prod->name ?? 'ID '.$d['producto_id'] }}</td>
                                <td>{{ $bod->nombre ?? 'ID '.$d['bodega_id'] }}</td>
                                <td>{{ number_format($d['cantidad_descontada'], 2) }}</td>
                                <td>{{ number_format($d['stock_restante'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Equivalentes --}}
            @if(!empty($res['equivalentes']))
                <h3>Equivalentes Utilizados</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Producto Equivalente</th>
                            <th>Bodega</th>
                            <th>Cant. descontada</th>
                            <th>Stock restante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($res['equivalentes'] as $eq)
                            @foreach($eq['bodegas'] ?? [] as $b)
                                @php
                                    $prodEq = \App\Models\Crm\product::find($eq['producto_id']);
                                    $bodEq  = \App\Models\Crm\bodega::find($b['bodega_id']);
                                @endphp
                                <tr>
                                    <td>{{ $prodEq->name ?? 'ID '.$eq['producto_id'] }}</td>
                                    <td>{{ $bodEq->nombre ?? 'ID '.$b['bodega_id'] }}</td>
                                    <td>{{ number_format($b['cantidad_descontada'], 2) }}</td>
                                    <td>{{ number_format($b['stock_restante'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(!empty($res['errores']))
                <h3 style="color:#c00;">Errores</h3>
                <ul>
                    @foreach($res['errores'] as $e)
                        <li>{{ $e['mensaje'] ?? 'Error desconocido' }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Documento consolidado generado automáticamente por SIG-SetasPlast BIC<br>
        © {{ date('Y') }} SetasPlast S.A.S BIC 
    </div>
</body>
</html>
