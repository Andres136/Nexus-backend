@extends('layouts.email-limpio')

@section('content')
@php
    $tieneVencida = !empty($resumen['tiene_vencida']);
    $tieneProxima = !empty($resumen['tiene_proxima']);
    $tituloRecordatorio = match (true) {
        $tieneVencida && $tieneProxima => 'Recordatorio: tienes facturas vencidas y próximas a vencer',
        $tieneVencida => 'Recordatorio: tienes facturas vencidas',
        default => 'Recordatorio: facturas próximas a vencer',
    };
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="background:{{ $tieneVencida ? '#dc2626' : '#f59e0b' }};color:#ffffff;padding:18px 20px;border-radius:8px;">
      <div style="font-size:28px;line-height:1;margin-bottom:8px;">{{ $tieneVencida ? '🚨' : '⚠️' }}</div>
      <div style="font-size:20px;font-weight:700;line-height:1.3;">
        {{ $tituloRecordatorio }}
      </div>
      <div style="font-size:14px;opacity:.95;margin-top:6px;">
        Hola {{ $cliente->nombre ?? 'cliente' }}, este es un recordatorio periódico del estado de tu cartera con nosotros.
      </div>
    </td>
  </tr>

  <tr><td height="12"></td></tr>

  <tr>
    <td style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        @if($tieneVencida)
        <tr>
          <td style="background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;padding:12px;">
            <div style="font-size:14px;font-weight:700;color:#92400e;margin-bottom:6px;">
              Facturas vencidas
            </div>
            <div style="font-size:13px;color:#7c2d12;">
              {{ collect($resumen['facturas_vencidas'] ?? [])->join(', ') }}
            </div>
            <div style="font-size:13px;color:#7c2d12;margin-top:4px;">
              Total vencido: <strong>${{ number_format($resumen['total_vencido'] ?? 0, 0, ',', '.') }}</strong>
            </div>
          </td>
        </tr>
        <tr><td height="10"></td></tr>
        @endif

        @if(!empty($resumen['tiene_proxima']))
        <tr>
          <td style="background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:12px;">
            <div style="font-size:14px;font-weight:700;color:#92400e;margin-bottom:6px;">
              Facturas próximas a vencer
            </div>
            <div style="font-size:13px;color:#78350f;">
              {{ collect($resumen['facturas_proximas'] ?? [])->join(', ') }}
            </div>
            <div style="font-size:13px;color:#78350f;margin-top:4px;">
              Total próximo: <strong>${{ number_format($resumen['total_proximo'] ?? 0, 0, ',', '.') }}</strong>
            </div>
          </td>
        </tr>
        <tr><td height="10"></td></tr>
        @endif

        <tr>
          <td style="font-size:0.95rem;color:#475569;padding-top:4px;">
            Te invitamos a ponerte al día con tu asesor comercial. Si ya realizaste algún pago reciente,
            puedes ignorar este recordatorio.
          </td>
        </tr>

        <tr><td height="14"></td></tr>

        <tr>
          <td style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center;color:#334155;font-size:14px;">
            <div><strong>¡Gracias por confiar en nosotros!</strong></div>
            <div style="font-size:13px;margin-top:2px;">Cualquier duda, tu asesor comercial está atento para ayudarte.</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
@endsection
