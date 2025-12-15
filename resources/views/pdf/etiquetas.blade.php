<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 10px;
        }

        .label {
            width: 48mm;
            height: 25mm;
            border: 1px solid #000;
            display: inline-block;
            margin: 2mm;
            text-align: center;
            font-size: 9px;
            vertical-align: top;
        }

        .name {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 2px;
        }

        .code {
            font-size: 8px;
            margin-bottom: 2px;
        }

        img {
            width: 100%;
            height: auto;
        }

        /* 🔥 CLAVE: forzar salto limpio */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

@foreach ($productos as $i => $p)

    <div class="label">
        <div class="name">{{ $p['name'] }}</div>
        <div class="code">{{ $p['code'] }}</div>
        <img src="data:image/png;base64,{{ $p['barcode'] }}">
    </div>

    {{-- 🔥 cada 6 etiquetas salto de página (ajusta según papel) --}}
    @if (($i + 1) % 6 === 0)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>
