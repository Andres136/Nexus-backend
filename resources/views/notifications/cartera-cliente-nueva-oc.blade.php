@extends('layouts.email-limpio')

@section('title', 'Cartera pendiente del cliente')

@section('content')
@php
    $ocNum = str_pad((int) ($ordenCompra->id ?? 0), 6, '0', STR_PAD_LEFT);
    $clienteNombre = optional($ordenCompra->cliente)->nombre ?? 'el cliente';
    $tieneVencida = !empty($resumen['tiene_vencida']);
@endphp

<div style="max-width:480px;margin:0 auto;background:#f8fafc;border-radius:12px;padding:32px 24px 24px 24px;box-shadow:0 2px 8px #0001;">
    <h2 style="color:{{ $tieneVencida ? '#dc2626' : '#f59e0b' }};font-size:1.4rem;margin-bottom:12px;">
        {{ $tieneVencida ? '🚨 Cliente con cartera vencida' : '⚠️ Cliente con cartera próxima a vencer' }}
    </h2>

    <p style="font-size:1.05rem;margin-bottom:16px;">
        Hola {{ $usuario->name ?? '' }}, acabas de crear la <strong>Orden de Compra #{{ $ocNum }}</strong>
        para <strong>{{ $clienteNombre }}</strong>. Antes de seguir avanzando con esta orden, te contamos que
        este cliente tiene cartera pendiente que conviene revisar:
    </p>

    @if($tieneVencida)
    <div style="background:#fff7ed;border-radius:8px;padding:14px 16px;margin-bottom:14px;">
        <div style="font-weight:700;color:#dc2626;margin-bottom:6px;">Facturas vencidas</div>
        <div style="color:#7c2d12;font-size:0.95rem;">
            {{ collect($resumen['facturas_vencidas'] ?? [])->join(', ') }}
        </div>
        <div style="margin-top:6px;">
            <span style="color:#64748b;">Total vencido:</span>
            <strong style="color:#dc2626;">${{ number_format($resumen['total_vencido'] ?? 0, 0, ',', '.') }}</strong>
        </div>
    </div>
    @endif

    @if(!empty($resumen['tiene_proxima']))
    <div style="background:#fffbeb;border-radius:8px;padding:14px 16px;margin-bottom:14px;">
        <div style="font-weight:700;color:#b45309;margin-bottom:6px;">Facturas próximas a vencer</div>
        <div style="color:#78350f;font-size:0.95rem;">
            {{ collect($resumen['facturas_proximas'] ?? [])->join(', ') }}
        </div>
        <div style="margin-top:6px;">
            <span style="color:#64748b;">Total próximo:</span>
            <strong style="color:#b45309;">${{ number_format($resumen['total_proximo'] ?? 0, 0, ',', '.') }}</strong>
        </div>
    </div>
    @endif

    <p style="font-size:0.95rem;color:#475569;margin-bottom:20px;">
        Te recomendamos gestionar esta cartera con el cliente cuanto antes, para que el proceso de tu orden
        no se vea afectado más adelante.
    </p>


</div>
@endsection
