@extends('layouts.email-modern')

@section('content')
<div class="notification-header">
    <div class="notification-icon">
        ⚠️
    </div>
    <h1>Orden Próxima a Vencer</h1>
    <p class="notification-subtitle">¡Atención {{ $usuario->name }}! La siguiente orden requiere seguimiento urgente.</p>
</div>

<div class="order-details-card urgent">
    <div class="urgent-banner">
        <strong>⚡ ACCIÓN URGENTE REQUERIDA</strong>
    </div>
    
    <div class="order-id">
        <strong>Orden #{{ str_pad($orden->id, 6, '0', STR_PAD_LEFT) }}</strong>
    </div>
    
    <div class="details-grid">
        <div class="detail-item">
            <span class="label">Cliente:</span>
            <span class="value">{{ $orden->cliente->nombre ?? 'No especificado' }}</span>
        </div>
        
        <div class="detail-item urgent-detail">
            <span class="label">⏰ Fecha de Entrega:</span>
            <span class="value">{{ \Carbon\Carbon::parse($orden->fecha_entrega)->format('d/m/Y') }}</span>
        </div>
        
        <div class="detail-item">
            <span class="label">Días Restantes:</span>
            <span class="value urgent-value">{{ \Carbon\Carbon::parse($orden->fecha_entrega)->diffInDays(now()) }} días</span>
        </div>
        
        <div class="detail-item total">
            <span class="label">Valor Total:</span>
            <span class="value">${{ number_format($orden->valor_total, 0, ',', '.') }}</span>
        </div>
    </div>
</div>

<div class="action-section">
    <a href="{{ config('app.frontend_url') }}/auth/crm/ordenes/{{ $orden->id }}" class="btn-urgent">
        Revisar Orden Ahora
    </a>
</div>

<div class="next-steps urgent-steps">
    <h3>🚨 Acciones Inmediatas:</h3>
    <ul>
        <li>Verificar el estado actual de producción</li>
        <li>Contactar al cliente para confirmar detalles</li>
        <li>Coordinar con logística para la entrega</li>
        <li>Actualizar el estado en el sistema</li>
    </ul>
</div>
@endsection

<style>
.urgent-banner {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    text-align: center;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: bold;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.urgent-detail {
    background: #ffeaa7;
    padding: 10px;
    border-radius: 6px;
    margin: 5px -10px;
}

.urgent-value {
    color: #e74c3c !important;
    font-weight: bold;
}

.btn-urgent {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
    animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
    from { box-shadow: 0 0 20px #e74c3c; }
    to { box-shadow: 0 0 30px #ff6b6b, 0 0 40px #ff6b6b; }
}

.urgent-steps {
    border-left-color: #e74c3c;
    background: #ffeaa7;
}
</style>
