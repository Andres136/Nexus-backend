@extends('layouts.email-limpio')

@section('content')
@php
    $departamento = $auditoria->departamento?->nombre ?? 'Sin departamento';
    $responsable  = $auditoria->departamento?->responsable;

    $fmtFecha = fn ($f) => $f ? \Carbon\Carbon::parse($f)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') : null;
    $inicio   = $fmtFecha($auditoria->fecha_inicio);
    $fin      = $fmtFecha($auditoria->fecha_fin);
    $rango    = ($auditoria->fecha_inicio === $auditoria->fecha_fin) ? $inicio : "{$inicio} → {$fin}";
    $hora     = $auditoria->hora ? \Carbon\Carbon::parse($auditoria->hora)->format('h:i A') : null;

    $filas = collect([
        ['Departamento a auditar', $departamento],
        ['Fecha', $rango],
        ['Hora', $hora],
        ['Lugar', $auditoria->lugar],
        ['Programada por', $auditoria->creador?->name],
        ['Equipo auditor', $auditoria->participantes->pluck('name')->join(', ') ?: null],
    ])->filter(fn ($fila) => filled($fila[1]));
@endphp

<div class="notification-header">
    <span class="notification-icon task">🗓️</span>
    <h1>Auditoría interna programada</h1>
    <p class="notification-subtitle">
        Hola{{ $responsable?->name ? ' ' . $responsable->name : '' }}, se programó una auditoría
        para <strong>{{ $departamento }}</strong>.
    </p>
</div>

<div class="bg-blue-light border-blue" style="padding: 18px 20px; border-radius: 6px; margin-bottom: 24px;">
    <div class="text-blue" style="font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
        Cronograma
    </div>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #2c3e50;">
        @foreach ($filas as [$etiqueta, $valor])
            <tr>
                <td style="padding: 6px 12px 6px 0; color: #7f8c8d; white-space: nowrap; vertical-align: top;">
                    {{ $etiqueta }}
                </td>
                <td style="padding: 6px 0; font-weight: 600;">{{ $valor }}</td>
            </tr>
        @endforeach
    </table>
</div>

@if ($auditoria->objetivo)
    <p style="color: #555; font-size: 14px; line-height: 1.7; margin-bottom: 14px;">
        <strong style="color: #2c3e50;">Objetivo:</strong> {{ $auditoria->objetivo }}
    </p>
@endif

@if ($auditoria->alcance)
    <p style="color: #555; font-size: 14px; line-height: 1.7; margin-bottom: 14px;">
        <strong style="color: #2c3e50;">Alcance:</strong> {{ $auditoria->alcance }}
    </p>
@endif

<div class="actions">
    <a href="{{ $link }}" class="btn-primary">Ver la auditoría &rarr;</a>
</div>

<div style="margin: 20px 0; padding: 14px; background: #f8f9fa; border-radius: 6px; font-size: 12px; color: #888; text-align: center;">
    Si el botón no funciona, copia este enlace en tu navegador:<br>
    <span class="text-blue" style="word-break: break-all;">{{ $link }}</span>
</div>

<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #aaa; text-align: center; line-height: 1.6;">
    <p style="margin: 0;">Recibes este correo porque eres el responsable del departamento a auditar.</p>
</div>
@endsection
