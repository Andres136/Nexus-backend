@extends('layouts.email-limpio')

@section('title', 'Confirmación de PQR')

@section('content')
    <div style="margin: 30px 0;">
        <h2 style="color: #208040; margin-bottom: 25px; font-size: 22px; font-weight: 600;">
            ✅ Hemos recibido su PQR
        </h2>
        
        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
            Estimado/a <strong>{{ $pqr['nombre'] }}</strong>,
        </p>

        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
            Hemos recibido su solicitud correctamente y está siendo procesada por nuestro equipo.
        </p>
        
        <div style="background: #f8fffe; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #208040;">
            <p style="margin: 0 0 15px 0; font-size: 16px; color: #2c3e50;">
                <strong style="color: #208040;">🆔 Su código de radicado:</strong>
            </p>
            <div style="background: #208040; color: white; padding: 15px; border-radius: 6px; text-align: center; font-size: 20px; font-weight: 600; letter-spacing: 1px;">
                {{ $codigo }}
            </div>
            <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                Guarde este código para consultar el estado de su solicitud
            </p>
        </div>

        <div style="margin: 25px 0;">
            <h3 style="color: #208040; font-size: 16px; margin-bottom: 15px;">📋 Resumen de su solicitud:</h3>
            <div style="background: #ffffff; padding: 15px; border: 1px solid #ddd; border-radius: 6px;">
                <p style="margin: 0 0 10px 0;"><strong>Mensaje:</strong></p>
                <div style="font-style: italic; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                    "{{ $pqr['mensaje'] }}"
                </div>
            </div>
        </div>

        <div style="margin: 25px 0; padding: 20px; background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 6px;">
            <h3 style="color: #208040; margin: 0 0 15px 0; font-size: 16px;">⏱️ Tiempo de respuesta:</h3>
            <p style="margin: 0; color: #2c5530;">
                Nuestro equipo revisará su solicitud y le responderá en <strong>máximo 15 días hábiles</strong>.
            </p>
        </div>

        <div style="margin: 25px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px;">
            <p style="margin: 0; color: #856404;">
                <strong>📞 ¿Necesita atención prioritaria?</strong><br>
                Puede comunicarse al <strong>3112890067</strong>
            </p>
        </div>

        @if($archivoUrl)
        <div style="margin: 25px 0; text-align: center;">
            <a href="{{ $archivoUrl }}" 
               style="display: inline-block; background: #208040; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                📎 Ver su archivo adjunto
            </a>
        </div>
        @endif
    </div>
@endsection

@section('footer-text')
    Atentamente, equipo de soporte SETASPLAST SAS BIC
@endsection
