<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Traslado rechazado por bodega</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 640px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }
        .header {
            background-color: #dc2626;
            color: #ffffff;
            padding: 16px 24px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .content {
            padding: 24px;
            color: #374151;
            font-size: 14px;
            line-height: 1.6;
        }
        .info-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 16px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 6px 0;
        }
        .label {
            font-weight: 600;
            color: #111827;
        }
        .motivo {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 12px;
            margin-top: 16px;
            color: #7f1d1d;
        }
        .footer {
            background-color: #f9fafb;
            padding: 16px 24px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">

    {{-- HEADER --}}
    <div class="header">
        <h1>Traslado rechazado por bodega</h1>
    </div>

    {{-- CONTENT --}}
    <div class="content">
        <p>
            Se informa que el siguiente traslado **ha sido rechazado por la bodega** y no continuará
            con el flujo de aprobación.
        </p>

        <div class="info-box">
            <p><span class="label">Código del traslado:</span> {{ $traslado->codigo }}</p>
            <p><span class="label">Bodega origen:</span> {{ $traslado->bodegaOrigen->nombre ?? 'N/A' }}</p>
            <p><span class="label">Bodega destino:</span> {{ $traslado->bodegaDestino->nombre ?? 'N/A' }}</p>
            <p><span class="label">Fecha del rechazo:</span> {{ now()->format('d/m/Y H:i') }}</p>
            <p><span class="label">Rechazado por:</span> {{ $usuario->name ?? 'Usuario del sistema' }}</p>
        </div>

        @if(!empty($traslado->observaciones))
            <div class="motivo">
                <strong>Motivo del rechazo:</strong>
                <p>{{ $traslado->observaciones }}</p>
            </div>
        @endif

        <p style="margin-top: 20px;">
            Si se requiere corregir la información o realizar un nuevo traslado,
            deberá generarse una nueva solicitud en el sistema.
        </p>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <p>
            Este es un mensaje automático generado por el sistema.<br>
            Por favor no responder a este correo.
        </p>
    </div>

</div>

</body>
</html>
