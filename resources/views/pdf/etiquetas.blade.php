<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Etiqueta Rollo</title>

<style>
@page {
    size: 32mm 25mm;
    margin: 0;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    line-height: normal;
}

/* ETIQUETA = PÁGINA */
body {
    font-family: Arial, Helvetica, sans-serif;
    line-height: normal;
}

/* ETIQUETA = PÁGINA */
.etiqueta {
    width: 32mm;
    height: 25mm;
    padding: 1mm 1.5mm 0.5mm 1.5mm;
    text-align: center;
    overflow: hidden;

    display: flex;
    flex-direction: column;
    justify-content: center; /* 🔥 CENTRADO REAL */
}

/* TEXTO */
.nombre {
    font-size: 6.8px;
    font-weight: bold;
    line-height: 1.05;
    margin-bottom: 0.5mm;
    margin-top: 1.2mm;
}

.codigo {
    font-size: 6px;
    margin-bottom: 0.6mm;
}

/* BARCODE */
.barcode img {
    width: 26mm;
    max-height: 10mm;
    object-fit: contain;
}


/* TEXTO */
.nombre {
    font-size: 6.8px;
    font-weight: bold;
    line-height: 1.05;
    margin-bottom: 0.5mm;
}

.codigo {
    font-size: 6px;
    margin-bottom: 0.6mm;
}

/* BARCODE */
.barcode img {
    width: 26mm;
    max-height: 10mm;
    object-fit: contain;
}

</style>
</head>

<body>

@foreach ($productos as $p)
<div class="etiqueta">
    <div class="nombre">{{ $p['name'] ?? 'SIN NOMBRE' }}</div>
    <div class="codigo">{{ $p['code'] ?? 'SIN-CÓDIGO' }}</div>

    @if (!empty($p['barcode']))
    <div class="barcode">
        <img src="data:image/png;base64,{{ $p['barcode'] }}">
    </div>
    @endif
</div>
@endforeach

</body>
</html>
