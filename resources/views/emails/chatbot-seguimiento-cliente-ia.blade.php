@extends('layouts.email-modern')

@section('content')
@php
  $avatarSrc = null;
  if (!empty($avatarBot)) {
      if (str_starts_with($avatarBot, 'http://') || str_starts_with($avatarBot, 'https://')) {
          $avatarSrc = $avatarBot;
      } else {
          $avatarPath = storage_path('app/public/' . ltrim($avatarBot, '/'));
          if (file_exists($avatarPath)) $avatarSrc = $message->embed($avatarPath);
      }
  }
@endphp
<span style="display:none;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
  Queremos saber cómo podemos ayudarte.
</span>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="background:linear-gradient(135deg,#087a43 0%,#159253 100%);color:#ffffff;padding:22px;border-radius:8px;">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
          @if($avatarSrc)
          <td width="76" style="width:76px;padding-right:14px;vertical-align:middle;">
            <img src="{{ $avatarSrc }}" alt="{{ $nombreBot }}" width="64" height="64"
                 style="display:block;width:64px;height:64px;object-fit:cover;border-radius:50%;border:3px solid rgba(255,255,255,.85);background:#ffffff;">
          </td>
          @endif
          <td style="vertical-align:middle;">
            <div style="font-size:14px;opacity:.95;margin-bottom:5px;">Hola {{ $cliente->nombre }},</div>
            <div style="font-size:22px;font-weight:700;line-height:1.3;">Nos alegra retomar el contacto</div>
            <div style="font-size:12px;opacity:.9;margin-top:5px;">{{ $nombreBot }} · Equipo comercial</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="border-radius:8px;overflow:hidden;background:#f1f5f9;">
      <img src="{{ $message->embed(public_path('images/chatbot/gestion-clientes-ia.png')) }}"
           alt="Soluciones de empaque para apoyar el crecimiento de tu negocio"
           width="600"
           style="display:block;width:100%;max-width:600px;height:auto;border:0;">
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:20px;color:#334155;font-size:15px;line-height:1.65;white-space:pre-line;">
      {{ $mensaje }}
    </td>
  </tr>

  @if($asesor)
  <tr><td height="16"></td></tr>
  <tr>
    <td style="background:#f8fffb;border-left:4px solid #159253;border-radius:6px;padding:12px 14px;color:#166534;font-size:13px;line-height:1.5;">
      Tu asesor comercial es <strong>{{ $asesor->nombre_completo ?: $asesor->name }}</strong>. Puedes responder directamente a este correo para continuar la conversación.
    </td>
  </tr>
  @endif

  <tr><td height="16"></td></tr>
  <tr><td style="color:#94a3b8;font-size:12px;text-align:center;">Mensaje de seguimiento comercial enviado desde Nexus.</td></tr>
</table>
@endsection
