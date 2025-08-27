@extends('layouts.email-limpio')

@section('title', 'Mensaje Recibido')

@section('content')
    <div style="margin: 30px 0;">
        <h2 style="color: #208040; margin-bottom: 25px; font-size: 22px; font-weight: 600;">
            ✅ Hemos recibido tu mensaje
        </h2>
        
        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
            Hola <strong>{{ $contacto['nombre'] }}</strong>,
        </p>

        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
            Gracias por contactarte con nosotros. Hemos recibido tu mensaje y nos pondremos en contacto contigo pronto.
        </p>
        
        <div style="background: #f8fffe; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #208040;">
            <h3 style="color: #208040; margin: 0 0 15px 0; font-size: 16px;">📋 Resumen de tu mensaje:</h3>
            
            <div style="margin: 15px 0;">
                @if($contacto['empresa'])
                <p style="margin: 5px 0;"><strong>Empresa:</strong> {{ $contacto['empresa'] }}</p>
                @endif
                
                @if($contacto['telefono'])
                <p style="margin: 5px 0;"><strong>Teléfono:</strong> {{ $contacto['telefono'] }}</p>
                @endif
            </div>

            <div style="margin: 20px 0;">
                <p style="margin: 0 0 10px 0; font-weight: 600;">Mensaje enviado:</p>
                <div style="background: #ffffff; padding: 15px; border: 1px solid #ddd; border-radius: 6px; font-style: italic; line-height: 1.6;">
                    "{{ $contacto['mensaje'] }}"
                </div>
            </div>
        </div>

        <div style="margin: 25px 0; padding: 20px; background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 6px;">
            <h3 style="color: #208040; margin: 0 0 15px 0; font-size: 16px;">⏱️ ¿Qué sigue ahora?</h3>
            <ul style="margin: 0; padding-left: 20px; color: #2c5530;">
                <li style="margin: 8px 0;">Nuestro equipo revisará tu mensaje</li>
                <li style="margin: 8px 0;">Te contactaremos a la brevedad posible</li>
                <li style="margin: 8px 0;">Si es urgente, puedes llamarnos directamente</li>
            </ul>
        </div>

        <div style="margin: 25px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; text-align: center;">
            <p style="margin: 0; color: #856404;">
                <strong>📞 ¿Necesitas respuesta inmediata?</strong><br>
                <span style="font-size: 18px; font-weight: 600;">3112890067</span>
            </p>
        </div>
    </div>
@endsection

@section('footer-text')
    Saludos, equipo de SETASPLAST SAS BIC
@endsection
