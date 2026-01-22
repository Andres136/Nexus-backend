<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Notificación de Traslado Pendiente</title>
    </head>
    <body>
    <h2>Traslado {{ $traslado->codigo }}</h2>

<p>
<b>Bodega origen:</b> {{ $traslado->bodegaOrigen->nombre }}<br>
<b>Bodega destino:</b> {{ $traslado->bodegaDestino->nombre }}<br>
<b>Creado por:</b> {{ $traslado->creador->name }}
</p>

<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
        </tr>
    </thead>
    <tbody>
        @foreach($traslado->detalles as $detalle)
        <tr>
            <td>{{ $detalle->producto->nombre }}</td>
            <td align="center">{{ $detalle->cantidad }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<br>

<a href="{{ $aprobarUrl }}"
   style="background:#0d6efd;color:#fff;padding:12px 18px;
          text-decoration:none;border-radius:4px;">
   ✅ Aprobar traslado (Inventario)
</a>

    </body>
</html>
