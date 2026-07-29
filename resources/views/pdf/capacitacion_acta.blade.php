@php
  $logoFile = !empty($empresa?->logo) ? public_path('storage/' . ltrim($empresa->logo, '/')) : null;
  $firmadas = $acta->envios->where('estado', 'firmada')->count();
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta {{ $acta->numero }}</title>
<style>
  @page { margin: 28px 34px; }
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; line-height: 1.45; }
  table { width: 100%; border-collapse: collapse; }
  .header td { vertical-align: middle; }
  .logo { max-width: 115px; max-height: 62px; }
  .company { font-size: 14px; font-weight: bold; color: #174a7e; }
  .muted { color: #64748b; }
  .title { margin: 15px 0 12px; border: 1px solid #94a3b8; background: #f8fafc; padding: 10px; text-align: center; }
  .title h1 { margin: 0 0 3px; font-size: 15px; }
  .meta td { border: 1px solid #cbd5e1; padding: 6px; }
  h2 { margin: 14px 0 5px; color: #174a7e; font-size: 11px; text-transform: uppercase; }
  .text { white-space: pre-wrap; text-align: justify; }
  .grid th, .grid td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
  .grid th { background: #eaf1f8; color: #174a7e; font-size: 9px; }
  .signature { max-width: 105px; max-height: 44px; display: block; margin: 0 auto 2px; }
  .status { font-size: 8px; font-weight: bold; text-transform: uppercase; }
  .signed { color: #15803d; }
  .pending { color: #b45309; }
  .footer { margin-top: 18px; border-top: 1px solid #cbd5e1; padding-top: 6px; text-align: center; color: #94a3b8; font-size: 8px; }
</style>
</head>
<body>
  <table class="header">
    <tr>
      <td style="width:135px">
        @if($logoFile && file_exists($logoFile))
          <img src="{{ $logoFile }}" class="logo" alt="Logo">
        @endif
      </td>
      <td>
        <div class="company">{{ $empresa->nombre }}</div>
        <div class="muted">
          @if($empresa->nit) NIT {{ $empresa->nit }} @endif
          @if($empresa->direccion) · {{ $empresa->direccion }} @endif
          @if($empresa->email)<br>{{ $empresa->email }} @endif
        </div>
      </td>
    </tr>
  </table>

  <div class="title">
    <h1>{{ $acta->titulo }}</h1>
    <strong>ACTA No. {{ $acta->numero }}</strong>
  </div>

  <table class="meta">
    <tr>
      <td><strong>Capacitación:</strong> {{ $acta->capacitacion->titulo }}</td>
      <td style="width:32%"><strong>Fecha:</strong> {{ $acta->capacitacion->fecha_realizacion }}</td>
    </tr>
    <tr>
      <td><strong>Lugar:</strong> {{ $acta->capacitacion->lugar ?: 'No especificado' }}</td>
      <td><strong>Modalidad:</strong> {{ ucfirst($acta->capacitacion->modalidad) }}</td>
    </tr>
    <tr>
      <td><strong>Elaborada por:</strong> {{ trim($acta->elaborador->name . ' ' . $acta->elaborador->apellidos) }}</td>
      <td><strong>Firmas:</strong> {{ $firmadas }} / {{ $acta->envios->count() }}</td>
    </tr>
  </table>

  @if($acta->objetivo)
    <h2>Objetivo</h2>
    <div class="text">{{ $acta->objetivo }}</div>
  @endif

  <h2>Desarrollo</h2>
  <div class="text">{{ $acta->desarrollo }}</div>

  @if(!empty($acta->compromisos))
    <h2>Compromisos</h2>
    <table class="grid">
      <thead><tr><th>Compromiso</th><th style="width:24%">Responsable</th><th style="width:17%">Fecha</th></tr></thead>
      <tbody>
        @foreach($acta->compromisos as $compromiso)
          <tr>
            <td>{{ $compromiso['descripcion'] ?? '' }}</td>
            <td>{{ $compromiso['responsable'] ?? '—' }}</td>
            <td>{{ $compromiso['fecha'] ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @if($acta->conclusiones)
    <h2>Conclusiones</h2>
    <div class="text">{{ $acta->conclusiones }}</div>
  @endif

  <h2>Asistentes y firmas — {{ $empresa->nombre }}</h2>
  <table class="grid">
    <thead>
      <tr>
        <th>Nombre completo</th>
        <th style="width:18%">Cédula</th>
        <th style="width:25%">Firma</th>
        <th style="width:17%">Fecha</th>
      </tr>
    </thead>
    <tbody>
      @foreach($acta->envios as $envio)
        <tr>
          <td>{{ trim(($envio->usuario->name ?? '') . ' ' . ($envio->usuario_apellidos ?: $envio->usuario->apellidos)) }}</td>
          <td>{{ $envio->numero_documento ?: '—' }}</td>
          <td style="text-align:center">
            @if($envio->firma_imagen)
              <img src="{{ $envio->firma_imagen }}" class="signature" alt="Firma">
            @endif
            <span class="status {{ $envio->estado === 'firmada' ? 'signed' : 'pending' }}">
              {{ $envio->estado === 'firmada' ? 'Firmada' : 'Pendiente' }}
            </span>
          </td>
          <td>{{ $envio->firmada_at?->format('d/m/Y H:i') ?: '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="footer">
    Documento generado el {{ now()->format('d/m/Y H:i') }} · Acta {{ $acta->numero }} · {{ $empresa->nombre }}
  </div>
</body>
</html>
