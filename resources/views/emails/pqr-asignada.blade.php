@extends('layouts.email-limpio')

@section('title', 'Nueva PQR Asignada')

@section('content')
    <div style="margin: 30px auto; max-width: 600px; font-family: Arial, Helvetica, sans-serif;">

        {{-- Encabezado --}}
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="color: #208040; font-size: 24px; margin: 0; font-weight: bold;">
                Nueva PQR Asignada
            </h2>
            <p style="color: #6c757d; font-size: 14px; margin-top: 8px;">
                Código de radicado: <strong style="color: #000;">{{ $codigo }}</strong>
            </p>
        </div>

        {{-- Bloque de información --}}
        <div style="background: #f8fffe; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #208040;">
            <h3 style="color: #208040; font-size: 16px; margin-bottom: 12px;">📋 Datos del solicitante</h3>
            <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px 0;"><strong>Nombre:</strong></td>
                    <td>{{ $pqr['nombre'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0;"><strong>Empresa:</strong></td>
                    <td>{{ $pqr['empresa'] ?? 'No especificada' }}</td>
                </tr>
               
            
            </table>
        </div>

        {{-- Mensaje --}}
        <div style="margin: 20px 0;">
            <h3 style="color: #208040; font-size: 16px; margin-bottom: 10px;">💬 Mensaje</h3>
            <div style="background: #ffffff; padding: 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; color: #333;">
                "{{ $pqr['mensaje'] }}"
            </div>
        </div>

        {{-- Estado --}}
        <div style="margin: 20px 0; padding: 15px; background: #fff8e1; border: 1px solid #ffeaa7; border-radius: 6px; font-size: 14px;">
            <strong style="color: #856404;">Estado:</strong> Pendiente de revisión
        </div>

        {{-- Archivo adjunto --}}
        @if($archivoUrl)
        <div style="margin: 25px 0; text-align: center;">
            <a href="{{ $archivoUrl }}" 
               style="display: inline-block; background: #208040; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                📎 Ver archivo adjunto
            </a>
        </div>
        @endif

        {{-- Footer --}}
        <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #888;">
            <p style="margin: 0;">Sistema PQR - <strong>SETASPLAST S.A.S. BIC</strong></p>
            <p style="margin: 5px 0;">Este es un mensaje automático, por favor no responder.</p>
        </div>

    </div>
@endsection
