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
<title>Llamado de Atención</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 32px 50px; line-height: 1.7; }
table { border-collapse: collapse; width: 100%; }
.bold { font-weight: bold; }
.tc { text-align: center; }
.tr { text-align: right; }
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

.severidad { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: bold; }
.severidad-leve { background: #fef9c3; color: #854d0e; }
.severidad-moderado { background: #fed7aa; color: #9a3412; }
.severidad-grave { background: #fee2e2; color: #991b1b; }

.seccion-titulo { font-size: 10px; font-weight: bold; color: {{ $colorPrim }}; text-transform: uppercase; letter-spacing: 0.5px; margin: 14px 0 6px; border-bottom: 1px solid {{ $colorBord }}; padding-bottom: 3px; }
.cuerpo { font-size: 11px; text-align: justify; line-height: 1.9; margin-bottom: 14px; }
.nota { font-size: 10px; color: #555; font-style: italic; border-left: 3px solid #f59e0b; padding-left: 10px; margin: 14px 0; }

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
    Ref: LA-{{ str_pad($llamado->id, 4, '0', STR_PAD_LEFT) }}<br>
    {{ \Carbon\Carbon::parse($llamado->fecha_hecho)->format('d/m/Y') }}
  </td>
</tr>
</table>

<hr class="divider">

<div class="titulo">LLAMADO DE ATENCIÓN</div>
<div class="subtitulo">Documento Formal · Recursos Humanos</div>

<div class="info-block">
  <table>
  <tr>
    <td class="info-label">Empleado:</td>
    <td class="info-val">{{ strtoupper($llamado->empleado->name ?? '—') }}</td>
    <td class="info-label" style="padding-left:20px;">Cargo:</td>
    <td class="info-val">{{ $llamado->contratacion->cargo ?? '—' }}</td>
  </tr>
  <tr>
    <td class="info-label">Fecha del hecho:</td>
    <td class="info-val">{{ \Carbon\Carbon::parse($llamado->fecha_hecho)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</td>
    <td class="info-label" style="padding-left:20px;">Tipo de falta:</td>
    <td class="info-val">{{ $llamado->titulo ?? ucfirst($llamado->tipo) }}</td>
  </tr>
  <tr>
    <td class="info-label">Severidad:</td>
    <td colspan="3">
      <span class="severidad severidad-{{ $llamado->severidad }}">{{ strtoupper($llamado->severidad) }}</span>
    </td>
  </tr>
  </table>
</div>

<div class="seccion-titulo">Descripción de los Hechos</div>
<div class="cuerpo">{{ $descripcion ?: ($llamado->detalle ?? 'Sin descripción registrada.') }}</div>

<div class="seccion-titulo">Notificación Formal</div>
<div class="cuerpo">
  Por medio del presente documento se notifica formalmente a
  <strong>{{ strtoupper($llamado->empleado->name ?? '—') }}</strong>
  que los hechos descritos constituyen una falta a las obligaciones laborales y al reglamento interno de
  <strong>{{ $empresa->nombre ?? 'SETASPLAST S.A.S.' }}</strong>.
  Se insta al empleado a corregir dicha conducta y se advierte que la reincidencia podrá dar lugar a medidas disciplinarias más severas de conformidad con el Código Sustantivo del Trabajo.
</div>

<div class="nota">
  El empleado tiene derecho a presentar sus descargos dentro de los cinco (5) días hábiles siguientes a la recepción de este documento.
</div>

<table class="firma-table">
<tr>
  <td>
    <div class="firma-linea">
      Firma Empleado<br><strong>{{ strtoupper($llamado->empleado->name ?? '—') }}</strong>
    </div>
    <div style="margin-top:6px; font-size:9px; color:#888;">Fecha: ____ / ____ / ______</div>
  </td>
  <td>
    <div class="firma-linea">
      Firma Empresa<br><strong>{{ $empresa->nombre ?? 'SETASPLAST S.A.S.' }}</strong><br>Gerencia / Recursos Humanos
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
