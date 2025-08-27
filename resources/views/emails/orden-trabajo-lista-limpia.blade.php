@extends('layouts.email-limpio')

@section('content')
@php
    $estaCompleta = isset($faltantes) && $faltantes == 0;
    $colorFondo = $estaCompleta ? '#d4edda' : '#fff3cd';
    $colorTexto = $estaCompleta ? '#155724' : '#856404';
    $colorBoton = $estaCompleta ? '#27ae60' : '#f39c12';
    $icono = $estaCompleta ? '✅' : '⚠️';
    $titulo = $estaCompleta ? 'Orden de Trabajo Completa' : 'Orden de Trabajo Lista (Parcial)';
@endphp

<div class="header-section" style="background: linear-gradient(135deg, {{ $colorFondo }} 0%, {{ $colorBoton }} 100%); text-align: center; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
    <div style="font-size: 48px; margin-bottom: 15px;">{{ $icono }}</div>
    <h1 style="margin: 0 0 10px 0; color: white; font-size: 24px; font-weight: 600;">{{ $titulo }}</h1>
    <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 16px;">Tu orden #{{ str_pad($ordenTrabajo->id, 6, '0', STR_PAD_LEFT) }} tiene productos listos</p>
</div>

<div class="card-section">
    <div style="background: white; border-radius: 8px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 2px solid {{ $colorBoton }}; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="margin: 0; color: #2c3e50; font-size: 20px;">Orden de Trabajo #{{ str_pad($ordenTrabajo->id, 6, '0', STR_PAD_LEFT) }}</h2>
                <p style="margin: 5px 0 0 0; color: #7f8c8d;">{{ $ordenTrabajo->nombre ?? 'Orden de Producción' }}</p>
            </div>
            <div style="text-align: right;">
                <span style="background: {{ $colorBoton }}; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                    {{ $estaCompleta ? '100% COMPLETA' : 'PARCIALMENTE LISTA' }}
                </span>
            </div>
        </div>

        @if($estaCompleta)
        <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
            <div style="font-size: 32px; margin-bottom: 10px;">🎉</div>
            <h3 style="margin: 0 0 10px 0; color: #155724; font-size: 18px;">¡Felicitaciones!</h3>
            <p style="margin: 0; color: #155724; font-size: 15px;">Tu orden de trabajo está <strong>completamente lista</strong> para entrega o despacho.</p>
        </div>
        @else
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 32px;">📦</div>
                <div>
                    <h3 style="margin: 0 0 8px 0; color: #856404; font-size: 16px;">Estado de Producción</h3>
                    <p style="margin: 0; color: #856404; font-size: 14px;">
                        Tu orden tiene productos listos para entrega.
                        @if(isset($faltantes) && $faltantes > 0)
                        <br><strong>Faltantes por completar: {{ $faltantes }} unidades</strong>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-bottom: 25px;">
            @if($ordenTrabajo->fecha_entrega)
            <div style="text-align: center; padding: 12px; background: #f8f9fa; border-radius: 6px;">
                <div style="font-size: 20px; margin-bottom: 5px;">📅</div>
                <div style="font-size: 12px; color: #6c757d; margin-bottom: 3px;">FECHA ENTREGA</div>
                <div style="font-weight: 600; color: #2c3e50; font-size: 13px;">{{ \Carbon\Carbon::parse($ordenTrabajo->fecha_entrega)->format('d/m/Y') }}</div>
            </div>
            @endif

            <div style="text-align: center; padding: 12px; background: {{ $colorFondo }}; border-radius: 6px;">
                <div style="font-size: 20px; margin-bottom: 5px;">📊</div>
                <div style="font-size: 12px; color: {{ $colorTexto }}; margin-bottom: 3px;">ESTADO</div>
                <div style="font-weight: 600; color: {{ $colorTexto }}; font-size: 13px;">
                    {{ $estaCompleta ? 'COMPLETA' : 'PARCIAL' }}
                </div>
            </div>

            @if(isset($faltantes))
            <div style="text-align: center; padding: 12px; background: #e8f5e8; border-radius: 6px;">
                <div style="font-size: 20px; margin-bottom: 5px;">📋</div>
                <div style="font-size: 12px; color: #155724; margin-bottom: 3px;">FALTANTES</div>
                <div style="font-weight: 600; color: #155724; font-size: 13px;">{{ $faltantes }}</div>
            </div>
            @endif
        </div>

        @if(!$estaCompleta && isset($faltantes) && $faltantes > 0)
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 14px;">📋 Próximos Pasos:</h4>
            <ul style="margin: 0; padding-left: 20px; color: #555;">
                <li>Los productos disponibles están listos para entrega</li>
                <li>Continuamos trabajando en las {{ $faltantes }} unidades restantes</li>
                <li>Te notificaremos cuando esté 100% completa</li>
            </ul>
        </div>
        @endif
    </div>

    <div class="action-buttons" style="text-align: center; margin: 30px 0;">
        @if($estaCompleta)
        <a href="{{ config('app.frontend_url') }}/auth/crm" 
           style="display: inline-block; background: linear-gradient(135deg, #27ae60 0%, #208040 100%); color: white; padding: 14px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            🚚 Programar Entrega
        </a>
        @else
        <a href="{{ config('app.frontend_url') }}/auth/crm" 
           style="display: inline-block; background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 14px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            📦 Ver Productos Listos
        </a>
        @endif
        
        <a href="{{ config('app.frontend_url') }}/auth/produccion/ordenes-trabajo/{{ $ordenTrabajo->id }}" 
           style="display: inline-block; background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 14px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            📊 Ver Detalles
        </a>
    </div>

    <div style="background: linear-gradient(135deg, {{ $colorFondo }} 0%, rgba(255,255,255,0.8) 100%); padding: 20px; border-radius: 8px; text-align: center;">
        <p style="margin: 0; color: {{ $colorTexto }}; font-size: 14px;">
            @if($estaCompleta)
            <strong>🎊 ¡Excelente trabajo del equipo de producción!</strong><br>
            Tu orden está lista para entrega según los estándares de calidad.
            @else
            <strong>⏳ Trabajo en progreso</strong><br>
            Parte de tu orden ya está disponible. Te mantendremos informado del avance.
            @endif
        </p>
    </div>
</div>
@endsection
