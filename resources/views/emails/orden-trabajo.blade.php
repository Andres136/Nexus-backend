@extends('layouts.email-modern')

@section('content')
@php
    use App\Services\EmailPersonalizationService;
    $saludo = EmailPersonalizationService::getSaludo();
@endphp

<div class="notification-header">
    <div class="notification-icon work-order">
        🔧
    </div>
    <h1>{{ $saludo }}, {{ $usuario->name }}</h1>
    <p class="notification-subtitle">Nueva Orden de Trabajo creada - Tu expertise técnica es requerida</p>
</div>

<div class="notification-card work-order">
    <div class="work-order-header">
        <div class="order-info">
            <h2>Orden de Trabajo #{{ str_pad($ordenTrabajo->id, 6, '0', STR_PAD_LEFT) }}</h2>
            <span class="priority-indicator {{ strtolower($ordenTrabajo->prioridad ?? 'media') }}">
                {{ strtoupper($ordenTrabajo->prioridad ?? 'MEDIA') }}
            </span>
        </div>
        <div class="order-status">
            <span class="status-badge {{ strtolower($ordenTrabajo->estado ?? 'pendiente') }}">
                {{ ucfirst($ordenTrabajo->estado ?? 'Pendiente') }}
            </span>
        </div>
    </div>

    <div class="work-order-summary">
        <div class="summary-card urgent">
            <div class="summary-icon">⚡</div>
            <div class="summary-content">
                <h4>Urgencia</h4>
                <p>{{ $ordenTrabajo->nivel_urgencia ?? 'Estándar' }}</p>
                <small>
                    @if($ordenTrabajo->fecha_limite)
                        Límite: {{ \Carbon\Carbon::parse($ordenTrabajo->fecha_limite)->format('d/m/Y H:i') }}
                    @else
                        Sin fecha límite específica
                    @endif
                </small>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">🏭</div>
            <div class="summary-content">
                <h4>Área/Departamento</h4>
                <p>{{ $ordenTrabajo->area ?? $ordenTrabajo->departamento ?? 'Producción General' }}</p>
                <small>{{ $ordenTrabajo->ubicacion_especifica ?? 'Ver detalles en sistema' }}</small>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">👨‍🔧</div>
            <div class="summary-content">
                <h4>Tipo de Trabajo</h4>
                <p>{{ $ordenTrabajo->tipo_trabajo ?? 'Mantenimiento General' }}</p>
                <small>{{ $ordenTrabajo->categoria ?? 'Rutinario' }}</small>
            </div>
        </div>

        @if($ordenTrabajo->estimacion_horas)
        <div class="summary-card">
            <div class="summary-icon">⏱️</div>
            <div class="summary-content">
                <h4>Tiempo Estimado</h4>
                <p>{{ $ordenTrabajo->estimacion_horas }} horas</p>
                <small>Planifica recursos necesarios</small>
            </div>
        </div>
        @endif
    </div>

    <div class="work-description">
        <h4>📝 Descripción del Trabajo</h4>
        <div class="description-content">
            <p>{{ $ordenTrabajo->descripcion ?? 'Revisar especificaciones detalladas en el sistema.' }}</p>
        </div>
        
        @if($ordenTrabajo->problema_reportado)
        <div class="problem-section">
            <h5>🚨 Problema Reportado:</h5>
            <p>{{ $ordenTrabajo->problema_reportado }}</p>
        </div>
        @endif
    </div>

    @if($ordenTrabajo->materiales_requeridos && is_array($ordenTrabajo->materiales_requeridos))
    <div class="materials-section">
        <h4>🧰 Materiales y Herramientas Requeridas</h4>
        <div class="materials-grid">
            @foreach($ordenTrabajo->materiales_requeridos as $material)
            <div class="material-item">
                <span class="material-icon">🔩</span>
                <div class="material-info">
                    <strong>{{ $material['nombre'] ?? $material }}</strong>
                    @if(isset($material['cantidad']))
                        <span class="quantity">x{{ $material['cantidad'] }}</span>
                    @endif
                    @if(isset($material['especificacion']))
                        <small>{{ $material['especificacion'] }}</small>
                    @endif
                </div>
                <span class="availability {{ isset($material['disponible']) && $material['disponible'] ? 'available' : 'check' }}">
                    {{ isset($material['disponible']) && $material['disponible'] ? '✅' : '❓' }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($ordenTrabajo->pasos_trabajo && is_array($ordenTrabajo->pasos_trabajo))
    <div class="steps-section">
        <h4>📋 Pasos de Trabajo Sugeridos</h4>
        <ol class="steps-list">
            @foreach($ordenTrabajo->pasos_trabajo as $paso)
            <li>{{ $paso }}</li>
            @endforeach
        </ol>
    </div>
    @endif

    @if($ordenTrabajo->consideraciones_seguridad)
    <div class="safety-section">
        <h4>⚠️ Consideraciones de Seguridad</h4>
        <div class="safety-alert">
            <div class="safety-icon">🦺</div>
            <div class="safety-content">
                <p>{{ $ordenTrabajo->consideraciones_seguridad }}</p>
                <div class="safety-checklist">
                    <div class="safety-item">✅ EPP requerido verificado</div>
                    <div class="safety-item">✅ Área de trabajo despejada</div>
                    <div class="safety-item">✅ Herramientas en buen estado</div>
                    <div class="safety-item">✅ Procedimiento de emergencia conocido</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="action-section">
    <a href="{{ config('app.frontend_url') }}/auth/produccion/ordenes-trabajo/{{ $ordenTrabajo->id }}" class="btn-primary">
        🔧 Ver Orden de Trabajo
    </a>
    <a href="{{ config('app.frontend_url') }}/auth/produccion/ordenes-trabajo/{{ $ordenTrabajo->id }}/aceptar" class="btn-task">
        ✅ Aceptar Trabajo
    </a>
    <a href="{{ config('app.frontend_url') }}/auth/produccion" class="btn-secondary">
        📊 Dashboard Producción
    </a>
</div>

<div class="timeline-trabajo">
    <h3>📅 Cronograma Propuesto</h3>
    <div class="timeline-container">
        @php
            $horasEstimadas = $ordenTrabajo->estimacion_horas ?? 4;
            $fechaInicio = \Carbon\Carbon::now();
            $fechaFin = $ordenTrabajo->fecha_limite ? 
                \Carbon\Carbon::parse($ordenTrabajo->fecha_limite) : 
                $fechaInicio->copy()->addHours($horasEstimadas + 2);
        @endphp
        
        <div class="timeline-step">
            <div class="step-icon">🔍</div>
            <div class="step-content">
                <strong>Evaluación Inicial</strong>
                <p>Revisar el área, verificar materiales y herramientas</p>
                <small>30 minutos</small>
            </div>
        </div>

        <div class="timeline-step">
            <div class="step-icon">🛠️</div>
            <div class="step-content">
                <strong>Ejecución</strong>
                <p>Realizar el trabajo según especificaciones</p>
                <small>{{ $horasEstimadas - 1 }} horas</small>
            </div>
        </div>

        <div class="timeline-step">
            <div class="step-icon">✅</div>
            <div class="step-content">
                <strong>Verificación y Limpieza</strong>
                <p>Comprobar calidad y limpiar área de trabajo</p>
                <small>30 minutos</small>
            </div>
        </div>

        <div class="timeline-step">
            <div class="step-icon">📝</div>
            <div class="step-content">
                <strong>Documentación</strong>
                <p>Actualizar estado y completar reporte</p>
                <small>15 minutos</small>
            </div>
        </div>
    </div>
</div>

<div class="quality-standards">
    <h3>🏆 Estándares de Calidad</h3>
    <div class="standards-grid">
        <div class="standard-card">
            <div class="standard-icon">🎯</div>
            <div class="standard-content">
                <h4>Precisión</h4>
                <p>Seguir especificaciones técnicas al 100%</p>
            </div>
        </div>
        <div class="standard-card">
            <div class="standard-icon">🛡️</div>
            <div class="standard-content">
                <h4>Seguridad</h4>
                <p>Cero incidentes, protocolos estrictos</p>
            </div>
        </div>
        <div class="standard-card">
            <div class="standard-icon">⏰</div>
            <div class="standard-content">
                <h4>Puntualidad</h4>
                <p>Completar dentro del tiempo estimado</p>
            </div>
        </div>
        <div class="standard-card">
            <div class="standard-icon">📊</div>
            <div class="standard-content">
                <h4>Documentación</h4>
                <p>Registrar todas las actividades realizadas</p>
            </div>
        </div>
    </div>
</div>

<div class="team-support">
    <h3>👥 Equipo de Apoyo</h3>
    <div class="support-contacts">
        @if($ordenTrabajo->supervisor)
        <div class="contact-card">
            <div class="contact-avatar">👨‍💼</div>
            <div class="contact-info">
                <strong>Supervisor</strong>
                <p>{{ $ordenTrabajo->supervisor->name ?? 'Supervisor de Turno' }}</p>
                <small>{{ $ordenTrabajo->supervisor->telefono ?? 'Ext. 1234' }}</small>
            </div>
        </div>
        @endif

        <div class="contact-card">
            <div class="contact-avatar">🛠️</div>
            <div class="contact-info">
                <strong>Mantenimiento</strong>
                <p>Equipo de Soporte Técnico</p>
                <small>Ext. 1500 - 24/7</small>
            </div>
        </div>

        <div class="contact-card">
            <div class="contact-avatar">🦺</div>
            <div class="contact-info">
                <strong>Seguridad Industrial</strong>
                <p>Oficial de Seguridad</p>
                <small>Ext. 1911 - Emergencias</small>
            </div>
        </div>

        <div class="contact-card">
            <div class="contact-avatar">📦</div>
            <div class="contact-info">
                <strong>Almacén</strong>
                <p>Materiales y Herramientas</p>
                <small>Ext. 1300</small>
            </div>
        </div>
    </div>
</div>

<div class="recognition-section">
    <h3>🌟 Tu Trabajo Hace la Diferencia</h3>
    <div class="recognition-content">
        <p>Cada orden de trabajo que completas con excelencia contribuye al éxito de nuestra operación. Tu experiencia técnica y dedicación son fundamentales para mantener la calidad que nos caracteriza.</p>
        <div class="impact-metrics">
            <div class="metric">
                <span class="metric-icon">⚡</span>
                <div>
                    <strong>Eficiencia Operativa</strong>
                    <p>Tu trabajo mantiene la producción en óptimas condiciones</p>
                </div>
            </div>
            <div class="metric">
                <span class="metric-icon">✨</span>
                <div>
                    <strong>Calidad del Producto</strong>
                    <p>Aseguras que nuestros estándares se mantengan altos</p>
                </div>
            </div>
            <div class="metric">
                <span class="metric-icon">🤝</span>
                <div>
                    <strong>Satisfacción del Cliente</strong>
                    <p>Contribuyes directamente a la confianza en nuestra marca</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
.work-order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.order-info h2 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 24px;
}

