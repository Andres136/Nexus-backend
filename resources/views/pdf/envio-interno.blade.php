{{-- filepath: /home/tic/Escritorio/Proyectos Setas/SIG_SETAS/setasplast-api/resources/views/pdf/envio-interno.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Envío Interno #{{ $envio->id }}</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 12px; 
            margin: 20px;
            line-height: 1.4;
        }
        
        .header {
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .logo-section {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
        }
        
        .company-info {
            display: table-cell;
            width: 60%;
            text-align: center;
            vertical-align: middle;
        }
        
        .document-info {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
            text-align: right;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #4a90e2;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .info-grid td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        
        .info-label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 25%;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        
        .products-table th {
            background-color: #4a90e2;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        .products-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .signature-section {
            margin-top: 60px;
            page-break-inside: avoid;
        }
        
        .signature-row {
            display: table;
            width: 100%;
            margin-top: 40px;
        }
        
        .signature-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 20px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }
        
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .logo {
            max-width: 80px;
            max-height: 60px;
        }
        
        .total-row {
            background-color: #e9ecef !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
    {{-- ✅ Header con logo y empresa --}}
    <div class="header">
        <div class="company-header">
            <div class="logo-section">
                @if($envio->empresa && $envio->empresa->logo)
                    <img src="{{ public_path('storage/' . $envio->empresa->logo) }}" 
                         alt="Logo {{ $envio->empresa->nombre }}" 
                         class="logo">
                @endif
            </div>
            
            <div class="company-info">
                <div class="company-name">
                    {{ $envio->empresa->nombre ?? 'SETASPLAST' }}
                </div>
                <div>{{ $envio->empresa->direccion ?? 'Sistema de Gestión de Inventarios' }}</div>
                @if($envio->empresa && ($envio->empresa->telefono || $envio->empresa->email))
                    <div>
                        @if($envio->empresa->telefono)
                            Tel: {{ $envio->empresa->telefono }}
                        @endif
                        @if($envio->empresa->email)
                            @if($envio->empresa->telefono) | @endif
                            Email: {{ $envio->empresa->email }}
                        @endif
                    </div>
                @endif
            </div>
            
            <div class="document-info">
                <strong>No. {{ str_pad($envio->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
                <small>{{ $envio->created_at->format('d/m/Y') }}</small>
            </div>
        </div>
        
        <div class="document-title">Traslado Interno de Inventario</div>
    </div>

    {{-- ✅ Información del envío --}}
    <table class="info-grid">
        <tr>
            <td class="info-label">Fecha de Envío:</td>
            <td>{{ $envio->fecha_envio ? \Carbon\Carbon::parse($envio->fecha_envio)->format('d/m/Y') : $envio->created_at->format('d/m/Y') }}</td>
            <td class="info-label">Usuario:</td>
            <td>{{ $envio->usuario->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">Sede Origen:</td>
            <td>{{ $envio->sedeOrigen->nombre ?? 'Sede no encontrada' }}</td>
            <td class="info-label">Sede Destino:</td>
            <td>{{ $envio->sedeDestino->nombre ?? 'Sede no encontrada' }}</td>
        </tr>
     
    </table>

   
    <h3>Detalles del Envío</h3>
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 8%;">Item</th>
                <th style="width: 30%;">N#O</th>
                <th style="width: 15%;">Código</th>
                <th style="width: 32%;">Descripción</th>
                <th style="width: 10%;">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalles as $detalle)
                <tr>
                    <td class="text-center">{{ $detalle->item }}</td>
                    <td>{{ $detalle->ordenCompra->numero_orden ?? 'Orden no encontrada' }}</td>
                    <td>{{ $detalle->code_id ?? ($detalle->producto->code ?? 'N/A') }}</td>
                 
                    <td>{{ $detalle->descripcion ?? 'Sin descripción' }}</td>
                    <td class="text-center">{{ number_format($detalle->cantidad, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
 
    </table>

    {{-- ✅ Observaciones --}}
    @if($envio->notas)
        <div style="margin: 20px 0;">
            <h4>Observaciones:</h4>
            <div style="border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9;">
                {{ $envio->notas }}
            </div>
        </div>
    @endif

    {{-- ✅ Sección de firmas --}}
    <div class="signature-section">
        <div class="signature-row">
            <div class="signature-cell">
                <div class="signature-line">
                    <strong>ENTREGA</strong><br>
                    {{ $envio->usuario->name ?? 'Sin asignar' }}<br>
                    <small>Nombre y Firma</small>
                </div>
            </div>
            
            <div class="signature-cell">
                <div class="signature-line">
                    <strong>RECIBE</strong><br>
                    _________________________<br>
                    <small>Nombre y Firma</small>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-center;">
            <small>
                <strong>Fecha de Recepción:</strong> __________________ &nbsp;&nbsp;&nbsp;
                <strong>Hora:</strong> __________________
            </small>
        </div>
    </div>

    {{-- ✅ Footer --}}
    <div class="footer">
        <div>
            Documento generado automáticamente el {{ now()->format('d/m/Y') }}
            <br>
            {{ $envio->empresa->nombre ?? 'SETASPLAST' }} - Sistema de Gestión de Inventarios Nexus
        </div>
    </div>
</body>
</html>