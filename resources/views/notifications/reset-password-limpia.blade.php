@extends('layouts.email-limpio')

@section('title', 'Restablecer contraseña')

@section('content')
    <div style="margin: 30px 0;">
        <h2 style="color: #208040; margin-bottom: 25px; font-size: 22px; font-weight: 600;">
            Restablece tu contraseña
        </h2>

        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
            ¡Hola {{ $usuario->name }}!
        </p>

        <div style="background: #f8fffe; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #208040;">
            <p style="margin: 0; font-size: 16px; color: #2c3e50;">
                Recibimos una solicitud para restablecer la contraseña de tu cuenta en {{ config('app.name') }}.
            </p>
        </div>

        <div style="margin: 25px 0; text-align: center;">
            <a href="{{ $url }}"
               style="display: inline-block; background: #208040; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                Restablecer contraseña
            </a>
        </div>

        <div style="margin: 25px 0; padding: 15px; background: #fff9f0; border: 1px solid #ffd700; border-radius: 6px;">
            <p style="margin: 0; color: #8b4513; font-size: 14px;">
                Este enlace expira en {{ $expira }} minutos.
            </p>
        </div>

        <div style="margin: 25px 0; padding: 15px; background: #e8f4fd; border: 1px solid #bee5eb; border-radius: 6px;">
            <p style="margin: 0; color: #0c5460; font-size: 14px;">
                Si tú no solicitaste este cambio, puedes ignorar este correo: tu contraseña seguirá siendo la misma.
            </p>
        </div>

        <p style="margin-top: 25px; font-size: 13px; color: #7f8c8d; word-break: break-all;">
            Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
            <a href="{{ $url }}" style="color: #208040;">{{ $url }}</a>
        </p>
    </div>
@endsection

@section('footer-text')
    Sistema de seguridad - SETASPLAST SAS BIC
@endsection
