{{-- resources/views/emails/traslados/aprobado_bodega.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Traslado aprobado por bodega</title>
</head>
<body>
    <h2>Traslado aprobado por bodega</h2>

    <p>
        El traslado <strong>{{ $traslado->codigo }}</strong> fue aprobado correctamente
        por el responsable de bodega.
    </p>

    <p>
        Estado actual:
        <strong>{{ $traslado->estado }}</strong>
    </p>
</body>
</html>
