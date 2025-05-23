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
            width: 120px;
            height:80px;
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
<div style="display: flex; gap: 20px; align-items: center;">
    @if($cotizacion->empresa === 'global')
        {{-- Primero el logo Global, luego el de Setas --}}
        <img class="logo" src="{{ public_path('images/GLOBAL.png') }}" alt="Logo GLOBAL">
        <img class="logo" src="{{ public_path('images/SETAS.png') }}"  alt="Logo SETAS">
    @else
        {{-- Primero el logo Setas, luego el de Global --}}
        <img class="logo" src="{{ public_path('images/SETAS.png') }}"  alt="Logo SETAS">
        <img class="logo" src="{{ public_path('images/GLOBAL.png') }}" alt="Logo GLOBAL">
    @endif

    {{-- Y los fijos --}}
    <img class="logo" src="{{ public_path('images/BIC.png') }}"    alt="Logo BIC">
    <img class="logo" src="{{ public_path('images/FENALCO.png') }}" alt="Logo FENALCO">
</div>



    
    <hr style="margin: 20px 0;">
    <p><strong>Numero de Cotizacion:</strong> {{ $cotizacion->id }}</p>

    <p><strong>Cliente:</strong> {{ $cotizacion->cliente->nombre ?? 'N/A' }}</p>

    <p><strong>Teléfono:</strong> {{ $cotizacion->cliente->telefono ?? 'N/A' }}</p>
    <p><strong>Correo:</strong> {{ $cotizacion->cliente->email ?? 'N/A' }}</p>
    <p><strong>Fecha de Cotización:</strong> {{ $cotizacion->created_at->format('d/m/Y') }}</p>
    <p><strong>Elaborado:</strong> {{ $cotizacion->user->name }}</p>
    



    <div class="section-title">Detalles:</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Descripción</th>
          
          
              
                <th> Unidad/PAQ *</th>
                   <th>Valor Unitario</th>
                <th>Valor Paquete/Unidad</th>
             
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
        <strong>Valor Total: ${{ number_format($cotizacion->valor_total, 0, ',', '.') }}</strong>
    </div>
    <div class="section-title">Observaciones:</div>
    <p>{{ $cotizacion->observaciones ?? 'Sin observaciones' }}</p>
    @php
    // 1) Carga el usuario que firma
    $firmante = $cotizacion->user;
  
    // 2) Define ruta al fichero dentro de storage/app/public
    //    Asegúrate de que $firmante->imagen === "usuarios/tu-archivo.png"
    $relativePath = $firmante && $firmante->imagen
      ? 'app/public/' . $firmante->imagen
      : null;
  
    // 3) Construye la ruta absoluta
    $path = $relativePath
      ? storage_path($relativePath)
      : public_path('images/firma-por-defecto.png');
  
    // DEBUG: Descomenta para ver en tu log/console cuál es el $path
    // \Log::debug('Firma path: ' . $path);
  
    // 4) Lee el fichero y genera el Data-URI
    if ($path && file_exists($path)) {
        $type   = pathinfo($path, PATHINFO_EXTENSION);
        $data   = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    } else {
        // Si no existe, deja vacío para no romper el <img>
        $base64 = null;
        // \Log::warning("No se encontró firma en ruta: $path");
    }
  @endphp
  
  <footer style="position: fixed; bottom: 20px; width: 100%;  left: 20px; text-align: left;">
    @if($base64)
      <img
        src="{{ $base64 }}"
        alt="Firma autorizada"
        style="height: 120px; width: auto; margin-bottom: 4px;"
      >
    @else
      <p style="color: #999; font-size: 10px;">
        {{ $firmante?->name ?? 'Firma no disponible' }}
      </p>
    @endif
  
    <div style="font-size: 10px; color: #666;">
      Firma autorizada
    </div>
  </footer>
  

  
      
</body>
</html>
