<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 0; }
body {
    font-family: Arial, Helvetica, sans-serif;
    text-align: center;
}
.etiqueta {
    width: 100%;
    height: 100%;
    padding: 6mm;
}
.nombre {
    font-size: 14px;
    font-weight: bold;
}
.codigo {
    font-size: 11px;
    margin-bottom: 3mm;
}
.qr svg {
    width: 30mm;
    height: 30mm;
}

</style>
</head>
<body>

@foreach ($productos as $p)
<div class="etiqueta">
    <div class="nombre">{{ $p['name'] }}</div>
    <div class="codigo">{{ $p['code'] }}</div>

    <div class="qr">
        {!! $p['qr_svg'] !!}
    </div>
</div>
@endforeach


</body>
</html>

