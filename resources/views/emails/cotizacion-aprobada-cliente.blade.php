@extends('layouts.email-modern')

@section('content')
@php
    $nombreEmpresa = $empresa?->nombre ?? $cotizacion->empresa ?? config('app.name');
@endphp

<span style="display:none;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
  Tu cotización #{{ $cotizacion->id }} está adjunta a este correo.
</span>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="background:linear-gradient(135deg,#087a43 0%,#159253 100%);color:#ffffff;padding:22px;border-radius:8px;">
      <div style="font-size:14px;opacity:.95;margin-bottom:5px;">Hola {{ $cliente->nombre }},</div>
      <div style="font-size:22px;font-weight:700;line-height:1.3;">✅ Tu cotización está lista</div>
      <div style="font-size:14px;opacity:.92;margin-top:6px;">Gracias por confiar en {{ $nombreEmpresa }}.</div>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="background:#f8fffb;border:1px solid #d9f3e4;border-left:5px solid #159253;border-radius:8px;padding:18px;">
      <div style="font-size:16px;font-weight:700;color:#087a43;margin-bottom:12px;">Resumen de la cotización</div>
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:14px;color:#334155;">
        <tr><td style="padding:5px 10px 5px 0;font-weight:600;width:140px;">Número:</td><td>#{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td style="padding:5px 10px 5px 0;font-weight:600;">Empresa:</td><td>{{ $nombreEmpresa }}</td></tr>
        <tr><td style="padding:5px 10px 5px 0;font-weight:600;">Productos:</td><td>{{ $cotizacion->detalles->count() }}</td></tr>
        <tr><td style="padding:5px 10px 5px 0;font-weight:600;">Total con IVA:</td><td style="font-size:17px;font-weight:700;color:#087a43;">$ {{ number_format((float) $cotizacion->valor_total, 0, ',', '.') }}</td></tr>
      </table>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="background:#eff6ff;border-left:4px solid #2563eb;border-radius:6px;padding:13px 15px;color:#1e3a8a;font-size:14px;line-height:1.5;">
      Encontrarás el documento completo en el archivo PDF adjunto. Si necesitas algún ajuste o deseas continuar con el pedido, responde este correo o comunícate con tu asesor comercial.
    </td>
  </tr>

  <tr><td height="18"></td></tr>

  <tr>
    <td style="color:#64748b;font-size:13px;line-height:1.55;text-align:center;">
      Este correo fue generado automáticamente después de la aprobación de tu cotización en Nexus.
    </td>
  </tr>
</table>
@endsection
