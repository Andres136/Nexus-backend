@php
  $nombreEmp = strtolower($empresa->nombre ?? '');
  $esGlobal  = str_contains($nombreEmp, 'global');
  $logoFile  = !empty($empresa?->logo) ? public_path('storage/' . ltrim($empresa->logo, '/')) : null;
  $colorPrim = $esGlobal ? '#1e3a5f' : '#2d7a27';
  $colorBg   = $esGlobal ? '#e8f0f8' : '#e8f5e3';
  $colorBord = $esGlobal ? '#b8cfe8' : '#c5e0c3';
  $dirEmp    = $empresa?->direccion;
  $telEmp    = $empresa?->telefono;
  $emailEmp  = $empresa?->email;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Desprendible de Nómina</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; margin: 32px 36px; line-height: 1.5; }
table { border-collapse: collapse; width: 100%; }
.w100 { width: 100%; }
.bold { font-weight: bold; }
.tc { text-align: center; }
.tr { text-align: right; }
.tl { text-align: left; }
.va-top { vertical-align: top; }

/* Header */
.header-table td { vertical-align: middle; padding-bottom: 8px; }
.logo { width: 110px; }
.empresa-info { font-size: 9px; color: #444; line-height: 1.6; }
.empresa-nombre { font-size: 12px; font-weight: bold; color: {{ $colorPrim }}; }

/* Título */
.titulo-block { text-align: center; margin: 10px 0 12px; }
.titulo { font-size: 18px; font-weight: bold; color: {{ $colorPrim }}; letter-spacing: 1px; }
.subtitulo { font-size: 10px; color: #555; margin-top: 2px; }

/* Info bloque */
.info-block { background: #f7f7f7; border: 1px solid {{ $colorBord }}; border-radius: 4px; padding: 8px 12px; margin-bottom: 12px; }
.info-block table td { padding: 2px 8px 2px 0; font-size: 9.5px; }
.info-label { color: #555; width: 140px; }
.info-val { font-weight: bold; color: #1a1a1a; }
.nit-periodo { font-size: 9px; color: #666; text-align: right; }

/* Tabla ingresos/deducciones */
.tabla-main { border: 1px solid {{ $colorBord }}; }
.tabla-main th { background: {{ $colorPrim }}; color: #fff; font-size: 10px; padding: 6px 10px; text-align: center; letter-spacing: 0.5px; }
.col-header { background: {{ $colorBg }}; color: {{ $colorPrim }}; font-size: 9px; font-weight: bold; padding: 4px 10px; border-bottom: 1px solid {{ $colorBord }}; }
.fila td { padding: 4px 10px; border-bottom: 1px dotted #e0e0e0; font-size: 9.5px; vertical-align: top; }
.fila td:last-child { text-align: right; }
.fila-total td { padding: 5px 10px; background: {{ $colorBg }}; font-weight: bold; font-size: 10px; border-top: 2px solid {{ $colorPrim }}; }
.fila-total td:last-child { text-align: right; color: {{ $colorPrim }}; }

/* Neto */
.neto-block { margin-top: 10px; background: {{ $colorPrim }}; color: #fff; padding: 10px 16px; border-radius: 4px; }
.neto-label { font-size: 13px; font-weight: bold; letter-spacing: 1px; }
.neto-valor { font-size: 20px; font-weight: bold; text-align: right; }

/* Footer */
.footer { margin-top: 18px; border-top: 1px solid {{ $colorBord }}; padding-top: 6px; font-size: 8px; color: #888; text-align: center; }
.divider { border: none; border-top: 2px solid {{ $colorPrim }}; margin: 10px 0; }
</style>
</head>
<body>

{{-- Header --}}
<table class="header-table w100">
<tr>
  <td style="width:120px;" class="va-top">
    @if($logoFile && file_exists($logoFile))
    <img src="{{ $logoFile }}" class="logo" alt="{{ $empresa->nombre ?? '' }}">
    @endif
  </td>
  <td class="va-top">
    <div class="empresa-nombre">{{ $empresa?->nombre }}</div>
    <div class="empresa-info">
      @if($empresa?->nit) NIT: {{ $empresa->nit }} @endif
      @if($empresa?->nit && $dirEmp)<br>@endif
      {{ $dirEmp }}
      @if(($empresa?->nit || $dirEmp) && ($emailEmp || $telEmp))<br>@endif
      {{ $emailEmp }}
      @if($emailEmp && $telEmp) · @endif
      {{ $telEmp }}
    </div>
  </td>
  <td style="width:140px;" class="va-top tr">
    <div class="nit-periodo">
      <span class="bold">Comprobante #{{ $nomina->id }}</span><br>
      Período: {{ \Carbon\Carbon::parse($nomina->periodo_inicio)->format('d/m/Y') }}
      – {{ \Carbon\Carbon::parse($nomina->periodo_fin)->format('d/m/Y') }}<br>
      Liquidado: {{ \Carbon\Carbon::parse($nomina->fecha_liquidacion)->format('d/m/Y') }}
    </div>
  </td>
</tr>
</table>

<hr class="divider">

<div class="titulo-block">
  <div class="titulo">Comprobante de Nómina</div>
</div>

{{-- Info empleado --}}
<div class="info-block">
  <table>
  <tr>
    <td class="info-label">Nombre:</td>
    <td class="info-val">{{ strtoupper($nomina->empleado->name ?? '—') }}</td>
    <td class="info-label" style="padding-left:30px;">Cargo:</td>
    <td class="info-val">{{ $nomina->contratacion->cargo ?? '—' }}</td>
  </tr>
  <tr>
    <td class="info-label">Identificación:</td>
    <td class="info-val">{{ $nomina->contratacion->numero_documento ?? '—' }}</td>
    <td class="info-label" style="padding-left:30px;">Salario básico:</td>
    <td class="info-val">$ {{ number_format($nomina->salario_base_devengado ?? 0, 0, ',', '.') }}</td>
  </tr>
  </table>
</div>

{{-- Tabla 2 columnas --}}
@php
  $col = 'width:50%;vertical-align:top;';
@endphp
<table class="tabla-main">
<thead>
<tr>
  <th style="width:50%; border-right:1px solid #fff;">INGRESOS</th>
  <th style="width:50%;">DEDUCCIONES</th>
</tr>
</thead>
<tbody>
<tr>
  <td style="{{ $col }} padding:0; border-right:1px solid #c5e0c3;">
    <table style="width:100%;">
      <tr><td class="col-header">Concepto</td><td class="col-header tr">Valor</td></tr>
      <tr class="fila"><td>Sueldo</td><td>$ {{ number_format($nomina->salario_base_devengado ?? 0, 0, ',', '.') }}</td></tr>
      @if(($nomina->auxilio_transporte ?? 0) > 0)
      <tr class="fila"><td>Aux. de transporte</td><td>$ {{ number_format($nomina->auxilio_transporte, 0, ',', '.') }}</td></tr>
      @endif
      @if(($nomina->total_comisiones ?? 0) > 0)
      <tr class="fila"><td>Comisiones</td><td>$ {{ number_format($nomina->total_comisiones, 0, ',', '.') }}</td></tr>
      @endif
      @if(($nomina->valor_horas_extras_diurnas ?? 0) > 0)
      <tr class="fila"><td>H. extras diurnas ({{ $nomina->horas_extras_diurnas }}h)</td><td>$ {{ number_format($nomina->valor_horas_extras_diurnas, 0, ',', '.') }}</td></tr>
      @endif
      @if(($nomina->valor_horas_extras_nocturnas ?? 0) > 0)
      <tr class="fila"><td>H. extras nocturnas ({{ $nomina->horas_extras_nocturnas }}h)</td><td>$ {{ number_format($nomina->valor_horas_extras_nocturnas, 0, ',', '.') }}</td></tr>
      @endif
      @if(($nomina->valor_horas_festivas ?? 0) > 0)
      <tr class="fila"><td>H. festivas ({{ $nomina->horas_festivas }}h)</td><td>$ {{ number_format($nomina->valor_horas_festivas, 0, ',', '.') }}</td></tr>
      @endif
      <tr class="fila-total"><td>Total Ingresos</td><td>$ {{ number_format($nomina->total_devengado ?? 0, 0, ',', '.') }}</td></tr>
    </table>
  </td>
  <td style="{{ $col }} padding:0;">
    <table style="width:100%;">
      <tr><td class="col-header">Concepto</td><td class="col-header tr">Valor</td></tr>
      <tr class="fila"><td>Fondo de salud (4%)</td><td>$ {{ number_format($nomina->deduccion_salud ?? 0, 0, ',', '.') }}</td></tr>
      <tr class="fila"><td>Fondo de pensión (4%)</td><td>$ {{ number_format($nomina->deduccion_pension ?? 0, 0, ',', '.') }}</td></tr>
      @if(($nomina->total_descuentos_adicionales ?? 0) > 0)
      <tr class="fila"><td>Préstamos / Descuentos</td><td>$ {{ number_format($nomina->total_descuentos_adicionales, 0, ',', '.') }}</td></tr>
      @endif
      <tr class="fila-total"><td>Total Deducciones</td><td>$ {{ number_format($nomina->total_deducciones ?? 0, 0, ',', '.') }}</td></tr>
    </table>
  </td>
</tr>
</tbody>
</table>

{{-- Neto --}}
<table class="neto-block w100" style="margin-top:10px;">
<tr>
  <td class="neto-label">NETO A PAGAR</td>
  <td class="neto-valor">$ {{ number_format($nomina->salario_neto ?? 0, 0, ',', '.') }}</td>
</tr>
</table>

{{-- Firmas --}}
<table class="w100" style="margin-top:30px;">
<tr>
  <td style="width:45%; text-align:center; padding-top:4px;">
    <div style="border-top:1px solid #999; padding-top:4px;">
    <div style="font-size:9px; color:#555;">Firma Empleado / {{ strtoupper($nomina->empleado->name ?? '') }}</div>
    </div>
  </td>
  <td style="width:10%;"></td>
  <td style="width:45%; text-align:center; padding-top:4px;">
    @if(!empty($firmaTalentoHumanoPath))
      <img src="{{ $firmaTalentoHumanoPath }}" style="max-width:150px; max-height:48px; display:block; margin:0 auto 2px;" alt="Firma Talento Humano">
    @endif
    <div style="border-top:1px solid #999; padding-top:4px;">
      <div style="font-size:9px; color:#555;">Firma Empresa / Talento Humano</div>
      <div style="font-size:9px; color:#333; line-height:1.45; margin-top:4px; text-transform:uppercase;">
        <strong style="font-size:10px; color:#111;">KRYSTELL RUIZ SANCHEZ</strong><br>
        COORDINACION ADMINISTRATIVA Y RECURSOS HUMANOS<br>
        3134924743
      </div>
    </div>
  </td>
</tr>
</table>

<div class="footer">
  Generado por el sistema Nexus · {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
