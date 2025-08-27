@extends('layouts.email-limpio')

@section('title', 'Nuevo Mensaje Web')

@section('content')
    <div style="margin: 30px 0;">
        <h2 style="color: #208040; margin-bottom: 25px; font-size: 22px; font-weight: 600;">
          Nuevo mensaje desde la web
        </h2>
        
        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
            Se ha recibido un nuevo mensaje desde el formulario de contacto de la página web.
        </p>
        
        <div style="background: #f8fffe; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #208040;">
            <h3 style="color: #208040; margin: 0 0 15px 0; font-size: 16px;">📋 Datos del contacto:</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin: 8px 0; padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                    <strong>Nombre:</strong> {{ $contacto['nombre'] }}
                </li>
                <li style="margin: 8px 0; padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                    <strong>Empresa:</strong> {{ $contacto['empresa'] ?? 'No especificada' }}
                </li>
                <li style="margin: 8px 0; padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                    <strong>Teléfono:</strong> {{ $contacto['telefono'] ?? 'No especificado' }}
                </li>
                <li style="margin: 8px 0; padding: 8px 0;">
                    <strong>Correo:</strong> 
                    <a href="mailto:{{ $contacto['email'] }}" style="color: #208040; text-decoration: none;">
                        {{ $contacto['email'] }}
                    </a>
                </li>
            </ul>
        </div>

        <div style="margin: 25px 0;">
            <h3 style="color: #208040; font-size: 16px; margin-bottom: 15px;">💬 Mensaje:</h3>
            <div style="background: #ffffff; padding: 20px; border: 1px solid #ddd; border-radius: 6px; font-style: italic; line-height: 1.6;">
                "{{ $contacto['mensaje'] }}"
            </div>
        </div>

        <div style="margin: 25px 0; text-align: center;">
            <a href="mailto:{{ $contacto['email'] }}?subject=Re: Mensaje desde SETASPLAST" 
               style="display: inline-block; background: #208040; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                📧 Responder mensaje
            </a>
        </div>

        <div style="margin: 25px 0; padding: 15px; background: #e8f4fd; border: 1px solid #bee5eb; border-radius: 6px;">
            <p style="margin: 0; color: #0c5460; font-size: 14px;">
                <strong>💡 Tip:</strong> Puedes responder directamente a este correo para contactar al remitente.
            </p>
        </div>
    </div>
@endsection

@section('footer-text')
    Sistema de Contacto - SETASPLAST SAS BIC
@endsection
