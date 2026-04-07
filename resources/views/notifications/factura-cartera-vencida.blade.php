@extends('layouts.email-limpio')

@section('title', 'Factura en Cartera Vencida')

@section('content')
<div style="max-width:480px;margin:0 auto;background:#f8fafc;border-radius:12px;padding:32px 24px 24px 24px;box-shadow:0 2px 8px #0001;">
    <h2 style="color:#dc2626;font-size:1.5rem;margin-bottom:12px;">
        🚨 Factura Vencida
    </h2>
    <p style="font-size:1.1rem;margin-bottom:20px;">
        La factura <strong>{{ $factura->numero_factura }}</strong> se encuentra vencida.
    </p>
    <div style="background:#fff7ed;border-radius:8px;padding:16px 18px;margin-bottom:24px;">
        <div style="margin-bottom:8px;">
            <span style="color:#64748b;">Cliente:</span>
            <strong style="color:#0f172a;">{{ $factura->cliente->nombre ?? 'N/A' }}</strong>
        </div>
        <div style="margin-bottom:8px;">
            <span style="color:#64748b;">Saldo:</span>
            <strong style="color:#dc2626;font-size:1.2rem;">${{ number_format($factura->saldo_pendiente, 2) }}</strong>
        </div>
        <div>
            <span style="color:#64748b;">Vencimiento:</span>
            <strong style="color:#b91c1c;">{{ $factura->fecha_vencimiento }}</strong>
        </div>
    </div>
    <a href="{{ $url }}"
       style="
        display:inline-block;
        padding:14px 28px;
        background:#2563eb;
        color:#fff;
        text-decoration:none;
        border-radius:8px;
        font-weight:bold;
        font-size:1.1rem;
        box-shadow:0 2px 6px #2563eb33;
        transition:background 0.2s;
       ">
       👉 Gestionar factura
    </a>
</div>
@endsection 