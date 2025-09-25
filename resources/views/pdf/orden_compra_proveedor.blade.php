<!-- resources/views/pdf/orden_compra_proveedor.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra - {{ $orden->numero_orden }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 18px;
            color: #e74c3c;
            font-weight: normal;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-box {
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
            margin-bottom: 15px;
        }

        .info-box h3 {
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-row strong {
            display: inline-block;
            width: 100px;
            color: #2c3e50;
        }

        .detalle-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }

        .detalle-table th {
            background-color: #34495e;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #34495e;
        }

        .detalle-table td {
            padding: 10px 8px;
            border: 1px solid #ddd;
        }

        .detalle-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .footer {
            margin-top: 40px;
            border-top: 1px solid #bdc3c7;
            padding-top: 20px;
            font-size: 10px;
            color: #7f8c8d;
        }

        .summary-box {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .observaciones {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .observaciones h4 {
            color: #856404;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .firmas {
            margin-top: 60px;
            display: table;
            width: 100%;
        }

        .firma-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
            vertical-align: top;
        }

        .firma-box strong {
            display: block;
            margin-bottom: 5px;
        }

        .no-data {
            padding: 20px;
            color: #7f8c8d;
            text-align: center;
            font-style: italic;
        }

        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
 <!-- Header con logo mejorado -->
<!-- Header con logo mejorado -->
<div class="header">
    <div style="display: table; width: 100%; margin-bottom: 20px;">
        
        <!-- Logo y nombre de la empresa -->
        @if($orden->empresa)
        <div style="display: table-cell; width: 25%; vertical-align: middle; text-align: center;">
            @if(isset($orden->empresa->logo_base64))
            <img src="{{ $orden->empresa->logo_base64 }}" 
                 alt="Logo {{ $orden->empresa->nombre }}" 
                 style="max-width: 120px; max-height: 80px; display: block; margin: 0 auto;">
            @endif
          <p style="margin: 8px 0 0 0; font-size: 20px; color: #666; font-weight: bold; text-align: center; text-transform: uppercase;">
    {{ $orden->empresa->nombre }}
</p>
        </div>
        @endif
        
        <!-- Información central -->
        <div style="display: table-cell; text-align: center; vertical-align: middle;">
            <h1 style="margin: 0; font-size: 24px; color: #2c3e50;">ORDEN DE COMPRA</h1>
            <h2 style="margin: 5px 0; font-size: 18px; color: #e74c3c;">{{ $orden->numero_orden }}</h2>
        </div>
        
        <!-- Información adicional derecha -->
        <div style="display: table-cell; width: 25%; text-align: right; vertical-align: middle; font-size: 12px;">
            <strong>Fecha:</strong><br>
            {{ $orden->fecha ? $orden->fecha->format('d/m/Y') : 'Sin fecha' }}<br><br>
          
        </div>
    </div>
    




</div>
    

<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tr>
        <!-- Empresa -->
        <td style=" #ccc; width: 50%; vertical-align: top; padding-right: 10px;">
            <div style="border: 1px solid #ccc; background: #fff; border-radius: 4px; overflow: hidden;">
                <!-- Encabezado -->
               
                <!-- Contenido -->
                <div style="padding: 12px; font-size: 12px; line-height: 1.5;">
                 
                    @if($orden->empresa && $orden->empresa->nit)
                        <div><strong>NIT:</strong> {{ $orden->empresa->nit }}</div>
                    @endif
                    @if($orden->empresa && $orden->empresa->telefono)
                        <div><strong>Teléfono:</strong> {{ $orden->empresa->telefono }}</div>
                    @endif
                    @if($orden->empresa && $orden->empresa->direccion)
                        <div><strong>Dirección:</strong> {{ $orden->empresa->direccion }}</div>
                    @endif
                </div>
            </div>
        </td>

        <!-- Proveedor -->
        <td style="width: 50%; vertical-align: top; padding-left: 10px;">
            <div style="border: 1px solid #ccc; background: #fff; border-radius: 4px; overflow: hidden;">
          
                <!-- Contenido -->
                <div style="padding: 12px; font-size: 12px; line-height: 1.5;">
                    <div><strong>Para:</strong> {{ $orden->proveedor->nombre ?? 'Sin proveedor' }}</div>
                    @if($orden->proveedor && $orden->proveedor->nit)
                        <div><strong>NIT:</strong> {{ $orden->proveedor->nit }}</div>
                    @endif
                    @if($orden->proveedor && $orden->proveedor->telefono)
                        <div><strong>Teléfono:</strong> {{ $orden->proveedor->telefono }}</div>
                    @endif
                    @if($orden->proveedor && $orden->proveedor->email)
                        <div><strong>Email:</strong> {{ $orden->proveedor->email }}</div>
                    @endif
                    @if($orden->proveedor && $orden->proveedor->direccion)
                        <div><strong>Dirección:</strong> {{ $orden->proveedor->direccion }}</div>
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>


    <!-- Tabla de Detalles -->
    <table class="detalle-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 8%">Item</th>
                <th class="text-center" style="width: 15%">Código</th>
                <th class="text-left" style="width: 47%">Descripción</th>
                <th class="text-center" style="width: 15%">Cant. Solicitada</th>
            
            </tr>
        </thead>
        <tbody>
            @forelse($orden->detalles as $detalle)
            <tr>
                <td class="text-center">{{ $detalle->item }}</td>
                <td class="text-center">
                    <strong>{{ $detalle->code ?? 'N/A' }}</strong>
                </td>
                <td class="text-left">{{ $detalle->descripcion ?? 'Sin descripción' }}</td>
                <td class="text-center">
                    <strong>{{ number_format($detalle->cantidad_solicitada, 0) }}</strong>
                </td>
            
            </tr>
            @empty
            <tr>
                <td colspan="5" class="no-data">
                    No hay detalles registrados para esta orden
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
   @if($orden->observaciones)
    <div class="observaciones">
        <h4>📝 Observaciones</h4>
        <p style="margin: 0; line-height: 1.5;">{{ $orden->observaciones }}</p>
    </div>
    @endif
    <!-- Resumen -->
    <div class="summary-box">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%;">
                <strong>📦 Total de Items:</strong> {{ $orden->detalles->count() }}
            </div>
            <div style="display: table-cell; width: 50%; text-align: right;">
                <strong>📊 Total Solicitado:</strong> {{ $orden->detalles->sum('cantidad_solicitada') }} 
            </div>
        </div>
    </div>

    <!-- Firmas -->
    <div class="firmas">
        <div class="firma-box">
            <strong>Solicitado por</strong>
            <small>{{ $orden->usuario->name ?? '' }}</small>
        </div>
        <div class="firma-box">
            <strong>Aprobado por</strong>
            <small>_________________</small>
        </div>
        <div class="firma-box">
            <strong>Recibido por</strong>
            <small>_________________</small>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell;">
                Documento generado el {{ now()->format('d/m/Y H:i:s') }}
            </div>
            <div style="display: table-cell; text-align: right;">
                Software-Nexus - Setasplast
            </div>
        </div>
    </div>
</body>
</html>