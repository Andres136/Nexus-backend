<!-- filepath: /home/tic/Escritorio/Proyectos Setas/SIG_SETAS/setasplast-api/resources/views/emails/traslados/pendiente_bodega.blade.php -->
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Traslado Pendiente de Aprobación</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
  
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 20px;">
    <tr>
      <td align="center">
        
        <!-- Container principal -->
        <table width="700" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);overflow:hidden;">
          
          <!-- Header con gradiente -->
          <tr>
            <td style="padding:32px 40px;background:linear-gradient(135deg,#1e293b 0%,#334155 100%);color:#ffffff;">
              <h1 style="margin:0 0 8px 0;font-size:24px;font-weight:600;letter-spacing:-0.025em;">
                Solicitud de Aprobación
              </h1>
              <div style="font-size:16px;opacity:0.9;font-weight:400;">
                Traslado entre Bodegas
              </div>
              <div style="margin-top:12px;padding:8px 16px;background:rgba(255,255,255,0.15);border-radius:6px;display:inline-block;">
                <span style="font-size:14px;font-weight:500;">{{ $traslado->codigo }}</span>
                <span style="opacity:0.8;margin-left:12px;">{{ $traslado->created_at->format('d/m/Y H:i') }}</span>
              </div>
            </td>
          </tr>

          <!-- Saludo -->
          <tr>
            <td style="padding:32px 40px 24px 40px;">
              <h2 style="margin:0 0 16px 0;color:#1f2937;font-size:18px;font-weight:500;">
                Estimado/a {{ $usuario->name ?? 'Responsable' }}
              </h2>
              
              <p style="margin:0 0 24px 0;color:#4b5563;font-size:16px;line-height:1.6;">
                Se requiere su aprobación para procesar el siguiente traslado de productos entre bodegas.
              </p>
            </td>
          </tr>

          <!-- Información del traslado -->
          <tr>
            <td style="padding:0 40px 32px 40px;">
              
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin-bottom:32px;">
                
                <h3 style="margin:0 0 20px 0;color:#1f2937;font-size:16px;font-weight:600;">
                  Información del Traslado
                </h3>
                
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="30%" style="padding:8px 0;vertical-align:top;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Bodega Origen:</span>
                    </td>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#1f2937;font-size:14px;font-weight:600;">
                        {{ $traslado->bodegaOrigen->nombre ?? $traslado->bodega_origen_id }}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Bodega Destino:</span>
                    </td>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#1f2937;font-size:14px;font-weight:600;">
                        {{ $traslado->bodegaDestino->nombre ?? $traslado->bodega_destino_id }}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Solicitado por:</span>
                    </td>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#1f2937;font-size:14px;font-weight:600;">
                        {{ $traslado->creador->name ?? $traslado->usuario_creador_id }}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Estado Actual:</span>
                    </td>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="background:#fef3c7;color:#92400e;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">
                        {{ $traslado->estado }}
                      </span>
                    </td>
                  </tr>
                  @if($traslado->observaciones)
                  <tr>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Observaciones:</span>
                    </td>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#1f2937;font-size:14px;font-style:italic;">
                        {{ $traslado->observaciones }}
                      </span>
                    </td>
                  </tr>
                  @endif
                </table>
                
              </div>

              <!-- Detalle de productos -->
              <h3 style="margin:0 0 16px 0;color:#1f2937;font-size:16px;font-weight:600;">
                Detalle de Productos
              </h3>

              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <thead>
                  <tr style="background:#f1f5f9;">
                    <th style="padding:16px 12px;text-align:center;font-size:14px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">
                      Item
                    </th>
                    <th style="padding:16px 12px;text-align:left;font-size:14px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">
                      Código
                    </th>
                    <th style="padding:16px 12px;text-align:left;font-size:14px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">
                      Producto
                    </th>
                    <th style="padding:16px 12px;text-align:right;font-size:14px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">
                      Cantidad
                    </th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($traslado->detalles as $d)
                    <tr style="background:{{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                      <td style="padding:16px 12px;text-align:center;font-size:14px;color:#64748b;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                      </td>
                      <td style="padding:16px 12px;font-size:14px;color:#1f2937;font-weight:500;font-family:monospace;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ $d->producto->code ?? 'N/A' }}
                      </td>
                      <td style="padding:16px 12px;font-size:14px;color:#1f2937;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ $d->producto->name ?? 'Producto no encontrado' }}
                      </td>
                      <td style="padding:16px 12px;text-align:right;font-size:14px;color:#1f2937;font-weight:600;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ rtrim(rtrim(number_format((float)$d->cantidad, 2, '.', ''), '0'), '.') }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>

            </td>
          </tr>

          <!-- Botones de acción -->
          <tr>
            <td style="padding:32px 40px;">
              
              <div style="text-align:center;margin-bottom:24px;">
                <h3 style="margin:0 0 16px 0;color:#1f2937;font-size:16px;font-weight:600;">
                  ¿Desea aprobar este traslado?
                </h3>
                
                <div style="display:inline-block;">
                  <!-- Botón Aprobar -->
                  <a href="{{ $aprobarUrl }}" 
                     style="display:inline-block;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:16px;font-weight:600;margin:0 8px;box-shadow:0 4px 6px -1px rgba(16,185,129,0.3);transition:all 0.2s;">
                    Aprobar Traslado
                  </a>
                  
                  <!-- Botón Rechazar 
                  <a href="{{ $rechazarUrl }}" 
                     style="display:inline-block;background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:16px;font-weight:600;margin:0 8px;box-shadow:0 4px 6px -1px rgba(239,68,68,0.3);transition:all 0.2s;">
                    Rechazar Traslado
                  </a>-->
                </div>
              </div>

              <!-- Enlace alternativo -->
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;text-align:center;">
                <p style="margin:0 0 8px 0;color:#6b7280;font-size:13px;font-weight:500;">
                  Si los botones no funcionan, puede acceder directamente:
                </p>
                <p style="margin:0;color:#64748b;font-size:12px;font-family:monospace;word-break:break-all;line-height:1.4;">
                  {{ $aprobarUrl }}
                </p>
              </div>

            </td>
          </tr>

          <!-- Footer elegante -->
          <tr>
            <td style="padding:24px 40px;background:#f8fafc;border-top:1px solid #e2e8f0;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="text-align:center;">
                    <p style="margin:0;color:#64748b;font-size:13px;line-height:1.5;">
                      <strong style="color:#475569;">Sistema SIG-SETAS</strong><br>
                      Gestión Integral de Inventarios y Traslados
                    </p>
                    <p style="margin:8px 0 0 0;color:#94a3b8;font-size:11px;">
                      Este es un mensaje automático. No responder a este correo.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>
        
      </td>
    </tr>
  </table>
  
</body>
</html>