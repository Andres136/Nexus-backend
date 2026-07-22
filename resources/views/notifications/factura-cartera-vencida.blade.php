@extends('layouts.email-limpio')

@section('title', 'Factura en Cartera Vencida')

@section('content')
<div style="max-width:480px;margin:0 auto;background:#f8fafc;border-radius:12px;padding:32px 24px 24px 24px;box-shadow:0 2px 8px #0001;">
    <h2 style="color:#dc2626;font-size:1.5rem;margin-bottom:12px;">
        🚨 Factura Vencida
    </h2>
    @if(!empty($usuarioCreadorOc))
    <p style="font-size:1.05rem;margin-bottom:12px;">
        Se acaba de generar una nueva Orden de Compra . Para seguir brindándote un excelente servicio, te invitamos a gestionar cuanto antes la siguiente factura, que se encuentra <strong>vencida</strong>:
    </p>
    @else
    <p style="font-size:1.05rem;margin-bottom:12px;">
        La siguiente factura se encuentra <strong>vencida</strong>. Te invitamos a darle seguimiento cuanto antes:
    </p>
    @endif
    <p style="font-size:1.1rem;margin-bottom:20px;">
        Factura <strong>{{ $factura->numero_factura }}</strong>
    </p>
    <div style="background:#fff7ed;border-radius:8px;padding:16px 18px;margin-bottom:24px;">
        <div style="margin-bottom:8px;">

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
    <p style="font-size:0.95rem;color:#475569;margin-bottom:24px;">
        Agradecemos tu gestión oportuna: mantener la cartera al día nos permite seguir ofreciendo un servicio ágil y de calidad a nuestros clientes.
    </p>

    @if(!empty($whatsappUrl))
    <a href="{{ $whatsappUrl }}"
       style="
        display:inline-block;
        margin-top:12px;
        padding:14px 28px;
        background:#25d366;
        color:#fff;
        text-decoration:none;
        border-radius:8px;
        font-weight:bold;
        font-size:1.1rem;
        box-shadow:0 2px 6px #25d36633;
       ">
       💬 Contactar por WhatsApp
    </a>
    @endif
</div>
@endsection
