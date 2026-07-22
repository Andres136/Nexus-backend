@extends('layouts.email-limpio')

@section('content')
@php
    $ocId = (int) ($ordenCompra->id ?? 0);
    $ocNum = str_pad($ocId, 6, '0', STR_PAD_LEFT);
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="background:#2563eb;color:#ffffff;padding:18px 20px;border-radius:8px;">
      <div style="font-size:28px;line-height:1;margin-bottom:8px;">📦</div>
      <div style="font-size:20px;font-weight:700;line-height:1.3;">Tu pedido está en alistamiento</div>
      <div style="font-size:14px;opacity:.95;margin-top:6px;">
        Hola {{ $cliente->nombre ?? 'cliente' }}, tu Orden de Compra #{{ $ocNum }} pasó a la fase de alistamiento.
      </div>
    </td>
  </tr>

  <tr><td height="12"></td></tr>

  <tr>
    <td style="background:#ffffff;border:1px solid #dbeafe;border-radius:8px;padding:16px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td colspan="2" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:6px;padding:12px;text-align:center;">
            <div style="font-size:15px;">
              Estamos preparando tu pedido para el despacho.
            </div>
          </td>
        </tr>

        @if(!empty($carteraInfo))
        <tr><td colspan="2" height="12"></td></tr>
        <tr>
          <td colspan="2" style="background:{{ !empty($carteraInfo['tiene_vencida']) ? '#fff7ed' : '#fffbeb' }};border:1px solid {{ !empty($carteraInfo['tiene_vencida']) ? '#fed7aa' : '#fde68a' }};border-radius:6px;padding:12px;">
            <div style="font-size:14px;font-weight:700;color:#92400e;margin-bottom:6px;">
              {{ !empty($carteraInfo['tiene_vencida']) ? '🚨 Tienes facturas vencidas' : '⚠️ Tienes facturas próximas a vencer' }}
            </div>
            @if(!empty($carteraInfo['tiene_vencida']))
            <div style="font-size:13px;color:#7c2d12;padding:2px 0;">
              Facturas vencidas: {{ $carteraInfo['facturas_vencidas']->join(', ') }} — total ${{ number_format($carteraInfo['total_vencido'], 0, ',', '.') }}
            </div>
            @endif
            @if(!empty($carteraInfo['tiene_proxima']))
            <div style="font-size:13px;color:#78350f;padding:2px 0;">
              Facturas próximas a vencer: {{ $carteraInfo['facturas_proximas']->join(', ') }} — total ${{ number_format($carteraInfo['total_proximo'], 0, ',', '.') }}
            </div>
            @endif
            <div style="font-size:13px;color:#92400e;margin-top:6px;">
              Para que el despacho de tu pedido no se vea afectado, te invitamos a gestionar tu cartera pendiente cuanto antes con tu asesor comercial.
            </div>
          </td>
        </tr>
        @endif

        <tr><td colspan="2" height="12"></td></tr>

        <tr>
          <td colspan="2" style="background:#eff6ff;border-radius:8px;padding:14px;text-align:center;color:#1d4ed8;font-size:14px;">
            <div><strong>¡Gracias por confiar en nosotros!</strong></div>
            <div style="font-size:13px;margin-top:2px;">Cualquier duda, tu asesor comercial está atento para ayudarte.</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
@endsection
