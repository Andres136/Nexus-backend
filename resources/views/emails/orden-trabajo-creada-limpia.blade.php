@extends('layouts.email-limpio')

@section('content')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="background:#208040;color:#fff;padding:16px 20px;border-radius:8px;">
      <div style="font-size:18px;font-weight:700;">Nueva Orden de Trabajo</div>
      <div style="font-size:13px;opacity:.95;margin-top:4px;">OT #{{ str_pad($ordenTrabajo->id, 6, '0', STR_PAD_LEFT) }}</div>
    </td>
  </tr>
  <tr><td height="12"></td></tr>
  <tr>
    <td style="background:#fff;border:1px solid #eceff1;border-radius:8px;padding:16px;">
      <table role="presentation" width="100%" style="font-size:14px;color:#2c3e50;">
        <tr><td><strong>👤 Cliente</strong></td><td align="right">{{ $cliente_nombre }}</td></tr>
        <tr><td><strong>🏢 Sede</strong></td><td align="right">{{ $sede_nombre }}</td></tr>
        <tr><td><strong>📅 Entrega</strong></td><td align="right">{{ $fecha_entrega }}</td></tr>
      </table>
      <div style="height:12px;line-height:12px;">&nbsp;</div>
      <a href="{{ $url }}" style="display:block;width:100%;max-width:600px;background:#208040;color:#fff;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:700;text-align:center;">
        Ver Orden de Trabajo
      </a>
    </td>
  </tr>
</table>
@endsection
