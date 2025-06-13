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
            margin: 40px;
        }
        .logo {
            width: 80px;
            height: auto;
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
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
        }
        th {
            background-color: #f7f7f7;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .observaciones {
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #fcfcfc;
            font-size: 11px;
            text-align: justify;
        }
    </style>
</head>
<body>

@php
function sinCeros($valor) {
    return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
}
@endphp

<div class="header">
    <div style="display: flex; gap: 20px; align-items: center;">
        @if($cotizacion->empresa === 'global')
            <img class="logo" src="{{ public_path('images/GLOBAL.png') }}" alt="Logo GLOBAL">
            <img class="logo" src="{{ public_path('images/SETAS.png') }}"  alt="Logo SETAS">
        @else
            <img class="logo" src="{{ public_path('images/SETAS.png') }}"  alt="Logo SETAS">
            <img class="logo" src="{{ public_path('images/GLOBAL.png') }}" alt="Logo GLOBAL">
        @endif
        <img class="logo" src="{{ public_path('images/BIC.png') }}"    alt="Logo BIC">
        <img class="logo" src="{{ public_path('images/FENALCO.png') }}" alt="Logo FENALCO">
    </div>
</div>

<hr>

<p><strong>Número de Cotización:</strong> {{ $cotizacion->id }}</p>
<p><strong>Cliente:</strong> {{ $cotizacion->cliente->nombre ?? 'N/A' }}</p>
<p><strong>Teléfono:</strong> {{ $cotizacion->cliente->telefono ?? 'N/A' }}</p>
<p><strong>Correo:</strong> {{ $cotizacion->cliente->email ?? 'N/A' }}</p>
<p><strong>Fecha de Cotización:</strong> {{ $cotizacion->created_at->format('d \\d\\e F \\d\\e Y') }}</p>
<p><strong>Elaborado por:</strong> {{ $cotizacion->user->name }}</p>

<div class="section-title">Detalles:</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Descripción</th>
            <th>Unidad/PAQ</th>
            <th>Valor Unitario</th>
            <th>Valor Paquete/Unidad</th>
            <th>Valor Total con IVA</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($cotizacion->detalles as $item)
        <tr>
            <td>{{ $item->item }}</td>
            <td>
                {{ mb_strtoupper($item->descripcion) }}
                @if ($item->ancho_cm > 0 && $item->largo_cm > 0 && $item->calibre > 0)
                    {{ sinCeros($item->ancho_cm) }}*{{ sinCeros($item->largo_cm) }} Cal.{{ sinCeros($item->cliente_clb) }}
                @endif
            </td>
            <td>{{ $item->cantidad }}</td>
            <td class="text-right">$ {{ number_format($item->valor_unitario, 0, ',', '.') }}</td>
            <td class="text-right">$ {{ number_format($item->valor_paquete, 0, ',', '.') }}</td>
            <td class="text-right">$ {{ number_format($item->valor_total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="text-right" style="margin-top: 20px;">
    <strong>Valor Total: $ {{ number_format($cotizacion->valor_total, 0, ',', '.') }} COP</strong>
</div>

<div class="section-title">Observaciones:</div>
<div class="observaciones">
    {{ $cotizacion->observaciones ?? 'Sin observaciones' }}
</div>

@php
// Control seguro de ruta
$firmante = $cotizacion->user;
$relativePath = $firmante && $firmante->imagen ? 'app/public/' . $firmante->imagen : null;
$path = $relativePath && file_exists(storage_path($relativePath)) 
        ? storage_path($relativePath) 
        : public_path('images/firma-por-defecto.png');
if (file_exists($path)) {
    $type   = pathinfo($path, PATHINFO_EXTENSION);
    $data   = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
} else {
    $base64 = null;
}
@endphp

<div style="margin-top: 50px; text-align: left;">
    @if($base64)
        <img src="{{ $base64 }}" alt="Firma autorizada" style="max-height: 100px; width: auto; margin-bottom: 4px;">
    @else
        <p style="color: #999; font-size: 10px;">{{ $firmante?->name ?? 'Firma no disponible' }}</p>
    @endif
    <div style="font-size: 10px; color: #666;">Firma autorizada</div>
</div>

</body>
</html>
