@extends('layouts.email-limpio')

@section('content')
<div class="header-section" style="background: linear-gradient(135deg, #f8d7da 0%, #e74c3c 100%); text-align: center; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
    <div style="font-size: 48px; margin-bottom: 15px;">⚠️</div>
    <h1 style="margin: 0 0 10px 0; color: white; font-size: 24px; font-weight: 600;">Tareas Vencidas</h1>
    <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 16px;">{{ $tareasVencidas->count() }} {{ $tareasVencidas->count() == 1 ? 'tarea requiere' : 'tareas requieren' }} tu atención urgente</p>
</div>

<div class="card-section">
    @foreach($tareasVencidas->take(3) as $tarea)
    <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #e74c3c; margin-bottom: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="margin: 0; color: #2c3e50; font-size: 18px;">{{ $tarea->titulo ?? 'Tarea sin título' }}</h3>
                @if($tarea->cliente)
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 14px;">👤 {{ $tarea->cliente->nombre }}</p>
                @endif
            </div>
            <div style="text-align: right;">
                <span style="background: #e74c3c; color: white; padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; animation: pulse 2s infinite;">
                    🔥 VENCIDA
                </span>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="color: #6c757d; font-size: 13px;">📅 Fecha límite</span>
                <span style="color: #e74c3c; font-weight: 600; font-size: 14px;">
                    {{ \Carbon\Carbon::parse($tarea->fecha_vencimiento)->format('d/m/Y H:i') }}
                </span>
            </div>
            <div style="text-align: center; color: #e74c3c; font-size: 12px; font-style: italic;">
                {{ \Carbon\Carbon::parse($tarea->fecha_vencimiento)->diffForHumans() }}
            </div>
        </div>

        @if($tarea->descripcion)
        <div style="color: #555; font-size: 14px; line-height: 1.4;">
            <strong>Descripción:</strong> {{ Str::limit($tarea->descripcion, 80) }}
        </div>
        @endif
    </div>
    @endforeach

    @if($tareasVencidas->count() > 3)
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
        <p style="margin: 0; color: #856404; font-weight: 600;">
            📋 Y {{ $tareasVencidas->count() - 3 }} tareas más esperando tu atención
        </p>
    </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0;">
        <div style="text-align: center; padding: 15px; background: #fff5f5; border-radius: 6px;">
            <div style="font-size: 24px; font-weight: 700; color: #e74c3c;">{{ $tareasVencidas->count() }}</div>
            <div style="font-size: 12px; color: #721c24;">VENCIDAS</div>
        </div>
        <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 6px;">
            <div style="font-size: 24px; font-weight: 700; color: #856404;">{{ $tareasVencidas->where('prioridad', 'alta')->count() }}</div>
            <div style="font-size: 12px; color: #856404;">ALTA PRIORIDAD</div>
        </div>
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 24px; font-weight: 700; color: #6c757d;">{{ $tareasVencidas->unique('cliente_id')->count() }}</div>
            <div style="font-size: 12px; color: #6c757d;">CLIENTES</div>
        </div>
    </div>

           style="display: inline-block; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 14px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); animation: pulse 2s infinite;">
            🚀 Resolver Tareas Vencidas
        </a>
        <a href="{{ config('app.frontend_url') }}/auth/crm/dashboard" 
           style="display: inline-block; background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 14px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            📊 Ver Dashboard
        </a>
    </div>

    <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; text-align: center;">
        <div style="font-size: 20px; margin-bottom: 10px;">⏰</div>
        <p style="margin: 0; color: #721c24; font-size: 14px;">
            <strong>Plan de Acción:</strong> Prioriza las tareas más críticas y contacta a los clientes afectados.
            <br><small>Tu equipo confía en tu eficiencia para mantener la satisfacción del cliente.</small>
        </p>
    </div>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
@endsection
    50% { transform: scale(1.1); }
}

@media (max-width: 600px) {
    .alert-card {
        flex-direction: column;
        text-align: center;
    }
    
    .task-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>
