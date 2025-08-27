@extends('layouts.email-limpio')

@section('content')
@php
    use Carbon\Carbon;

    $appUrl   = rtrim(config('app.frontend_url', config('app.url')), '/');
    $ocId     = (int)($ordenCompra->id ?? 0);
    $otId     = (int)($ordenTrabajo->id ?? 0);
    $ocNum    = str_pad($ocId, 6, '0', STR_PAD_LEFT);
    $otNum    = str_pad($otId, 6, '0', STR_PAD_LEFT);
    $sede     = optional($ordenCompra->sede)->nombre ?? null;
    $entrega  = $ordenTrabajo->fecha_entrega ? Carbon::parse($ordenTrabajo->fecha_entrega) : null;
    $fechaEnt = $entrega ? $entrega->format('d/m/Y') : 'Por confirmar';
@endphp

<!-- Preheader oculto -->
<span style="display:none;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
  Orden de Trabajo #{{ $otNum }} generada a partir de la OC #{{ $ocNum }}.
</span>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;">
  <!-- Header -->
  <tr>
    <td style="background:#28a745;color:#ffffff;padding:18px 20px;border-radius:8px;">
      <div style="font-size:28px;line-height:1;margin-bottom:8px;">🎉</div>
      <div style="font-size:20px;font-weight:700;line-height:1.3;">Orden de Trabajo Generada</div>
      <div style="font-size:14px;opacity:.95;margin-top:6px;">
        {{ $usuario->name ?? 'equipo' }}, tu orden de compra ya está en producción.
      </div>
    </td>
  </tr>

  <tr><td height="12"></td></tr>

  <!-- Tarjeta principal -->
  <tr>
    <td style="background:#ffffff;border:1px solid #e6f4ea;border-radius:8px;padding:16px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="font-size:16px;font-weight:700;color:#2c3e50;">
            Orden de Compra #{{ $ocNum }}
            <div style="font-size:13px;color:#6c757d;margin-top:2px;">Ahora es Orden de Trabajo #{{ $otNum }}</div>
          </td>
          <td align="right">
            <span style="display:inline-block;background:#28a745;color:#fff;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:700;">
              🏭 En producción
            </span>
          </td>
        </tr>

        <tr><td colspan="2" height="12"></td></tr>

        <!-- Bloque informativo -->
        <tr>
          <td colspan="2" style="background:#d4edda;border:1px solid #c3e6cb;color:#155724;border-radius:6px;padding:12px;text-align:center;">
            <div style="font-size:20px;margin-bottom:6px;">🎯</div>
            <div style="font-size:15px;">
              Tu orden ha sido procesada y <strong>ya está en producción</strong>. El equipo está trabajando en tus requerimientos.
            </div>
          </td>
        </tr>

        <tr><td colspan="2" height="12"></td></tr>

        <!-- Detalles (2 columnas lógicas sin usar grid/flex) -->
        <tr>
          <td colspan="2" style="background:#f8f9fa;border-radius:6px;padding:12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;color:#2c3e50;">
              @if($sede)
              <tr>
                <td style="padding:6px 0;"><strong>🏢 Sede</strong></td>
                <td style="padding:6px 0;" align="right">{{ $sede }}</td>
              </tr>
              @endif
              <tr>
                <td style="padding:6px 0;"><strong>📅 Fecha de entrega</strong></td>
                <td style="padding:6px 0;" align="right">{{ $fechaEnt }}</td>
              </tr>
              <tr>
                <td style="padding:6px 0;"><strong>⚡ Estado</strong></td>
                <td style="padding:6px 0;" align="right">Iniciada</td>
              </tr>
              <tr>
                <td style="padding:6px 0;border-top:1px solid #eee;"><strong>🔧 OT ID</strong></td>
                <td style="padding:6px 0;border-top:1px solid #eee;" align="right">#{{ $otNum }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <tr><td colspan="2" height="12"></td></tr>

        <!-- Próximos pasos -->
        <tr>
          <td colspan="2" style="background:#ffffff;border:1px dashed #c3e6cb;border-radius:6px;padding:12px;">
            <div style="font-size:14px;font-weight:700;color:#2c3e50;margin-bottom:6px;">📋 Próximos pasos</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;color:#555;">
              <tr><td style="padding:4px 0;">• Producción recibió la orden.</td></tr>
              <tr><td style="padding:4px 0;">• Preparación de materiales y recursos.</td></tr>
              <tr><td style="padding:4px 0;">• Notificación cuando esté lista para entrega.</td></tr>
              <tr><td style="padding:4px 0;">• Seguimiento desde tu dashboard.</td></tr>
            </table>
          </td>
        </tr>

        <tr><td colspan="2" height="12"></td></tr>

        <!-- CTAs (full width en móvil sin media queries) -->
        <tr>
          <td colspan="2" align="center">
            <a href="{{ $appUrl }}/auth/crm/ordenes-compra/{{ $ordenCompra->id }}"
               style="display:block;width:100%;max-width:600px;background:#208040;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:700;text-align:center;">
              📊 Ver estado de la orden
            </a>
            <div style="height:10px;line-height:10px;">&nbsp;</div>
            <a href="{{ $appUrl }}/auth/crm/dashboard"
               style="display:block;width:100%;max-width:600px;background:#6c757d;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:600;text-align:center;">
              🏠 Ir al dashboard
            </a>
          </td>
        </tr>

        <tr><td colspan="2" height="12"></td></tr>

        <!-- Cierre -->
        <tr>
          <td colspan="2" style="background:#f1faf4;border-radius:8px;padding:14px;text-align:center;color:#155724;font-size:14px;">
            <div style="font-size:18px;margin-bottom:6px;">💪</div>
            <div><strong>¡Gracias por confiar en nosotros!</strong></div>
            <div style="font-size:13px;margin-top:2px;">Trabajamos para entregar calidad en el tiempo acordado.</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
@endsection
