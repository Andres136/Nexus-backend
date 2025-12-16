<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas - {{ date('Y-m-d H:i:s') }}</title>
    <style>
        /* ✅ Reset para consistencia entre navegadores */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: white;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ✅ Configuración optimizada para papel A4 */
        @page {
            size: A4;
            margin: 10mm;
        }

        /* ✅ Container principal */
        .labels-container {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            align-items: flex-start;
        }

        /* ✅ Etiqueta optimizada para producción */
        .label {
            width: 48mm;
            height: 25mm;
            border: 1px solid #ddd; /* ✅ Borde sutil para guía de corte */
            display: inline-block;
            margin: 1mm;
            padding: 1mm;
            text-align: center;
            font-size: 8px;
            vertical-align: top;
            page-break-inside: avoid; /* ✅ Evita cortar etiquetas */
            position: relative;
            background: white;
            overflow: hidden; /* ✅ Evita desbordamiento */
        }

        /* ✅ Nombre del producto */
 .name {
    font-weight: bold;
    font-size: 7px;
    line-height: 1.1;
    max-height: 5mm;              /* 2 líneas aprox */
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;        /* 🔥 máximo 2 líneas */
    -webkit-box-orient: vertical;
    word-break: break-word;
}


        /* ✅ Código del producto */
        .code {
            font-size: 8px;
            margin-bottom: 1.5mm;
            color: #333;
            font-weight: 500;
            letter-spacing: 0.3px;
        
        }

        /* ✅ Código de barras optimizado */
        .barcode img {
            max-width: 44mm;
            max-height: 12mm;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        /* ✅ Fallback si no hay código de barras */
        .no-barcode {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 12mm;
            background-color: #f5f5f5;
            border: 1px dashed #ccc;
            font-size: 6px;
            color: #666;
        }

        /* ✅ Control de saltos de página precisos */
        .page-break {
            page-break-after: always;
            height: 0;
            margin: 0;
            padding: 0;
        }

        /* ✅ Debug info (solo en desarrollo) */
        .debug-info {
            position: fixed;
            top: 0;
            right: 0;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px;
            font-size: 10px;
            z-index: 1000;
            display: {{ config('app.debug') ? 'block' : 'none' }};
        }

        /* ✅ Estilos específicos para impresión */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .label {
            border: 0.2mm dashed #ccc;

            }
            
            .debug-info {
                display: none !important;
            }
            
            /* ✅ Forzar impresión en negro */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }

        /* ✅ Responsive para diferentes tamaños de papel */
        @media (max-width: 210mm) {
            .labels-container {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    {{-- ✅ Info de debug (solo en desarrollo) --}}
    <div class="debug-info">
        Total: {{ count($productos) }} etiquetas<br>
        Generado: {{ date('H:i:s') }}
    </div>

    {{-- ✅ Validación de datos --}}
    @if(empty($productos))
        <div style="text-align: center; padding: 50px; font-size: 16px; color: #666;">
            ⚠️ No hay productos para generar etiquetas
        </div>
    @else

    <div class="labels-container">
        @foreach ($productos as $i => $p)
            <div class="label">
                {{-- ✅ Validación de campos --}}
            <div class="name {{ strlen($p['name']) > 25 ? 'small' : '' }}">
    {{ $p['name'] }}
</div>

                
                <div class="code">
                    {{ !empty($p['code']) ? $p['code'] : 'SIN-CÓDIGO' }}
                </div>
                
                {{-- ✅ Código de barras con fallback --}}
                <div class="barcode">
                    @if(!empty($p['barcode']))
                        <img src="data:image/png;base64,{{ $p['barcode'] }}" 
                             alt="Código {{ $p['code'] ?? 'N/A' }}"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="no-barcode" style="display: none;">
                            SIN CÓDIGO DE BARRAS
                        </div>
                    @else
                        <div class="no-barcode">
                            CÓDIGO NO DISPONIBLE
                        </div>
                    @endif
                </div>
            </div>

         {{-- ✅ Correcto para A4 --}}
@if (($i + 1) % 40 === 0 && ($i + 1) < count($productos))
    <div class="page-break"></div>
@endif

        @endforeach
    </div>

    @endif


</body>
</html>