@extends('layouts.email-limpio')

@section('content')
@php
    $usuario  = $envio->usuario;
    $encuesta = $envio->encuesta;
    $capacitacion = $encuesta->capacitacion;
@endphp

<div style="font-family: Arial, Helvetica, sans-serif;">

    {{-- Encabezado --}}
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="display: inline-block; padding: 16px;
                    background: linear-gradient(135deg, #208040 0%, #27ae60 100%);
                    border-radius: 50%; margin-bottom: 16px;">
            <span style="font-size: 36px; color: white; line-height: 1;">📋</span>
        </div>
        <h2 style="color: #2c3e50; font-size: 22px; margin: 0; font-weight: bold;">
            Evaluación de capacitación
        </h2>
        <p style="color: #6c757d; font-size: 14px; margin-top: 8px;">
            Hola <strong style="color: #2c3e50;">{{ $usuario->name }}</strong>,
            te invitamos a responder esta evaluación.
        </p>
    </div>

    {{-- Tarjeta de la capacitación --}}
    <div style="background: #f8fffe; padding: 20px; border-radius: 8px;
                margin-bottom: 24px; border-left: 5px solid #208040;">
        <h3 style="color: #208040; font-size: 16px; margin: 0 0 8px; font-weight: bold;">
            {{ $encuesta->titulo }}
        </h3>
        @if($capacitacion)
        <p style="color: #7f8c8d; font-size: 13px; margin: 0 0 6px;">
            Capacitación:
            <strong style="color: #2c3e50;">{{ $capacitacion->titulo }}</strong>
            @if($capacitacion->fecha_realizacion)
            &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($capacitacion->fecha_realizacion)->format('d/m/Y') }}
            @endif
        </p>
        @endif
        @if($encuesta->descripcion)
        <p style="color: #555; font-size: 14px; margin: 8px 0 0; line-height: 1.6;">
            {{ $encuesta->descripcion }}
        </p>
        @endif
    </div>

    {{-- Botón principal --}}
    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $link }}"
           style="display: inline-block;
                  background: linear-gradient(135deg, #208040 0%, #27ae60 100%);
                  color: white; text-decoration: none; padding: 14px 36px;
                  border-radius: 8px; font-weight: 700; font-size: 16px;
                  box-shadow: 0 4px 12px rgba(32,128,64,0.3); letter-spacing: 0.3px;">
            Responder evaluación &rarr;
        </a>
    </div>

    {{-- Link alternativo --}}
    <div style="margin: 20px 0; padding: 14px; background: #f8f9fa;
                border-radius: 6px; font-size: 12px; color: #888; text-align: center;">
        Si el botón no funciona, copia este enlace en tu navegador:<br>
        <span style="color: #208040; word-break: break-all;">{{ $link }}</span>
    </div>

    {{-- Nota de privacidad --}}
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;
                font-size: 12px; color: #aaa; text-align: center; line-height: 1.6;">
        <p style="margin: 0;">Este enlace es personal e intransferible — expira una vez respondido.</p>
    </div>

</div>
@endsection
