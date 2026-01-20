<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Traslado pendiente</title>
</head>
<body style="margin:0;padding:0;background:#f5f6f8;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6f8;padding:24px 0;">
    <tr>
      <td align="center">
        <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;">
          <!-- Header -->
          <tr>
            <td style="padding:18px 24px;background:#0f172a;color:#fff;">
              <div style="font-size:16px;font-weight:bold;">Traslado pendiente de aprobación - Bodega</div>
              <div style="font-size:13px;opacity:.9;">Código: {{ $traslado->codigo }} | Fecha: {{ $traslado->created_at->format('Y-m-d H:i') }}</div>
            </td>
          </tr>

          <!-- Resumen -->
          <tr>
            <td style="padding:20px 24px;">
              <p style="margin:0 0 10px 0;color:#111827;font-size:14px;">
                Hola <b>{{ $usuario->name ?? 'Responsable' }}</b>,
              </p>

              <p style="margin:0 0 14px 0;color:#374151;font-size:14px;line-height:1.5;">
                Se ha generado un traslado que requiere tu aprobación como responsable de bodega de origen.
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;">
                <tr>
                  <td style="padding:12px 14px;font-size:13px;color:#111827;">
                    <b>Bodega origen:</b> {{ $traslado->bodegaOrigen->nombre ?? $traslado->bodega_origen_id }}<br>
                    <b>Bodega destino:</b> {{ $traslado->bodegaDestino->nombre ?? $traslado->bodega_destino_id }}<br>
                    <b>Creado por:</b> {{ $traslado->creador->name ?? $traslado->usuario_creador_id }}<br>
                    <b>Estado:</b> {{ $traslado->estado }}<br>
                    @if($traslado->observaciones)
                      <b>Observaciones:</b> {{ $traslado->observaciones }}
                    @endif
                  </td>
                </tr>
              </table>

              <h3 style="margin:18px 0 10px 0;font-size:14px;color:#111827;">Detalle del traslado</h3>

              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e5e7eb;">
                <thead>
                  <tr style="background:#f3f4f6;">
                    <th style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#111827;">#</th>  
                    <th style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#111827;">Código</th>
                    <th align="left" style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#111827;">Producto</th>
                    <th align="right" style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#111827;">Cantidad</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($traslado->detalles as $d)
                    <tr>
                      <td style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#111827;">
                        {{ $loop->iteration }}
                      </td>
                      <td style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#111827;">
                        {{ $d->producto->code ?? ('Producto #'.$d->producto_id) }}
                      </td>
                      <td style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#111827;">
                        {{ $d->producto->name ?? ('Producto #'.$d->producto_id) }}
                      </td>
                      <td align="right" style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#111827;">
                        {{ rtrim(rtrim(number_format((float)$d->cantidad, 2, '.', ''), '0'), '.') }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>

              <!-- Botones -->
              <div style="padding:18px 0 0 0;">
                <a href="{{ $aprobarUrl }}"
                   style="display:inline-block;background:#16a34a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-size:14px;font-weight:bold;">
                  Aprobar
                </a>

                <a href="{{ $rechazarUrl }}"
                   style="display:inline-block;background:#dc2626;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-size:14px;font-weight:bold;margin-left:10px;">
                  Rechazar
                </a>
              </div>

              <p style="margin:16px 0 0 0;color:#6b7280;font-size:12px;line-height:1.4;">
                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                <span style="word-break:break-all;">{{ $aprobarUrl }}</span>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:14px 24px;background:#f9fafb;color:#6b7280;font-size:12px;">
              Mensaje automático del sistema SIG-SETAS. No responder este correo.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
