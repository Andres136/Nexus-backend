<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Requerimiento {{ $requerimiento->codigo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; margin: 28px; }
        .header { border-bottom: 3px solid #1f3a8a; padding-bottom: 12px; margin-bottom: 18px; }
        .eyebrow { color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; }
        h1 { margin: 4px 0 0; color: #1f3a8a; font-size: 24px; }
        h2 { margin: 0 0 8px; font-size: 14px; color: #1f2937; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .grid td { vertical-align: top; padding: 6px 8px; border: 1px solid #e5e7eb; }
        .label { color: #64748b; font-size: 10px; text-transform: uppercase; }
        .value { margin-top: 2px; font-weight: 700; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; background: #eef2ff; color: #3730a3; font-weight: 700; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items th { background: #f1f5f9; color: #334155; text-align: left; padding: 8px; border: 1px solid #dbe3ea; font-size: 10px; text-transform: uppercase; }
        table.items td { padding: 8px; border: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .section { margin-top: 18px; }
        .footer { position: fixed; bottom: 18px; left: 28px; right: 28px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">Requerimiento interno de compra</div>
        <h1>{{ $requerimiento->codigo }}</h1>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="label">Solicitante</div>
                <div class="value">{{ $requerimiento->solicitante?->nombre_completo ?? $requerimiento->solicitante?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="label">Sede</div>
                <div class="value">{{ $requerimiento->sede?->nombre ?? '—' }}</div>
            </td>
            <td>
                <div class="label">Bodega</div>
                <div class="value">{{ $requerimiento->bodega?->nombre ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Fecha solicitud</div>
                <div class="value">{{ optional($requerimiento->fecha_solicitud)->format('Y-m-d H:i') }}</div>
            </td>
            <td>
                <div class="label">Fecha requerida</div>
                <div class="value">{{ optional($requerimiento->fecha_requerida)->format('Y-m-d') ?? '—' }}</div>
            </td>
            <td>
                <div class="label">Estado</div>
                <div class="value"><span class="badge">{{ str_replace('_', ' ', $requerimiento->estado) }}</span></div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Prioridad</div>
                <div class="value">{{ ucfirst($requerimiento->prioridad) }}</div>
            </td>
            <td colspan="2">
                <div class="label">Orden de compra</div>
                <div class="value">{{ $requerimiento->ordenCompra?->numero_orden ?? 'Pendiente' }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2>Productos solicitados</h2>
        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto / referencia</th>
                    <th class="right">Solicitado</th>
                    <th class="right">Aprobado</th>
                    <th>Proveedor sugerido</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requerimiento->detalles as $index => $detalle)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $detalle->producto?->name ?? $detalle->referencia_sugerida ?? '—' }}</strong>
                            @if($detalle->producto?->code)
                                <br><span class="muted">{{ $detalle->producto->code }}</span>
                            @endif
                        </td>
                        <td class="right">{{ number_format($detalle->cantidad_solicitada, 2) }}</td>
                        <td class="right">{{ $detalle->cantidad_aprobada !== null ? number_format($detalle->cantidad_aprobada, 2) : '—' }}</td>
                        <td>{{ $detalle->proveedorSugerido?->nombre ?? '—' }}</td>
                        <td>{{ $detalle->observacion ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($requerimiento->observacion)
        <div class="section">
            <h2>Observación general</h2>
            <p>{{ $requerimiento->observacion }}</p>
        </div>
    @endif

    <div class="section">
        <h2>Trazabilidad</h2>
        <table class="items">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Evento</th>
                    <th>Usuario</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requerimiento->eventos as $evento)
                    <tr>
                        <td>{{ optional($evento->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ str_replace('_', ' ', $evento->tipo_evento) }}</td>
                        <td>{{ $evento->usuario?->nombre_completo ?? $evento->usuario?->name ?? '—' }}</td>
                        <td>{{ $evento->comentario ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Documento generado por SIG. Este requerimiento no descuenta inventario; soporta el análisis y generación de orden de compra.
    </div>
</body>
</html>
