@extends('layouts.email-modern')

@section('content')
@php
    use Carbon\Carbon;
    $appUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
    $venc = $tarea->fecha_fin ?? null;
    $fechaLegible = $venc ? Carbon::parse($venc)->format('d/m/Y') : 'Sin fecha';
    $humano = $venc ? Carbon::parse($venc)->diffForHumans() : '';
@endphp

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="background:#e6f3ff; padding:20px; border-radius:8px;">
      <h2 style="margin:0 0 8px 0; font-size:22px; color:#208040;">✅ Nueva tarea asignada</h2>
      <p style="margin:0; font-size:16px;">
        Hola {{ $usuario->name ?? 'equipo' }}, tienes una nueva tarea:
        <strong>{{ $tarea->nombre ?? 'Sin título' }}</strong>
      </p>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="background:#ffffff; border:1px solid #e9ecef; border-radius:8px; padding:16px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:6px 0;">
            <strong>Fecha límite:</strong> {{ $fechaLegible }}
            @if($humano) <span style="color:#6c757d;">({{ $humano }})</span> @endif
          </td>
        </tr>

        @if(optional($tarea->cliente)->nombre)
        <tr>
          <td style="padding:6px 0;">
            <strong>Cliente:</strong> {{ $tarea->cliente->nombre }}
          </td>
        </tr>
        @endif

        <tr>
          <td style="padding:6px 0;">
            <strong>Estado:</strong> {{ ucfirst($tarea->estado ?? 'pendiente') }}
          </td>
        </tr>

        @if(!empty($tarea->descripcion))
        <tr>
          <td style="padding:10px; background:#f8f9fa; border-radius:6px; line-height:1.5;">
            <strong>Descripción:</strong>
            <div style="margin-top:6px;">{{ $tarea->descripcion }}</div>
          </td>
        </tr>
        @endif
      </table>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td align="center">
      <!-- Botón principal: ocupa todo el ancho en móvil -->
      <a href="{{ $appUrl }}/auth/crm/tareas/{{ $tarea->id }}"
         style="display:block; max-width:320px; width:100%; background:#208040; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:6px; font-weight:600; text-align:center; margin:0 auto;">
        🚀 Gestionar tarea
      </a>

      <div style="height:10px; line-height:10px;">&nbsp;</div>

      <a href="{{ $appUrl }}/auth/crm/tareas"
         style="display:block; max-width:320px; width:100%; background:#3498db; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:6px; font-weight:600; text-align:center; margin:0 auto;">
        📋 Ver todas
      </a>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <tr>
    <td style="background:#e8f5e8; padding:12px 16px; border-radius:8px; color:#208040; font-size:14px; line-height:1.5;">
      <strong>Tip:</strong> Trabaja en bloques sin interrupciones y actualiza el estado con cada avance.
    </td>
  </tr>
</table>
@endsection
