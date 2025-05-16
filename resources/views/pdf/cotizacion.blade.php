<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización #{{ $cotizacion->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .logo {
            width: 140px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    @php
    function sinCeros($valor) {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
@endphp
    @php
    $logoPrincipal = $cotizacion->empresa === 'global'
        ? public_path('images/GLOBAL.png')
        : public_path('images/SETAS.png');
@endphp

<div class="header">
    <div style="display: flex; gap: 20px; align-items: center;">
        <img src="{{ $logoPrincipal }}" alt="Logo empresa" style="height: 60px;">
        <img src="{{ public_path('images/BIC.png') }}" alt="Logo BIC" style="height: 60px;">
        <img src="{{ public_path('images/FENALCO.png') }}" alt="Logo FENALCO" style="height: 60px;">
    </div>
    <div style="text-align: right;">
        <strong>Fecha:</strong> {{ $cotizacion->created_at->format('d/m/Y') }}
    </div>
</div>

    
    <hr style="margin: 20px 0;">
    
    <p><strong>Cliente:</strong> {{ $cotizacion->cliente->nombre ?? 'N/A' }}</p>
    <p><strong>Asesor:</strong> {{ $cotizacion->user->name ?? 'N/A' }}</p>
    <p><strong>Teléfono:</strong> {{ $cotizacion->user->telefono ?? 'N/A' }}</p>
    <p><strong>Correo:</strong> {{ $cotizacion->user->email ?? 'N/A' }}</p>
    



    <div class="section-title">Detalles:</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Descripción</th>
          
          
              
                <th>    Unidad o PAQ *</th>
                <th>Valor Paquete o Unidad</th>
                <th>Valor Unitario</th>
                <th>Valor Total con Iva</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cotizacion->detalles as $item)
                <tr>
                    <td>{{ $item->item }}</td>
               
                
                    <td>
                        {{ mb_strtoupper($item->descripcion) }}

                        @if ($item->ancho_cm > 0 && $item->largo_cm > 0 && $item->calibre > 0)
                            {{ sinCeros($item->ancho_cm) }}*{{ sinCeros($item->largo_cm) }} Cal.{{ sinCeros($item->calibre) }}
                        @endif
                    </td>
                    
                

           
                    <td>{{ $item->cantidad }}</td>
                   
                    <td class="text-right">$ {{ number_format($item->precio_total, 0, ',', '.') }}</td>
                    <td class="text-right">$ {{ number_format($item->valor_unitario, 0, ',', '.') }}</td>
                    <td class="text-right">$ {{ number_format($item->valor_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="text-right" style="margin-top: 20px;">
        <strong>Valor Total: ${{ number_format($cotizacion->valor_total, 0, ',', '.') }}</strong>
    </div>
    <div class="section-title">Observaciones:</div>
    <p>{{ $cotizacion->observaciones ?? 'Sin observaciones' }}</p>

</body>
</html>
