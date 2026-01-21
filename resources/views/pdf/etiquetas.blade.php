<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page {
    size: 102.5mm 56mm;
    margin: 0;
}

html, body {
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
}

/* 🔥 CONTENEDOR SIN HEIGHT FIJO */
.etiqueta {
    width: 103.5mm;
    min-height: 50mm;
    padding: 3mm;
    box-sizing: border-box;

    display: table;
    text-align: center;
}

/* CONTENIDO CENTRADO */
.contenido {
    display: table-cell;
    vertical-align: middle;
 
}

/* TEXTO */
.nombre {
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 3mm;
    text-transform: uppercase;
}

.codigo {
    font-size: 8px;
    margin-bottom: 2mm;
    font-family: monospace;
}

/* BARCODE */
.barcode img {
    width: 85mm;
    max-height: 15mm;
    object-fit: contain;
    margin-top: 2mm;
}

</style>
</head>

<body>
@foreach ($productos as $p)
<div class="etiqueta">
    <div class="contenido">
        <div class="nombre">{{ $p['name'] }}</div>
        <div class="codigo">{{ $p['code'] }}</div>
        <div class="barcode">
            <img src="data:image/png;base64,{{ $p['barcode'] }}">
        </div>
    </div>
</div>
@endforeach
</body>
</html>
