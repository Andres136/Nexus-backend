@extends('layouts.email-limpio')

@section('title', 'Nueva PQR Recibida')

@section('content')
    <div style="margin: 30px 0;">
        <h2 style="color: #208040; margin-bottom: 25px; font-size: 22px; font-weight: 600;">
            📥 Nueva PQR Recibida
        </h2>
        
        <div style="background: #f8fffe; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #208040;">
            <p style="margin: 0 0 15px 0; font-size: 16px; color: #2c3e50;">
                <strong style="color: #208040;">Código de radicado:</strong> {{ $codigo }}
            </p>
            
            <div style="margin: 20px 0;">
                <h3 style="color: #208040; font-size: 16px; margin-bottom: 10px;">📋 Datos del solicitante:</h3>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin: 8px 0; padding: 5px 0; border-bottom: 1px solid #e0e0e0;">
                        <strong>Nombre:</strong> {{ $pqr['nombre'] }}
                    </li>
                    <li style="margin: 8px 0; padding: 5px 0; border-bottom: 1px solid #e0e0e0;">
                        <strong>Empresa:</strong> {{ $pqr['empresa'] ?? 'No especificada' }}
                    </li>
                    <li style="margin: 8px 0; padding: 5px 0; border-bottom: 1px solid #e0e0e0;">
                        <strong>Teléfono:</strong> {{ $pqr['telefono'] ?? 'No especificado' }}
                    </li>
                    <li style="margin: 8px 0; padding: 5px 0;">
                        <strong>Correo:</strong> {{ $emailSolicitante }}
                    </li>
                </ul>
            </div>
        </div>

        <div style="margin: 25px 0;">
            <h3 style="color: #208040; font-size: 16px; margin-bottom: 15px;">💬 Mensaje:</h3>
            <div style="background: #ffffff; padding: 15px; border: 1px solid #ddd; border-radius: 6px; font-style: italic;">
                "{{ $pqr['mensaje'] }}"
            </div>
        </div>

        <div style="margin: 25px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px;">
            <p style="margin: 0; color: #856404;">
                <strong>⏰ Estado:</strong> Pendiente de revisión
            </p>
        </div>

        @if($archivoUrl)
        <div style="margin: 25px 0; text-align: center;">
            <a href="{{ $archivoUrl }}" 
               style="display: inline-block; background: #208040; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                📎 Ver archivo adjunto
            </a>
        </div>
        @endif
    </div>
@endsection

@section('footer-text')
    Sistema PQR - SETASPLAST SAS BIC
@endsection
