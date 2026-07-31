@extends('layouts.email-modern')

@section('content')
<div class="notification-header">
    <div class="notification-icon" style="background: linear-gradient(135deg, #208040 0%, #166b32 100%);">✅</div>
    <h1>Tu ticket fue cerrado</h1>
    <p class="notification-subtitle">
        Hola {{ $usuario->name ?? 'equipo' }}, tu solicitud ha sido finalizada por el equipo técnico.
    </p>
</div>

<div class="order-details-card">
    <div class="order-id">Ticket #{{ $ticket->id }}</div>
    <div class="details-grid">
        <div class="detail-item">
            <span class="label">Atendido por</span>
            <span class="value">{{ $ticket->asignado->name ?? '-' }}</span>
        </div>
        @if($ticket->producto)
        <div class="detail-item">
            <span class="label">Equipo</span>
            <span class="value">{{ $ticket->producto->name }}</span>
        </div>
        @endif
        <div class="detail-item">
            <span class="label">Estado</span>
            <span class="value"><span class="status-badge completado">Cerrado</span></span>
        </div>
        <div class="detail-item">
            <span class="label">Fecha de cierre</span>
            <span class="value">{{ optional($ticket->fecha_solucion)->format('d/m/Y H:i') ?? '-' }}</span>
        </div>
    </div>

    <div style="margin-top:20px; padding:15px; background:#ffffff; border-radius:8px;">
        <strong>Descripción original:</strong>
        <p style="margin-top:8px; color:#555;">{{ $ticket->descripcion }}</p>
    </div>
</div>

@if(!empty($comentarioCierre))
<div style="margin:20px 0; padding:15px; background:#f0fff4; border-left:4px solid #208040; border-radius:0 8px 8px 0;">
    <strong style="color:#166b32;">Comentario de cierre:</strong>
    <p style="margin-top:6px; color:#333;">{{ $comentarioCierre }}</p>
</div>
@endif

@if(count($adjuntos))
<div style="margin:25px 0;">
    <h3 style="color:#2c3e50; font-size:16px; margin-bottom:10px;">📎 Archivos adjuntos</h3>
    @foreach($adjuntos as $adjunto)
        <p style="margin:4px 0;">
            <a href="{{ $adjunto['url'] }}" style="color:#208040; text-decoration:none;">📄 {{ $adjunto['nombre'] }}</a>
        </p>
    @endforeach
</div>
@endif

<div class="action-section">
    <a href="{{ $url }}" class="btn-primary">Ver ticket y evidencias</a>
</div>

<div class="help-section">
    <p>Si consideras que la solicitud no quedó resuelta, ingresa al sistema y agrega un nuevo comentario.</p>
</div>
@endsection
