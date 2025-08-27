@extends('layouts.email-modern')

@section('content')
@php
    use Carbon\Carbon;

    $appUrl   = rtrim(config('app.frontend_url', config('app.url')), '/');
    $id       = (string)($orden->id ?? 0);
    $numOc    = str_pad($id, 6, '0', STR_PAD_LEFT);
    $entrega  = $orden->fecha_entrega ? Carbon::parse($orden->fecha_entrega) : null;
    $fechaEnt = $entrega ? $entrega->format('d/m/Y') : 'Sin fecha';
    $cliente  = optional($orden->cliente)->nombre ?? 'No especificado';
    $ubic     = $orden->ubicacion_entrega ?? 'No especificada';
    $valor    = is_numeric($orden->valor_total ?? null) ? number_format($orden->valor_total, 0, ',', '.') : '0';
    $altaPri  = ($orden->valor_total ?? 0) > 3000000;
@endphp

<!-- Preheader (oculto en bandeja) -->
<span style="display:none;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
  Nueva Orden de Compra #{{ $numOc }} para {{ $cliente }}
</span>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;">
  <!-- Header -->
  <tr>
    <td style="background:#208040;color:#ffffff;padding:16px 20px;border-radius:8px;">
      <div style="font-size:14px;opacity:.95;margin-bottom:4px;">
        Hola {{ $usuario->name ?? 'equipo' }},
      </div>
      <div style="font-size:20px;font-weight:700;line-height:1.3;">
        📋 Nueva Orden de Compra
      </div>
      <div style="font-size:13px;opacity:.9;margin-top:2px;">
        Se ha generado una nueva orden en el sistema.
      </div>
    </td>
  </tr>

  <tr><td height="12"></td></tr>

  <!-- Tarjeta OC -->
  <tr>
    <td style="background:#ffffff;border:1px solid #eceff1;border-radius:8px;padding:16px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="font-size:18px;font-weight:700;color:#2c3e50;">
            Orden #{{ $numOc }}
          </td>
          <td align="right">
            @if($altaPri)
              <span style="display:inline-block;background:#e74c3c;color:#fff;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                Alta prioridad
              </span>
            @endif
          </td>
        </tr>

        <tr><td colspan="2" height="10"></td></tr>

        <tr>
          <td colspan="2" style="font-size:14px;color:#333;line-height:1.6;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:6px 0;"><strong>👤 Cliente:</strong></td>
                <td style="padding:6px 0;" align="right">{{ $cliente }}</td>
              </tr>
              <tr>
                <td style="padding:6px 0;"><strong>📅 Entrega:</strong></td>
                <td style="padding:6px 0;" align="right">{{ $fechaEnt }}</td>
              </tr>
              <tr>
                <td style="padding:6px 0;"><strong>📍 Ubicación:</strong></td>
                <td style="padding:6px 0;" align="right">{{ $ubic }}</td>
              </tr>
              <tr>
                <td style="padding:10px 0;border-top:1px solid #f0f0f0;"><strong>💰 Valor total:</strong></td>
                <td style="padding:10px 0;border-top:1px solid #f0f0f0;font-weight:700;color:#208040;" align="right">
                  ${{ $valor }}
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr><td colspan="2" height="10"></td></tr>

        <tr>
          <td colspan="2" align="center">
            <!-- Botones full-width en móvil (sin media queries) -->
            <a href="{{ $appUrl }}/auth/crm/ordenes/{{ $orden->id }}"
               style="display:block;width:100%;max-width:600px;background:#208040;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:6px;font-weight:700;text-align:center;">
              Ver detalles
            </a>
            <div style="height:10px;line-height:10px;">&nbsp;</div>
            <a href="{{ $appUrl }}/auth/crm"
               style="display:block;width:100%;max-width:600px;background:#3498db;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:6px;font-weight:600;text-align:center;">
              Ir al dashboard
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr><td height="12"></td></tr>

  <!-- Próximos pasos -->
  <tr>
    <td style="background:#f6faf7;border:1px solid #e3efe6;border-radius:8px;padding:14px;">
      <div style="font-size:16px;color:#208040;font-weight:700;margin-bottom:6px;">📋 Próximos pasos</div>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;color:#2c3e50;">
        <tr><td style="padding:4px 0;">• Revisar especificaciones.</td></tr>
        <tr><td style="padding:4px 0;">• Coordinar con producción.</td></tr>
        <tr><td style="padding:4px 0;">• Confirmar disponibilidad.</td></tr>
      </table>
    </td>
  </tr>
</table>
@endsection
