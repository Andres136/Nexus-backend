@extends('layouts.email-limpio')

@section('title', 'Inicio de Sesión')

@section('content')
    <div style="margin: 30px 0;">
        <h2 style="color: #208040; margin-bottom: 25px; font-size: 22px; font-weight: 600;">
          Inicio de sesión detectado
        </h2>
        
        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
            ¡Hola Administrador!
        </p>
        
        <div style="background: #f8fffe; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #208040;">
            <p style="margin: 0; font-size: 16px; color: #2c3e50;">
                El usuario <strong style="color: #208040;">"{{ $nombreUsuario }}"</strong> ha iniciado sesión en el sistema.
            </p>
        </div>

        <div style="margin: 25px 0; padding: 20px; background: #fff9f0; border: 1px solid #ffd700; border-radius: 6px;">
            <h3 style="color: #b8860b; margin: 0 0 15px 0; font-size: 16px;"> Información del acceso:</h3>
            <ul style="margin: 0; padding-left: 20px; color: #8b4513;">
               <li style="margin: 8px 0;">Fecha y hora: {{ now()->setTimezone('America/Bogota')->format('d/m/Y - H:i:s') }}</li>
                <li style="margin: 8px 0;">Usuario: {{ $nombreUsuario }}</li>
                <li style="margin: 8px 0;">Sistema: SETASPLAST SIG</li>
            </ul>
        </div>

        <div style="margin: 25px 0; text-align: center;">
            <a href="{{ url('/') }}" 
               style="display: inline-block; background: #208040; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                Revisar actividad del sistema
            </a>
        </div>

        <div style="margin: 25px 0; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px;">
            <h3 style="color: #721c24; margin: 0 0 10px 0; font-size: 16px;">Seguridad:</h3>
            <p style="margin: 0; color: #721c24;">
                Si no reconoces esta actividad o crees que puede ser un acceso no autorizado, 
                por favor revisa inmediatamente la seguridad de las cuentas del sistema.
            </p>
        </div>

        <div style="margin: 25px 0; padding: 15px; background: #e8f4fd; border: 1px solid #bee5eb; border-radius: 6px;">
            <p style="margin: 0; color: #0c5460; font-size: 14px;">
                <strong> Nota:</strong> Esta es una notificación automática de seguridad del sistema.
            </p>
        </div>
    </div>
@endsection

@section('footer-text')
    Sistema de seguridad - SETASPLAST SAS BIC
@endsection
