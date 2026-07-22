@extends('layouts.email-limpio')

@section('content')
@php
    $ocNum = str_pad((int) ($ordenCompra->id ?? 0), 6, '0', STR_PAD_LEFT);
    $clienteNombre = optional($ordenCompra->cliente)->nombre ?? 'el cliente';
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;">
  <tr>
    <td style="background:#f59e0b;color:#ffffff;padding:18px 20px;border-radius:8px;">
      <div style="font-size:28px;line-height:1;margin-bottom:8px;">⚠️</div>
      <div style="font-size:20px;font-weight:700;line-height:1.3;">Tu orden pasó a alistamiento</div>
      <div style="font-size:14px;opacity:.95;margin-top:6px;">
        {{ $usuario->name ?? 'equipo' }}, tu Orden de Compra #{{ $ocNum }} ya está en alistamiento, pero {{ $clienteNombre }} tiene cartera pendiente.
      </div>
    </td>
  </tr>

  <tr><td height="12"></td></tr>

  <tr>
    <td style="background:#ffffff;border:1px solid #fde68a;border-radius:8px;padding:16px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td colspan="2" style="background:{{ !empty($carteraInfo['tiene_vencida']) ? '#fff7ed' : '#fffbeb' }};border:1px solid {{ !empty($carteraInfo['tiene_vencida']) ? '#fed7aa' : '#fde68a' }};border-radius:6px;padding:12px;">
            <div style="font-size:14px;font-weight:700;color:#92400e;margin-bottom:6px;">
              {{ !empty($carteraInfo['tiene_vencida']) ? '🚨 Este cliente tiene cartera vencida' : '⚠️ Este cliente tiene cartera próxima a vencer' }}
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
              Evita bloqueos en el despacho: gestiona la cartera con el cliente antes de que la orden llegue a esa etapa.
            </div>
          </td>
        </tr>

        <tr><td colspan="2" height="12"></td></tr>

        <tr>
          <td colspan="2" align="center">
            <a href="{{ $url }}"
               style="display:block;width:100%;max-width:600px;background:#d97706;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:700;text-align:center;">
              📋 Ver Orden de Compra
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
@endsection
