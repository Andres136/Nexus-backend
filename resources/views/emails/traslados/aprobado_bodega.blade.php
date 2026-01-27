<!-- filepath: /home/tic/Escritorio/Proyectos Setas/SIG_SETAS/setasplast-api/resources/views/emails/traslados/aprobado_bodega.blade.php -->
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Traslado Aprobado por Bodega</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
  
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 20px;">
    <tr>
      <td align="center">
        
        <!-- Container principal -->
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);overflow:hidden;">
          
          <!-- Header con estado aprobado -->
          <tr>
            <td style="padding:32px 40px;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:#ffffff;text-align:center;">
              
              <!-- Check mark visual -->
              <div style="width:80px;height:80px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px auto;display:flex;align-items:center;justify-content:center;border:3px solid rgba(255,255,255,0.3);">
                <div style="width:24px;height:12px;border-left:4px solid #ffffff;border-bottom:4px solid #ffffff;transform:rotate(-45deg);margin-top:-6px;"></div>
              </div>
              
              <h1 style="margin:0 0 8px 0;font-size:26px;font-weight:700;letter-spacing:-0.025em;">
                Traslado Aprobado
              </h1>
              <div style="font-size:16px;opacity:0.95;font-weight:500;">
                Aprobación Exitosa por Bodega
              </div>
              
            </td>
          </tr>

          <!-- Estado y código destacado -->
          <tr>
            <td style="padding:32px 40px 24px 40px;text-align:center;">
              
              <div style="background:linear-gradient(135deg,#dcfce7 0%,#bbf7d0 100%);border:1px solid #a7f3d0;border-radius:12px;padding:24px;margin-bottom:32px;">
                
                <h2 style="margin:0 0 8px 0;color:#065f46;font-size:20px;font-weight:600;">
                  {{ $traslado->codigo }}
                </h2>
                
                <div style="background:#10b981;color:#ffffff;padding:8px 20px;border-radius:20px;display:inline-block;font-size:14px;font-weight:600;margin-bottom:16px;">
                  {{ $traslado->estado }}
                </div>
                
                <p style="margin:0;color:#047857;font-size:16px;line-height:1.5;">
                  Su solicitud de traslado ha sido <strong>aprobada exitosamente</strong> por el responsable de bodega.
                </p>
                
              </div>

            </td>
          </tr>

          <!-- Información del traslado -->
          <tr>
            <td style="padding:0 40px 32px 40px;">
              
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;">
                
                <h3 style="margin:0 0 20px 0;color:#1f2937;font-size:16px;font-weight:600;text-align:center;">
                  Detalles del Traslado
                </h3>
                
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                  <tr>
                    <td width="40%" style="padding:12px 0;vertical-align:top;border-bottom:1px solid #f1f5f9;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Código de Traslado:</span>
                    </td>
                    <td style="padding:12px 0;vertical-align:top;border-bottom:1px solid #f1f5f9;">
                      <span style="color:#1f2937;font-size:14px;font-weight:700;font-family:monospace;">
                        {{ $traslado->codigo }}
                      </span>
                    </td>
                  </tr>
                  
                  <tr>
                    <td style="padding:12px 0;vertical-align:top;border-bottom:1px solid #f1f5f9;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Bodega Origen:</span>
                    </td>
                    <td style="padding:12px 0;vertical-align:top;border-bottom:1px solid #f1f5f9;">
                      <span style="color:#1f2937;font-size:14px;font-weight:600;">
                        {{ $traslado->bodegaOrigen->nombre ?? 'No disponible' }}
                      </span>
                    </td>
                  </tr>
                  
                  <tr>
                    <td style="padding:12px 0;vertical-align:top;border-bottom:1px solid #f1f5f9;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Bodega Destino:</span>
                    </td>
                    <td style="padding:12px 0;vertical-align:top;border-bottom:1px solid #f1f5f9;">
                      <span style="color:#1f2937;font-size:14px;font-weight:600;">
                        {{ $traslado->bodegaDestino->nombre ?? 'No disponible' }}
                      </span>
                    </td>
                  </tr>
                  
                  <tr>
                    <td style="padding:12px 0;vertical-align:top;border-bottom:1px solid #f1f5f9;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Aprobado por:</span>
                    </td>
                    <td style="padding:12px 0;vertical-align:top;border-bottom:1px solid #f1f5f9;">
                      <span style="color:#1f2937;font-size:14px;font-weight:600;">
                        {{ $traslado->usuarioAprobadorBodega->name ?? 'Usuario de bodega' }}
                      </span>
                    </td>
                  </tr>
                  
                  <tr>
                    <td style="padding:12px 0;vertical-align:top;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Fecha de Aprobación:</span>
                    </td>
                    <td style="padding:12px 0;vertical-align:top;">
                      <span style="color:#1f2937;font-size:14px;font-weight:600;">
                        {{ $traslado->updated_at->format('d/m/Y H:i') }}
                      </span>
                    </td>
                  </tr>
                </table>
                
              </div>

            </td>
          </tr>

          <!-- Próximos pasos -->
          <tr>
            <td style="padding:0 40px 32px 40px;">
              
              <div style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%);border:1px solid #93c5fd;border-radius:10px;padding:24px;">
                
                <h3 style="margin:0 0 16px 0;color:#1d4ed8;font-size:16px;font-weight:600;text-align:center;">
                  Próximos Pasos
                </h3>
                
                <div style="text-align:center;">
                  
                  <div style="background:#3b82f6;color:#ffffff;padding:12px 24px;border-radius:8px;display:inline-block;margin-bottom:16px;">
                    <span style="font-size:14px;font-weight:600;">PENDIENTE DE INVENTARIO</span>
                  </div>
                  
                  <p style="margin:0;color:#1e40af;font-size:14px;line-height:1.6;">
                    Su traslado ahora requiere aprobación del área de <strong>inventario</strong>.<br>
                    Recibirá una notificación cuando el proceso se complete.
                  </p>
                  
                </div>
                
              </div>

            </td>
          </tr>

          <!-- Resumen de productos (si tiene detalles) -->
          @if($traslado->detalles && $traslado->detalles->count() > 0)
          <tr>
            <td style="padding:0 40px 32px 40px;">
              
              <h3 style="margin:0 0 16px 0;color:#1f2937;font-size:16px;font-weight:600;">
                Productos Trasladados ({{ $traslado->detalles->count() }} items)
              </h3>

              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <thead>
                  <tr style="background:#f8fafc;">
                    <th style="padding:12px;text-align:left;font-size:13px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">
                      Producto
                    </th>
                    <th style="padding:12px;text-align:right;font-size:13px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">
                      Cantidad
                    </th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($traslado->detalles->take(5) as $d)
                    <tr style="background:{{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                      <td style="padding:12px;font-size:13px;color:#1f2937;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        <div style="font-weight:600;">{{ $d->producto->name ?? 'Producto no encontrado' }}</div>
                        <div style="font-size:11px;color:#6b7280;font-family:monospace;">{{ $d->producto->code ?? 'N/A' }}</div>
                      </td>
                      <td style="padding:12px;text-align:right;font-size:13px;color:#1f2937;font-weight:600;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ rtrim(rtrim(number_format((float)$d->cantidad, 2, '.', ''), '0'), '.') }}
                      </td>
                    </tr>
                  @endforeach
                  
                  @if($traslado->detalles->count() > 5)
                    <tr>
                      <td colspan="2" style="padding:12px;text-align:center;font-size:13px;color:#6b7280;font-style:italic;">
                        ... y {{ $traslado->detalles->count() - 5 }} productos más
                      </td>
                    </tr>
                  @endif
                </tbody>
              </table>

            </td>
          </tr>
          @endif

          <!-- Footer -->
          <tr>
            <td style="padding:24px 40px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">
              
              <p style="margin:0 0 8px 0;color:#475569;font-size:13px;font-weight:600;">
           NEXUS
              </p>
              <p style="margin:0;color:#94a3b8;font-size:11px;">
               Sistema de Gestión Integral de Inventarios y Traslados<br>
                Este es un mensaje automático. No responder a este correo.
              </p>
              
            </td>
          </tr>

        </table>
        
      </td>
    </tr>
  </table>
  
</body>
</html>