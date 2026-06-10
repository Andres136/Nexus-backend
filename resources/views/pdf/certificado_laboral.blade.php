@php
  $nombreEmp = strtolower($empresa->nombre ?? '');
  $esGlobal  = str_contains($nombreEmp, 'global');
  $logoFile  = $esGlobal ? public_path('images/GLOBAL.png') : public_path('images/SETAS.png');
  $colorPrim = $esGlobal ? '#1e3a5f' : '#2d7a27';
  $colorBord = $esGlobal ? '#b8cfe8' : '#c5e0c3';
  $dirEmp    = $empresa->direccion ?? 'Calle 55 # 64-14, Villa del Río';
  $telEmp    = $empresa->telefono  ?? '3112690067';
  $emailEmp  = $empresa->email     ?? 'comercial@setasplast.com.co';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Certificado Laboral</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 32px 50px; line-height: 1.7; }
table { border-collapse: collapse; width: 100%; }
.verde { color: #2d7a27; }
.bold { font-weight: bold; }
.tc { text-align: center; }
.tr { text-align: right; }

.header-table td { vertical-align: middle; padding-bottom: 8px; }
.logo { width: 110px; }
.empresa-nombre { font-size: 13px; font-weight: bold; color: {{ $colorPrim }}; }
.empresa-info { font-size: 9px; color: #555; line-height: 1.6; }
.divider { border: none; border-top: 2px solid {{ $colorPrim }}; margin: 10px 0 18px; }
.titulo { font-size: 15px; font-weight: bold; color: {{ $colorPrim }}; text-align: center; letter-spacing: 2px; margin-bottom: 20px; }
.fecha-ciudad { font-size: 10px; color: #444; margin-bottom: 14px; }
.destinatario { font-size: 10px; margin-bottom: 20px; }
.cuerpo { font-size: 11px; text-align: justify; line-height: 1.9; margin-bottom: 16px; }
.firma-block { margin-top: 50px; }
.firma-img { max-width: 170px; max-height: 58px; display: block; margin-bottom: 2px; }
.firma-linea { border-top: 1px solid #555; width: 200px; margin-top: 8px; padding-top: 4px; font-size: 9px; color: #444; }
.firma-th { font-size: 9px; color: #333; line-height: 1.45; margin-top: 4px; text-transform: uppercase; }
.firma-th strong { font-size: 10px; color: #111; }
.badge { display: inline-block; font-size: 8px; color: #555; }
.footer { margin-top: 30px; border-top: 1px solid #c5e0c3; padding-top: 6px; font-size: 8px; color: #aaa; text-align: center; }
</style>
</head>
<body>

<table class="header-table">
<tr>
  <td style="width:120px;">
    <img src="{{ $logoFile }}" class="logo" alt="{{ $empresa->nombre ?? '' }}">
  </td>
  <td>
    <div class="empresa-nombre">{{ $empresa->nombre ?? 'SETASPLAST S.A.S.' }}</div>
    <div class="empresa-info">
      NIT: {{ $empresa->nit ?? 'N/A' }} &nbsp;|&nbsp; {{ $dirEmp }}<br>
      {{ $emailEmp }} &nbsp;·&nbsp; {{ $telEmp }}
    </div>
  </td>
  <td style="width:80px; text-align:right; vertical-align:top;">
    @if(file_exists(public_path('images/BIC.png')))
    <img src="{{ public_path('images/BIC.png') }}" style="width:40px;" alt="BIC">
    @endif
  </td>
</tr>
</table>

<hr class="divider">

<div class="titulo">CERTIFICADO LABORAL</div>

<div class="fecha-ciudad">
  Villa del Río, {{ $fecha_actual }}
</div>

<div class="destinatario">
  <strong>Señores:</strong><br>
  {{ $dirigido_a ?? 'A QUIEN INTERESE' }}
</div>

<div class="cuerpo">
  <strong>{{ $empresa->nombre ?? 'SETASPLAST S.A.S.' }}</strong>,
  con NIT <strong>{{ $empresa->nit ?? 'N/A' }}</strong>, certifica que
  <strong>{{ strtoupper($contratacion->usuario->name ?? '—') }}</strong>,
  identificado(a) con cédula de ciudadanía No.
  <strong>{{ $contratacion->numero_documento ?? '—' }}</strong>,
  labora en nuestra empresa en el cargo de
  <strong>{{ $contratacion->cargo ?? '—' }}</strong>
  desde el
  <strong>{{ \Carbon\Carbon::parse($contratacion->inicio_contratacion)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</strong>,
  con un salario básico mensual de
  <strong>$ {{ number_format($contratacion->base_salario ?? 0, 0, ',', '.') }}</strong>
  @if(($contratacion->auxilio_transporte ?? 0) > 0)
  más auxilio de transporte de
  <strong>$ {{ number_format($contratacion->auxilio_transporte, 0, ',', '.') }}</strong>
  @endif
  @if($contratacion->no_salarial ?? false)
  y componente no salarial
  @endif.
</div>

<div class="cuerpo">
  Su tipo de contrato es <strong>{{ $contratacion->tipoContrato->nombre ?? '—' }}</strong>
  @if($contratacion->fin_contrato)
  con fecha de terminación el <strong>{{ \Carbon\Carbon::parse($contratacion->fin_contrato)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</strong>
  @else
  a término indefinido
  @endif.
  Al momento de expedición del presente certificado, el empleado se encuentra activo en la empresa.
</div>

<div class="cuerpo">
  La presente certificación se expide a solicitud del interesado(a) para los fines que estime conveniente.
</div>

<div class="firma-block">
  @if(!empty($firmaTalentoHumanoPath))
    <img src="{{ $firmaTalentoHumanoPath }}" class="firma-img" alt="Firma Talento Humano">
  @endif
  <div class="firma-linea">
    Firma Autorizada<br>
    <div class="firma-th">
      <strong>KRYSTELL RUIZ SANCHEZ</strong><br>
      COORDINACION ADMINISTRATIVA Y RECURSOS HUMANOS<br>
      3134924743<br>
      WWW.SETASPLAST.COM.CO
    </div>
  </div>
</div>

<div class="footer">
  Generado por el sistema Nexus · {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