.work-order-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 25px 0;
}

.summary-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    display: flex;
    align-items: flex-start;
    gap: 15px;
    border-top: 3px solid #f39c12;
}

.summary-card.urgent {
    border-top-color: #e74c3c;
}

.summary-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.summary-content h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-content p {
    margin: 0 0 5px 0;
    font-weight: 600;
    color: #2c3e50;
    font-size: 16px;
}

.work-description {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.problem-section {
    background: #fff5f5;
    padding: 15px;
    border-radius: 6px;
    margin-top: 15px;
    border-left: 3px solid #e74c3c;
}

.materials-section, .steps-section, .safety-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.materials-grid {
    display: grid;
    gap: 10px;
    margin-top: 15px;
}

.material-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
}

.material-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.material-info {
    flex-grow: 1;
}

.quantity {
    background: #3498db;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 12px;
    margin-left: 10px;
}

.availability {
    font-size: 18px;
}

.steps-list {
    list-style: none;
    counter-reset: step-counter;
    padding: 0;
    margin-top: 15px;
}

.steps-list li {
    counter-increment: step-counter;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    margin: 10px 0;
    position: relative;
    padding-left: 60px;
}

.steps-list li::before {
    content: counter(step-counter);
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: #f39c12;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.safety-alert {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    background: #fff8e1;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ffc107;
    margin-top: 15px;
}

