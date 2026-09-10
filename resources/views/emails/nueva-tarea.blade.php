@extends('layouts.email-modern')

@section('content')
@php
    use Carbon\Carbon;
    $appUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
    $venc = $tarea->fecha_fin ?? null;
    $fechaLegible = $venc ? Carbon::parse($venc)->setTimezone('America/Bogota')->format('d/m/Y H:i') : 'Sin fecha';
    $humano = $venc ? Carbon::parse($venc)->setTimezone('America/Bogota')->diffForHumans() : '';
@endphp

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="padding-bottom:12px;">
      <h2 style="margin:0 0 4px 0; font-size:18px; color:#1f2937;">Nueva tarea asignada</h2>
      <p style="margin:0; font-size:14px; color:#374151;">
        Hola {{ $usuario->name ?? 'equipo' }}, tienes una nueva tarea:
        <strong>{{ $tarea->nombre ?? 'Sin título' }}</strong>
      </p>
    </td>
  </tr>

  <tr>
    <td style="border:1px solid #e5e7eb; border-radius:8px; padding:14px; font-size:14px; color:#374151;">
      <p style="margin:0 0 6px 0;">
        <strong>Fecha límite:</strong> {{ $fechaLegible }}
        @if($humano) <span style="color:#6b7280;">({{ $humano }})</span> @endif
      </p>

      @if(optional($tarea->cliente)->nombre)
      <p style="margin:0 0 6px 0;"><strong>Cliente:</strong> {{ $tarea->cliente->nombre }}</p>
      @endif

      <p style="margin:0; @if(!empty($tarea->descripcion)) margin-bottom:6px; @endif"><strong>Estado:</strong> {{ ucfirst($tarea->estado ?? 'pendiente') }}</p>

      @if(!empty($tarea->descripcion))
      <p style="margin:0;"><strong>Descripción:</strong> {{ $tarea->descripcion }}</p>
      @endif
    </td>
  </tr>

  <tr><td height="14"></td></tr>

  <tr>
    <td align="center">
      <a href="{{ $appUrl }}/auth/tareas"
         style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; padding:10px 22px; border-radius:6px; font-weight:600; font-size:14px;">
        Ver todas las tareas
      </a>
    </td>
  </tr>
</table>
@endsection
