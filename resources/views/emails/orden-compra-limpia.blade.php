@extends('layouts.email-modern')

@section('content')
<div class="notification-header">
    <div class="notification-icon">
        📋
    </div>
    <h1>Nueva Orden de Compra</h1>
    <p class="notification-subtitle">Hola {{ $usuario->name }}, se ha generado una nueva orden</p>
</div>

<div class="order-card">
    <div class="order-header">
        <h2>Orden #{{ str_pad($orden->id, 6, '0', STR_PAD_LEFT) }}</h2>
        @if($orden->valor_total > 3000000)
            <span class="priority-high">Alta Prioridad</span>
        @endif
    </div>
    
    <div class="order-details">
        <div class="detail-row">
            <span>👤 Cliente:</span>
            <strong>{{ $orden->cliente->nombre ?? 'No especificado' }}</strong>
        </div>
        
        <div class="detail-row">
            <span>📅 Entrega:</span>
            <strong>{{ \Carbon\Carbon::parse($orden->fecha_entrega)->format('d/m/Y') }}</strong>
        </div>
        
        <div class="detail-row">
            <span>📍 Ubicación:</span>
            <strong>{{ $orden->ubicacion_entrega ?? 'No especificada' }}</strong>
        </div>
        
        <div class="detail-row total-row">
            <span>💰 Valor Total:</span>
            <strong>${{ number_format($orden->valor_total, 0, ',', '.') }}</strong>
        </div>
    </div>
</div>

<div class="actions">
    <a href="{{ config('app.frontend_url') }}/auth/crm/ordenes/{{ $orden->id }}" class="btn-primary">
        Ver Detalles
    </a>
    <a href="{{ config('app.frontend_url') }}/auth/crm" class="btn-secondary">
        Dashboard
    </a>
</div>

<div class="next-steps">
    <h3>📋 Próximos pasos</h3>
    <ul>
        <li>Revisar especificaciones</li>
        <li>Coordinar con producción</li>
        <li>Confirmar disponibilidad</li>
    </ul>
</div>
@endsection

<style>
.order-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin: 20px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-top: 4px solid #208040;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.order-header h2 {
    color: #2c3e50;
    margin: 0;
}

.priority-high {
    background: #e74c3c;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.order-details {
    background: #fef7ed;
    padding: 20px;
    border-radius: 8px;
    border-left: 3px solid #fff0db;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.detail-row:last-child {
    border-bottom: none;
}

.total-row {
    background: rgba(32, 128, 64, 0.1);
    margin: 10px -20px -20px -20px;
    padding: 15px 20px;
    border-radius: 0 0 8px 8px;
}

.total-row strong {
    color: #208040;
    font-size: 18px;
}

.actions {
    text-align: center;
    margin: 30px 0;
}

.btn-primary, .btn-secondary {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    margin: 0 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #208040, #27ae60);
    color: white;
}

.btn-secondary {
    background: #ecf0f1;
    color: #2c3e50;
}

.next-steps {
    background: rgba(32, 128, 64, 0.05);
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.next-steps h3 {
    color: #208040;
    margin: 0 0 15px 0;
}

.next-steps ul {
    margin: 0;
    padding: 0 0 0 20px;
}

.next-steps li {
    margin: 8px 0;
    color: #2c3e50;
}

@media (max-width: 600px) {
    .order-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 4px;
        text-align: center;
    }
    
    .actions {
        flex-direction: column;
    }
    
    .btn-primary, .btn-secondary {
        display: block;
        margin: 8px 0;
    }
}
</style>