.safety-icon {
    font-size: 32px;
    flex-shrink: 0;
}

.safety-checklist {
    margin-top: 15px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.safety-item {
    font-size: 12px;
    color: #27ae60;
    padding: 5px 0;
}

.timeline-trabajo {
    background: linear-gradient(135deg, #fff8e1 0%, #fff3c4 100%);
    padding: 25px;
    border-radius: 8px;
    margin: 25px 0;
}

.timeline-step {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px 0;
    border-left: 2px solid #f39c12;
    padding-left: 25px;
    margin-left: 15px;
    position: relative;
}

.timeline-step::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 20px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #f39c12;
    border: 3px solid white;
    box-shadow: 0 0 0 3px #f39c12;
}

.step-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.quality-standards {
    background: #e8f5e8;
    padding: 25px;
    border-radius: 8px;
    margin: 25px 0;
}

.standards-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.standard-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.standard-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.team-support {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 25px;
    border-radius: 8px;
    margin: 25px 0;
}

.support-contacts {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.contact-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.contact-avatar {
    font-size: 24px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.recognition-section {
    background: linear-gradient(135deg, #fff0db 0%, #208040 20%);
    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
    padding: 30px;
    border-radius: 8px;
    margin: 25px 0;
    text-align: center;
}

.impact-metrics {
    margin-top: 25px;
    display: grid;
    gap: 15px;
}

.metric {
    display: flex;
    align-items: center;
    gap: 15px;
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: left;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.metric-icon {
    font-size: 24px;
    flex-shrink: 0;
}

@media (max-width: 600px) {
    .work-order-summary {
        grid-template-columns: 1fr;
    }
    
    .standards-grid, .support-contacts {
        grid-template-columns: 1fr;
    }
    
    .safety-checklist {
        grid-template-columns: 1fr;
    }
    
    .timeline-step, .contact-card {
        flex-direction: column;
        gap: 5px;
    }
}
</style>
