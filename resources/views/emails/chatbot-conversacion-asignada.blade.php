@extends('layouts.email-modern')

@section('content')
@php
    use Illuminate\Support\Str;

    $nombreVisitante = $conversacion->nombre_lead ?: 'Visitante sin identificar';
    $datosContacto = array_filter([
        'Correo' => $conversacion->email_lead,
        'Teléfono' => $conversacion->telefono_lead,
        'Empresa' => $conversacion->empresa_lead,
    ]);
@endphp

<span style="display:none;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
  Tienes una nueva conversación comercial asignada en Nexus.
</span>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="background:linear-gradient(135deg,#087a43 0%,#159253 100%);color:#ffffff;padding:20px;border-radius:8px;">
      <div style="font-size:14px;opacity:.95;margin-bottom:5px;">
        Hola {{ $usuario->name ?? 'equipo' }},
      </div>
      <div style="font-size:22px;font-weight:700;line-height:1.3;">
        💬 Nueva conversación asignada
      </div>
      <div style="font-size:14px;opacity:.92;margin-top:5px;">
        Un visitante necesita tu atención desde el chatbot comercial.
      </div>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="background:#f8fffb;border:1px solid #d9f3e4;border-left:5px solid #159253;border-radius:8px;padding:18px;">
      <div style="font-size:16px;font-weight:700;color:#087a43;margin-bottom:12px;">
        👤 Datos del visitante
      </div>
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:14px;color:#334155;">
        <tr>
          <td style="padding:5px 10px 5px 0;font-weight:600;width:110px;">Nombre:</td>
          <td style="padding:5px 0;">{{ $nombreVisitante }}</td>
        </tr>
        @foreach($datosContacto as $etiqueta => $valor)
        <tr>
          <td style="padding:5px 10px 5px 0;font-weight:600;">{{ $etiqueta }}:</td>
          <td style="padding:5px 0;">{{ $valor }}</td>
        </tr>
        @endforeach
        <tr>
          <td style="padding:5px 10px 5px 0;font-weight:600;">Origen:</td>
          <td style="padding:5px 0;">{{ $conversacion->dominio ?: 'Sitio web' }}</td>
        </tr>
      </table>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="font-size:16px;font-weight:700;color:#1f2937;padding-bottom:10px;">
      Últimos mensajes
    </td>
  </tr>

  @forelse($mensajes as $mensaje)
  <tr>
    <td style="padding:0 0 8px;">
      <div style="background:{{ $mensaje->remitente === 'lead' ? '#ecfdf3' : '#f8fafc' }};border:1px solid {{ $mensaje->remitente === 'lead' ? '#bbebcf' : '#e5e7eb' }};border-radius:7px;padding:11px 13px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:{{ $mensaje->remitente === 'lead' ? '#087a43' : '#64748b' }};margin-bottom:3px;">
          {{ $mensaje->remitente === 'lead' ? $nombreVisitante : ($mensaje->remitente === 'bot' ? 'Asistente virtual' : 'Asesor') }}
        </div>
        <div style="font-size:14px;line-height:1.45;color:#334155;">
          {{ Str::limit($mensaje->contenido, 280) }}
        </div>
      </div>
    </td>
  </tr>
  @empty
  <tr>
    <td style="background:#f8fafc;border-radius:7px;padding:12px;color:#64748b;font-size:14px;">
      Aún no hay mensajes en la conversación.
    </td>
  </tr>
  @endforelse

  <tr><td height="18"></td></tr>

  <tr>
    <td align="center">
      <a href="{{ $url }}"
         style="display:block;width:100%;max-width:360px;background:#159253;color:#ffffff;text-decoration:none;padding:13px 22px;border-radius:7px;font-weight:700;text-align:center;font-size:15px;">
        Abrir y responder conversación
      </a>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="background:#fff8e1;border-left:4px solid #f59e0b;border-radius:6px;padding:12px 14px;color:#7c5700;font-size:13px;line-height:1.5;">
      <strong>Acción recomendada:</strong> responde lo antes posible. Al tomar la conversación, la IA dejará de intervenir y el visitante verá tu nombre como asesor.
    </td>
  </tr>
</table>
@endsection
