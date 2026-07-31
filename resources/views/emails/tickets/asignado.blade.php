@extends('layouts.email-modern')

@section('content')
<div class="notification-header">
    <div class="notification-icon task">🎫</div>
    <h1>Nuevo ticket asignado</h1>
    <p class="notification-subtitle">
        Hola {{ $usuario->name ?? 'equipo' }}, te han asignado un ticket de soporte TIC.
    </p>
</div>

<div class="order-details-card">
    <div class="order-id">Ticket #{{ $ticket->id }}</div>
    <div class="details-grid">
        <div class="detail-item">
            <span class="label">Solicitante</span>
            <span class="value">{{ $ticket->solicitante->name ?? '-' }}</span>
        </div>
        @if($ticket->producto)
        <div class="detail-item">
            <span class="label">Equipo</span>
            <span class="value">{{ $ticket->producto->name }}</span>
        </div>
        @endif
        @if($ticket->departamento)
        <div class="detail-item">
            <span class="label">Departamento</span>
            <span class="value">{{ $ticket->departamento->nombre }}</span>
        </div>
        @endif
        <div class="detail-item">
            <span class="label">Prioridad</span>
            <span class="value">
                <span class="priority-indicator {{ $ticket->prioridad }}">{{ ucfirst($ticket->prioridad) }}</span>
            </span>
        </div>
        <div class="detail-item">
            <span class="label">Entrega solicitada</span>
            <span class="value">{{ $ticket->fecha_entrega }} {{ substr($ticket->hora_entrega ?? '', 0, 5) }}</span>
        </div>
    </div>

    <div style="margin-top:20px; padding:15px; background:#ffffff; border-radius:8px;">
        <strong>Descripción:</strong>
        <p style="margin-top:8px; color:#555;">{{ $ticket->descripcion }}</p>
    </div>
</div>

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
    <a href="{{ $url }}" class="btn-primary btn-task">Ver ticket en el sistema</a>
</div>

<div class="help-section">
    <p>Desde el sistema puedes revisar los archivos adjuntos, comentar y actualizar el estado del ticket.</p>
</div>
@endsection
