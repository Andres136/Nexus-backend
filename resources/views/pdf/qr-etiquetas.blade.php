<!DOCTYPE html>
<html>
<head>
 <style>
   @page {
    size: 100.5mm 70mm;
    margin: 0;
}

html, body {
    width: 106.5mm;
    height: 56mm;
    margin: 0;
    padding: 0;
    overflow: hidden;
    font-family: Arial, Helvetica, sans-serif;
}

.label {
    position: relative;
    width: 106.5mm;
    height: 56mm;
    padding: 8px 10px;
    box-sizing: border-box;
    text-align: center;
}

.logo img {
    max-height: 32px;
    margin-bottom: 4px;
    
}

.product-name {
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.qr img {
    width: 115px;
    height: 115px;
}

.product-code {
    font-size: 9px;
    margin-top: 2px;
    letter-spacing: 0.5px;
}


</style>

</head>
<body>
@foreach ($etiquetas as $e)

    @if (!$loop->first)
        <div style="page-break-before: always;"></div>
    @endif

    <div class="label">
        @if (!empty($e['logo']))
            <div class="logo">
                <img src="{{ $e['logo'] }}">
            </div>
        @endif

        <div class="product-name">{{ $e['nombre'] }}</div>

        <div class="qr">
            <img src="data:image/png;base64,{{ $e['qr'] }}">
        </div>

        <div class="product-code">{{ $e['codigo'] }}</div>
    </div>

@endforeach


</body>

</html>