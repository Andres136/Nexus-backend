@extends('layouts.email-modern')

@section('content')
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $appUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
    $total  = $tareasVencidas->count();
    $items  = $tareasVencidas->take(3);
@endphp

<!-- Preheader (aparece en la bandeja, oculto en el cuerpo) -->
<span style="display:none;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
  Tienes {{ $total }} {{ $total===1 ? 'tarea vencida' : 'tareas vencidas' }} pendientes.
</span>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;">
  <!-- Encabezado -->
  <tr>
    <td style="background:#c62828;color:#ffffff;padding:16px 20px;border-radius:8px;">
      <div style="font-size:14px;opacity:.95;margin-bottom:4px;">
        {{ \App\Services\EmailPersonalizationService::getSaludo() }}, {{ $usuario->name ?? 'equipo' }}
      </div>
      <div style="font-size:20px;font-weight:700;line-height:1.3;">
        🚨 Tareas vencidas detectadas ({{ $total }})
      </div>
      <div style="font-size:13px;opacity:.9;margin-top:2px;">
        Actúa ahora para evitar acumulación.
      </div>
    </td>
  </tr>

  <tr><td height="12"></td></tr>

  <!-- Listado (máx. 3) -->
  @foreach($items as $tarea)
    @php
      $v = $tarea->fecha_fin ? Carbon::parse($tarea->fecha_fin) : null;
    @endphp
    <tr>
      <td style="background:#ffffff;border:1px solid #eceff1;border-left:4px solid #e53935;border-radius:6px;padding:14px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="font-weight:600;font-size:16px;color:#2c3e50;">
              {{ $tarea->nombre ?? 'Tarea sin título' }}
            </td>
            <td align="right" style="font-size:12px;color:#e53935;white-space:nowrap;">
              🔥 Vencida
            </td>
          </tr>

          <tr>
            <td colspan="2" style="font-size:14px;padding-top:6px;color:#333;">
              <strong>Fecha límite:</strong> {{ $v ? $v->format('d/m/Y H:i') : 'Sin fecha' }}
              @if($v)<span style="color:#777;"> ({{ $v->diffForHumans() }})</span>@endif
            </td>
          </tr>

          @if(optional($tarea->cliente)->nombre)
          <tr>
            <td colspan="2" style="font-size:14px;padding-top:4px;color:#333;">
              <strong>Cliente:</strong> {{ $tarea->cliente->nombre }}
            </td>
          </tr>
          @endif

          @if(!empty($tarea->descripcion))
          <tr>
            <td colspan="2" style="font-size:14px;padding:10px;margin-top:6px;background:#f8f9fa;border-radius:4px;color:#555;">
              {{ Str::limit($tarea->descripcion, 140) }}
            </td>
          </tr>
          @endif

          <tr>
            <td colspan="2" align="left" style="padding-top:10px;">
              <a href="{{ $appUrl }}/auth/crm/tareas/{{ $tarea->id }}"
                 style="display:inline-block;background:#208040;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:6px;font-weight:600;">
                Abrir tarea
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr><td height="10"></td></tr>
  @endforeach

  @if($total > 3)
  <tr>
    <td style="text-align:center;background:#fffbe6;border:1px solid #ffe58f;border-radius:6px;padding:12px;font-size:14px;">
      Y {{ $total - 3 }} {{ $total - 3 === 1 ? 'tarea más' : 'tareas más' }} requieren tu atención.
    </td>
  </tr>
  <tr><td height="12"></td></tr>
  @endif

  <!-- CTAs (se apilan en móvil) -->
  <tr>
    <td align="center">
   
      <div style="height:10px;line-height:10px;">&nbsp;</div>
      <a href="{{ $appUrl }}/auth/tareas"
         style="display:block;width:100%;max-width:600px;background:#3498db;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:600;text-align:center;">
        Ir al dashboard
      </a>
    </td>
  </tr>

  <tr><td height="16"></td></tr>

  <!-- Plan de acción (compacto) -->
  <tr>
    <td style="background:#f1f8e9;border-radius:6px;padding:14px;color:#2e7d32;font-size:14px;line-height:1.5;">
      <strong>Plan de acción:</strong> Revisa las críticas, comunica a clientes, completa 2 hoy y reprograma la semana.
    </td>
  </tr>
</table>
@endsection
