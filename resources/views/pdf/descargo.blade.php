@php
  $nombreEmp = strtolower($empresa->nombre ?? '');
  $esGlobal  = str_contains($nombreEmp, 'global');
  $logoFile  = $esGlobal ? public_path('images/GLOBAL.png') : public_path('images/SETAS.png');
  $colorPrim = $esGlobal ? '#1e3a5f' : '#2d7a27';
  $colorBord = $esGlobal ? '#b8cfe8' : '#c5e0c3';
  $dirEmp    = $empresa->direccion ?? 'Calle 55 # 64-14, Villa del Río';
  $telEmp    = $empresa->telefono  ?? '3112690067';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Descargo</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 32px 50px; line-height: 1.7; }
table { border-collapse: collapse; width: 100%; }
.bold { font-weight: bold; }
.tc { text-align: center; }
.va-top { vertical-align: top; }

.logo { width: 110px; }
.empresa-nombre { font-size: 13px; font-weight: bold; color: {{ $colorPrim }}; }
.empresa-info { font-size: 9px; color: #555; line-height: 1.6; }
.divider { border: none; border-top: 2px solid {{ $colorPrim }}; margin: 10px 0 18px; }

.titulo { font-size: 15px; font-weight: bold; color: {{ $colorPrim }}; text-align: center; letter-spacing: 2px; margin-bottom: 6px; }
.subtitulo { font-size: 9px; color: #888; text-align: center; margin-bottom: 20px; }

.info-block { background: #f7f7f7; border-left: 3px solid {{ $colorPrim }}; padding: 8px 12px; margin-bottom: 16px; font-size: 10px; }
.info-block table td { padding: 2px 12px 2px 0; }
.info-label { color: #555; width: 140px; }
.info-val { font-weight: bold; }

.seccion-titulo { font-size: 10px; font-weight: bold; color: {{ $colorPrim }}; text-transform: uppercase; letter-spacing: 0.5px; margin: 14px 0 6px; border-bottom: 1px solid {{ $colorBord }}; padding-bottom: 3px; }
.cuerpo { font-size: 11px; text-align: justify; line-height: 1.9; margin-bottom: 14px; }

.descripcion-block { background: #fafafa; border: 1px solid #e0e0e0; border-radius: 3px; padding: 12px 14px; margin: 10px 0; font-size: 11px; line-height: 1.9; min-height: 80px; }

.firma-table { margin-top: 50px; }
.firma-table td { text-align: center; font-size: 9px; color: #555; vertical-align: bottom; padding: 0 20px; }
.firma-linea { border-top: 1px solid #555; margin-top: 50px; padding-top: 5px; }

.footer { margin-top: 20px; border-top: 1px solid {{ $colorBord }}; padding-top: 6px; font-size: 8px; color: #aaa; text-align: center; }
</style>
</head>
<body>

<table>
<tr>
  <td style="width:120px;" class="va-top">
    <img src="{{ $logoFile }}" class="logo" alt="{{ $empresa->nombre ?? '' }}">
  </td>
  <td class="va-top">
    <div class="empresa-nombre">{{ $empresa->nombre ?? 'SETASPLAST S.A.S.' }}</div>
    <div class="empresa-info">
      NIT: {{ $empresa->nit ?? 'N/A' }} &nbsp;|&nbsp; Calle 55 # 64-14, Villa del Río<br>
      comercial@setasplast.com &nbsp;·&nbsp; 3112690067
    </div>
  </td>
  <td style="width:100px; text-align:right; vertical-align:top; font-size:9px; color:#888;">
    Ref: DC-{{ str_pad($descargo->id, 4, '0', STR_PAD_LEFT) }}<br>
    {{ \Carbon\Carbon::parse($descargo->fecha_hecho)->format('d/m/Y') }}
  </td>
</tr>
</table>

<hr class="divider">

<div class="titulo">DESCARGO</div>
<div class="subtitulo">Respuesta Formal del Empleado · Recursos Humanos</div>

<div class="info-block">
  <table>
  <tr>
    <td class="info-label">Empleado:</td>
    <td class="info-val">{{ strtoupper($descargo->empleado->name ?? '—') }}</td>
    <td class="info-label" style="padding-left:20px;">Cargo:</td>
    <td class="info-val">{{ $descargo->contratacion->cargo ?? '—' }}</td>
  </tr>
  <tr>
    <td class="info-label">Identificación:</td>
    <td class="info-val">{{ $descargo->contratacion->numero_documento ?? '—' }}</td>
    <td class="info-label" style="padding-left:20px;">Empresa:</td>
    <td class="info-val">{{ $empresa->nombre ?? 'SETASPLAST S.A.S.' }}</td>
  </tr>
  <tr>
    <td class="info-label">Fecha del hecho:</td>
    <td class="info-val">{{ \Carbon\Carbon::parse($descargo->fecha_hecho)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</td>
    <td class="info-label" style="padding-left:20px;">Motivo:</td>
    <td class="info-val">{{ $descargo->tipo_descargo }}</td>
  </tr>
  </table>
</div>

<div class="seccion-titulo">Declaración del Empleado</div>
<div class="cuerpo">
  Yo, <strong>{{ strtoupper($descargo->empleado->name ?? '—') }}</strong>,
  identificado(a) con cédula de ciudadanía No. <strong>{{ $descargo->contratacion->numero_documento ?? '—' }}</strong>,
  en respuesta a la notificación recibida por motivo de
  <strong>{{ $descargo->tipo_descargo }}</strong>
  del día <strong>{{ \Carbon\Carbon::parse($descargo->fecha_hecho)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</strong>,
  manifiesto lo siguiente:
</div>

<div class="descripcion-block">{{ $descargo->descripcion }}</div>

<div class="cuerpo" style="margin-top:14px;">
  Declaro que la información consignada en este descargo es verdadera y la suscribo de forma voluntaria.
</div>

<table class="firma-table">
<tr>
  <td>
    <div class="firma-linea">
      Firma Empleado<br><strong>{{ strtoupper($descargo->empleado->name ?? '—') }}</strong>
    </div>
    <div style="margin-top:6px; font-size:9px; color:#888;">Fecha: ____ / ____ / ______</div>
  </td>
  <td>
    <div class="firma-linea">
      Recibido por<br><strong>{{ $empresa->nombre ?? 'SETASPLAST S.A.S.' }}</strong><br>Gerencia / Recursos Humanos
    </div>
    <div style="margin-top:6px; font-size:9px; color:#888;">Fecha: ____ / ____ / ______</div>
  </td>
</tr>
</table>

<div class="footer">
  Generado por el sistema Nexus · {{ now()->format('d/m/Y H:i') }} · Documento confidencial de uso interno.
</div>

</body>
</html>
