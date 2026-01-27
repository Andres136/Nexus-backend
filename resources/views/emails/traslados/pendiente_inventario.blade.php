<!-- filepath: /home/tic/Escritorio/Proyectos Setas/SIG_SETAS/setasplast-api/resources/views/emails/traslados/pendiente_inventario.blade.php -->
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Aprobación de Inventario Requerida</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
  
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 20px;">
    <tr>
      <td align="center">
        
        <!-- Container principal -->
        <table width="700" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);overflow:hidden;">
          
          <!-- Header con estado inventario -->
          <tr>
            <td style="padding:32px 40px;background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);color:#ffffff;">
              
              <!-- Icono de inventario (sin usar iconos reales) -->
              <div style="width:80px;height:80px;background:rgba(255,255,255,0.15);border-radius:50%;margin:0 auto 16px auto;position:relative;border:3px solid rgba(255,255,255,0.2);">
                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">
                  <div style="width:32px;height:24px;border:3px solid #ffffff;border-radius:4px;position:relative;">
                    <div style="position:absolute;top:-8px;left:8px;right:8px;height:8px;background:rgba(255,255,255,0.3);border-radius:2px 2px 0 0;"></div>
                    <div style="position:absolute;top:4px;left:4px;right:4px;bottom:4px;">
                      <div style="width:100%;height:2px;background:#ffffff;margin-bottom:2px;"></div>
                      <div style="width:80%;height:2px;background:#ffffff;margin-bottom:2px;"></div>
                      <div style="width:60%;height:2px;background:#ffffff;"></div>
                    </div>
                  </div>
                </div>
              </div>
              
              <h1 style="margin:0 0 8px 0;font-size:24px;font-weight:600;letter-spacing:-0.025em;text-align:center;">
                Aprobación de Inventario
              </h1>
              <div style="font-size:16px;opacity:0.9;font-weight:400;text-align:center;">
                Traslado Pendiente de Validación
              </div>
              <div style="margin-top:12px;padding:8px 16px;background:rgba(255,255,255,0.15);border-radius:6px;display:inline-block;">
                <span style="font-size:14px;font-weight:500;">{{ $traslado->codigo }}</span>
                <span style="opacity:0.8;margin-left:12px;">{{ $traslado->created_at->format('d/m/Y H:i') }}</span>
              </div>
            </td>
          </tr>

          <!-- Saludo y contexto -->
          <tr>
            <td style="padding:32px 40px 24px 40px;">
              <h2 style="margin:0 0 16px 0;color:#1f2937;font-size:18px;font-weight:500;">
                Estimado/a Responsable de Inventario
              </h2>
              
              <p style="margin:0 0 24px 0;color:#4b5563;font-size:16px;line-height:1.6;">
                Un traslado ha sido <strong>aprobado por bodega</strong> y ahora requiere su validación desde el área de inventario para completar el proceso.
              </p>
            </td>
          </tr>

          <!-- Estado actual destacado -->
          <tr>
            <td style="padding:0 40px 24px 40px;">
              
              <div style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%);border:1px solid #93c5fd;border-radius:12px;padding:24px;text-align:center;">
                
                <div style="background:#3b82f6;color:#ffffff;padding:10px 24px;border-radius:20px;display:inline-block;font-size:14px;font-weight:600;margin-bottom:12px;">
                  PENDIENTE DE INVENTARIO
                </div>
                
                <p style="margin:0;color:#1e40af;font-size:14px;line-height:1.5;">
                  El traslado ya fue aprobado por bodega y está listo para su validación final.
                </p>
                
              </div>

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
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Código:</span>
                    </td>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#1f2937;font-size:14px;font-weight:700;font-family:monospace;">
                        {{ $traslado->codigo }}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:8px 0;vertical-align:top;">
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
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Aprobado por Bodega:</span>
                    </td>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#10b981;font-size:14px;font-weight:600;">
                        {{ $traslado->aprobadorBodega->name ?? 'Usuario de bodega' }}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#6b7280;font-size:14px;font-weight:500;">Total de Productos:</span>
                    </td>
                    <td style="padding:8px 0;vertical-align:top;">
                      <span style="color:#1f2937;font-size:14px;font-weight:600;">
                        {{ $traslado->detalles->count() }} items
                      </span>
                    </td>
                  </tr>
                </table>
                
              </div>

              <!-- Detalle de productos -->
              <h3 style="margin:0 0 16px 0;color:#1f2937;font-size:16px;font-weight:600;">
                Productos a Trasladar
              </h3>

              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <thead>
                  <tr style="background:#f1f5f9;">
                    <th style="padding:16px 12px;text-align:center;font-size:14px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">
                      #
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
                  @foreach($traslado->detalles as $detalle)
                    <tr style="background:{{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                      <td style="padding:16px 12px;text-align:center;font-size:14px;color:#64748b;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                      </td>
                      <td style="padding:16px 12px;font-size:14px;color:#1f2937;font-weight:500;font-family:monospace;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ $detalle->producto->code ?? 'N/A' }}
                      </td>
                      <td style="padding:16px 12px;font-size:14px;color:#1f2937;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ $detalle->producto->name ?? $detalle->producto->nombre ?? 'Producto no encontrado' }}
                      </td>
                      <td style="padding:16px 12px;text-align:right;font-size:14px;color:#1f2937;font-weight:600;border-bottom:{{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                        {{ rtrim(rtrim(number_format((float)$detalle->cantidad, 2, '.', ''), '0'), '.') }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>

            </td>
          </tr>

          <!-- Acción requerida -->
          <tr>
            <td style="padding:32px 40px;">
              
              <div style="background:linear-gradient(135deg,#fff7ed 0%,#fed7aa 100%);border:1px solid #fdba74;border-radius:12px;padding:24px;text-align:center;margin-bottom:32px;">
                
                <h3 style="margin:0 0 12px 0;color:#ea580c;font-size:16px;font-weight:600;">
                  Acción Requerida
                </h3>
                
                <p style="margin:0 0 20px 0;color:#c2410c;font-size:14px;line-height:1.5;">
                  Por favor, revise los productos y cantidades. Una vez validado el inventario disponible, proceda con la aprobación del traslado.
                </p>
                
                <!-- Botón principal -->
                <a href="{{ $aprobarUrl }}" 
                   style="display:inline-block;background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:8px;font-size:16px;font-weight:600;box-shadow:0 4px 6px -1px rgba(59,130,246,0.3);transition:all 0.2s;">
                  Aprobar Traslado de Inventario
                </a>
                
              </div>

              <!-- Enlace alternativo -->
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;text-align:center;">
                <p style="margin:0 0 8px 0;color:#6b7280;font-size:13px;font-weight:500;">
                  Si el botón no funciona, puede acceder directamente:
                </p>
                <p style="margin:0;color:#64748b;font-size:12px;font-family:monospace;word-break:break-all;line-height:1.4;">
                  {{ $aprobarUrl }}
                </p>
              </div>

            </td>
          </tr>

          <!-- Footer -->
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