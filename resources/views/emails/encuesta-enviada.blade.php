@extends('layouts.email-limpio')

@section('content')
<div style="margin: 30px auto; max-width: 600px; font-family: Arial, Helvetica, sans-serif;">

    {{-- Encabezado --}}
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="display: inline-block; padding: 16px; background: linear-gradient(135deg, #208040 0%, #27ae60 100%); border-radius: 50%; margin-bottom: 16px;">
            <span style="font-size: 36px;">📋</span>
        </div>
        <h2 style="color: #2c3e50; font-size: 22px; margin: 0; font-weight: bold;">
            Tu opinión nos importa
        </h2>
        <p style="color: #6c757d; font-size: 14px; margin-top: 8px;">
            Hola <strong>{{ $envio->cliente->nombre }}</strong>, hemos preparado una encuesta para ti.
        </p>
    </div>

    {{-- Descripción de la encuesta --}}
    <div style="background: #f8fffe; padding: 20px; border-radius: 8px; margin-bottom: 24px; border-left: 5px solid #208040;">
        <h3 style="color: #208040; font-size: 16px; margin: 0 0 8px;">
            {{ $envio->encuesta->titulo }}
        </h3>
        @if($envio->encuesta->descripcion)
        <p style="color: #555; font-size: 14px; margin: 0; line-height: 1.6;">
            {{ $envio->encuesta->descripcion }}
        </p>
        @endif
    </div>

    {{-- Info de preguntas --}}
    <div style="margin-bottom: 24px; padding: 14px 18px; background: #f0f4f8; border-radius: 8px; font-size: 14px; color: #555;">
        <strong style="color: #2c3e50;">📝 Preguntas:</strong>
        {{ $envio->encuesta->preguntas->count() }} preguntas &nbsp;|&nbsp;
        <strong style="color: #2c3e50;">⏱ Tiempo estimado:</strong>
        {{ ceil($envio->encuesta->preguntas->count() * 0.5) }}–{{ $envio->encuesta->preguntas->count() }} min
    </div>

    {{-- Botón principal --}}
    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $link }}"
           style="display: inline-block; background: linear-gradient(135deg, #208040 0%, #27ae60 100%);
                  color: white; text-decoration: none; padding: 14px 36px;
                  border-radius: 8px; font-weight: 700; font-size: 16px;
                  box-shadow: 0 4px 12px rgba(32,128,64,0.3); letter-spacing: 0.3px;">
            Responder encuesta →
        </a>
    </div>

    {{-- Link alternativo --}}
    <div style="margin: 20px 0; padding: 14px; background: #f8f9fa; border-radius: 6px; font-size: 12px; color: #888; text-align: center;">
        Si el botón no funciona, copia este enlace en tu navegador:<br>
        <span style="color: #208040; word-break: break-all;">{{ $link }}</span>
    </div>

    {{-- Nota de privacidad --}}
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #aaa; text-align: center; line-height: 1.6;">
        <p style="margin: 0;">Tus respuestas son confidenciales y solo serán usadas para mejorar nuestro servicio.</p>
        <p style="margin: 6px 0 0;">Este enlace es personal e intransferible — expira una vez respondido.</p>
    </div>

</div>
@endsection
