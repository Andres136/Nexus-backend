@extends('layouts.email-limpio')

@section('content')
@php
    $responsable = $lote->responsable;
@endphp

<div style="font-family: Arial, Helvetica, sans-serif;">

    {{-- Encabezado --}}
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="display: inline-block; padding: 16px;
                    background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
                    border-radius: 50%; margin-bottom: 16px;">
            <span style="font-size: 36px; color: white; line-height: 1;">💰</span>
        </div>
        <h2 style="color: #2c3e50; font-size: 22px; margin: 0; font-weight: bold;">
            Nómina pendiente de aprobación
        </h2>
        <p style="color: #6c757d; font-size: 14px; margin-top: 8px;">
            Hola <strong style="color: #2c3e50;">{{ $responsable->name }}</strong>,
            hay un lote de nómina esperando tu aprobación.
        </p>
    </div>

    {{-- Tarjeta del lote --}}
    <div style="background: #f5f5ff; padding: 20px; border-radius: 8px;
                margin-bottom: 24px; border-left: 5px solid #4338ca;">
        <h3 style="color: #4338ca; font-size: 16px; margin: 0 0 8px; font-weight: bold;">
            Período: {{ $lote->periodo_inicio->format('d/m/Y') }} al {{ $lote->periodo_fin->format('d/m/Y') }}
        </h3>
        <p style="color: #7f8c8d; font-size: 13px; margin: 0 0 6px;">
            Empleados incluidos: <strong style="color: #2c3e50;">{{ $totalEmpleados }}</strong>
        </p>
        <p style="color: #7f8c8d; font-size: 13px; margin: 0;">
            Generado por: <strong style="color: #2c3e50;">{{ $lote->generadoPor?->name }}</strong>
        </p>
    </div>

    <p style="color: #555; font-size: 14px; line-height: 1.6; text-align: center;">
        Al aprobar, la nómina de estos empleados quedará liquidada de inmediato.
        Revisa el detalle antes de confirmar.
    </p>

    {{-- Botón principal --}}
    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $link }}"
           style="display: inline-block;
                  background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
                  color: white; text-decoration: none; padding: 14px 36px;
                  border-radius: 8px; font-weight: 700; font-size: 16px;
                  box-shadow: 0 4px 12px rgba(67,56,202,0.3); letter-spacing: 0.3px;">
            Revisar y aprobar &rarr;
        </a>
    </div>

    {{-- Link alternativo --}}
    <div style="margin: 20px 0; padding: 14px; background: #f8f9fa;
                border-radius: 6px; font-size: 12px; color: #888; text-align: center;">
        Si el botón no funciona, copia este enlace en tu navegador:<br>
        <span style="color: #4338ca; word-break: break-all;">{{ $link }}</span>
    </div>

    {{-- Nota --}}
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;
                font-size: 12px; color: #aaa; text-align: center; line-height: 1.6;">
        <p style="margin: 0;">Debes iniciar sesión con tu cuenta para poder aprobar este lote.</p>
    </div>

</div>
@endsection
