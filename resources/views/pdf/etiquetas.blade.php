<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Etiqueta Rollo</title>

<style>
/* 🖨️ CONFIGURACIÓN DE IMPRESIÓN */
@page {
    size: 103.5mm 50mm;
    margin: 0;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* 🏷️ ETIQUETA = UNA PÁGINA */
.etiqueta {
    width: 97.5mm;   /* área útil */
    height: 44mm;    /* área útil */
    margin: 3mm;     /* margen físico */

    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    text-align: center;
    overflow: hidden;
}

/* 🔤 NOMBRE PRODUCTO */
.nombre {
    font-size: 14px;
    font-weight: bold;
    line-height: 1.1;
    margin-bottom: 2mm;
    max-height: 12mm;
    overflow: hidden;
}

/* 🔢 CÓDIGO */
.codigo {
    font-size: 11px;
    margin-bottom: 2mm;
    letter-spacing: 0.5px;
}
.barcode {
    padding: 0 6mm; /* zona silenciosa */
}


/* 🧾 BARCODE */
.barcode img {
    width: 80mm;
    max-height: 22mm;
    object-fit: contain;
}

/* 🚫 EVITAR SALTOS EXTRA */
.etiqueta:last-child {
    page-break-after: auto;
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
