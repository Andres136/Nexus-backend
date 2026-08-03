<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización #{{ $cotizacion->id }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 28px 32px;
            line-height: 1.4;
        }

        /* ── Layout helpers ─────────────────────────── */
        .w100  { width: 100%; }
        .no-border td, .no-border th { border: none; padding: 0; }
        .va-top { vertical-align: top; }
        .va-mid { vertical-align: middle; }
        .tr  { text-align: right; }
        .tc  { text-align: center; }
        .tl  { text-align: left; }
        .bold { font-weight: bold; }

        /* ── Divider ────────────────────────────────── */
        .divider {
            border: none;
            border-top: 2px solid #1e3a5f;
            margin: 10px 0;
        }
        .divider-light {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 8px 0;
        }

        /* ── Document title block ───────────────────── */
        .doc-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 2px;
        }
        .doc-number {
            font-size: 13px;
            color: #3b7bc8;
            font-weight: bold;
            margin-top: 2px;
        }
        .doc-meta {
            font-size: 9px;
            color: #64748b;
            margin-top: 4px;
        }

        /* ── Client info card ───────────────────────── */
        .card {
            border: 1px solid #d1dce8;
            padding: 8px 12px;
            background: #f8fafc;
        }
        .card-dark {
            border: 1px solid #1e3a5f;
            padding: 8px 12px;
            background: #1e3a5f;
            color: #ffffff;
        }
        .field-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .field-label-light {
            font-size: 8px;
            text-transform: uppercase;
            color: #93c5fd;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .field-value {
            font-size: 11px;
            color: #1e293b;
            font-weight: bold;
        }
        .field-value-light {
            font-size: 11px;
            color: #ffffff;
            font-weight: bold;
        }
        .field-sub {
            font-size: 9px;
            color: #475569;
            margin-top: 1px;
        }
        .field-sub-light {
            font-size: 9px;
            color: #bfdbfe;
            margin-top: 1px;
        }

        /* ── Section title ──────────────────────────── */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 3px;
            margin-top: 14px;
            margin-bottom: 8px;
        }

        /* ── Items table ────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        .items-table thead tr {
            background-color: #1e3a5f;
        }
        .items-table th {
            color: #ffffff;
            padding: 6px 7px;
            text-align: center;
            font-size: 8.5px;
            font-weight: bold;
            border: none;
            letter-spacing: 0.3px;
        }
        .items-table td {
            padding: 5px 7px;
            border-bottom: 1px solid #e8eef5;
            vertical-align: middle;
        }
        .items-table tbody tr.even td {
            background-color: #f1f5f9;
        }
        .items-table tbody tr.odd td {
            background-color: #ffffff;
        }
        .items-table .num  { text-align: center; color: #64748b; width: 20px; }
        .items-table .money { text-align: right; font-variant-numeric: tabular-nums; }
        .items-table .iva-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 1px 5px;
            font-size: 8px;
            font-weight: bold;
        }

        /* ── Totals ─────────────────────────────────── */
        .totals-table {
            width: 200px;
            border-collapse: collapse;
            font-size: 10px;
            float: right;
            margin-top: 6px;
        }
        .totals-table td {
            padding: 4px 10px;
            border: none;
        }
        .totals-label { color: #64748b; }
        .totals-value { text-align: right; font-weight: bold; color: #1e293b; }
        .totals-total-row td {
            background-color: #1e3a5f;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 6px 10px;
        }
        .totals-iva-row td { color: #475569; font-size: 9px; }
        .clearfix { clear: both; }

        /* ── Observations ───────────────────────────── */
        .obs-box {
            border-left: 3px solid #1e3a5f;
            padding: 8px 12px;
            background: #f8fafc;
            font-size: 9px;
            color: #334155;
            line-height: 1.7;
        }

        /* ── Signature ──────────────────────────────── */
        .signature-block {
            margin-top: 36px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            width: 160px;
            margin-top: 4px;
        }
        .signature-label {
            font-size: 8px;
            color: #64748b;
            margin-top: 3px;
        }

        /* ── Footer bar ─────────────────────────────── */
        .footer-bar {
            background: #1e3a5f;
            color: #93c5fd;
            font-size: 8px;
            text-align: center;
            padding: 5px;
            margin-top: 20px;
            letter-spacing: 0.5px;
        }

        .logo { height: 48px; width: auto; }
    </style>
</head>
<body>

@php
function sinCeros($valor) {
    if (!is_numeric($valor)) return '0';
    return rtrim(rtrim(number_format((float)$valor, 2, '.', ''), '0'), '.');
}
function money($valor) {
    return is_numeric($valor)
        ? '$ ' . number_format((float)$valor, 0, ',', '.')
        : '$ 0';
}

$subtotal   = $cotizacion->detalles->sum('valor_paquete');
$ivaTotal   = $cotizacion->detalles->sum('valor_total') - $subtotal;
$grandTotal = $cotizacion->detalles->sum('valor_total');
@endphp

{{-- ═══════════════════════════════════════════ HEADER ══ --}}
<table class="w100 no-border">
    <tr>
        {{-- Logos --}}
        <td class="va-mid" style="width:55%;">
            @if(!empty($logo) && file_exists($logo))
                <img class="logo" src="{{ $logo }}" alt="{{ $cotizacion->empresaReal?->nombre ?? $cotizacion->empresa }}">
            @endif
            <img class="logo" src="{{ public_path('images/BIC.png') }}"     alt="BIC"     style="margin-left:8px;">
            <img class="logo" src="{{ public_path('images/FENALCO.png') }}" alt="Fenalco" style="margin-left:8px;">
        </td>
        {{-- Title block --}}
        <td class="va-mid tr" style="width:45%;">
            <div class="doc-title">COTIZACIÓN</div>
            <div class="doc-number"># {{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div class="doc-meta">
                Fecha: {{ $cotizacion->updated_at->format('d/m/Y') }}
                &nbsp;|&nbsp;
                Empresa: {{ $cotizacion->empresaReal?->nombre ?? $cotizacion->empresa }}
            </div>
        </td>
    </tr>
</table>

<hr class="divider">

{{-- ═══════════════════════════════════════ CLIENT INFO ══ --}}
<table class="w100 no-border" style="margin-bottom:12px;">
    <tr>
        {{-- Client card --}}
        <td class="va-top" style="width:65%; padding-right:10px;">
            <div class="card">
                <div class="field-label">Cliente</div>
                <div class="field-value" style="font-size:13px;">
                    {{ $cotizacion->cliente->nombre ?? 'N/A' }}
                </div>
                <hr class="divider-light">
                <table class="w100 no-border">
                    <tr>
                        <td style="width:50%;">
                            <div class="field-label">Teléfono</div>
                            <div class="field-sub">{{ $cotizacion->cliente->telefono ?? '—' }}</div>
                        </td>
                        <td style="width:50%;">
                            <div class="field-label">Correo</div>
                            <div class="field-sub">{{ $cotizacion->cliente->email ?? '—' }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
        {{-- Meta card --}}
        <td class="va-top" style="width:35%;">
            <div class="card-dark">
                <div class="field-label-light">Elaborado por</div>
                <div class="field-value-light">{{ $cotizacion->user->name }}</div>
                <hr style="border:none; border-top:1px solid rgba(255,255,255,0.2); margin:6px 0;">


                <hr style="border:none; border-top:1px solid rgba(255,255,255,0.2); margin:6px 0;">
                <div class="field-label-light">Fecha emisión</div>
                <div class="field-value-light" style="font-size:9px;">
                    {{ $cotizacion->updated_at->format('d \\d\\e F \\d\\e Y') }}
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════ ITEMS ══ --}}
<div class="section-title">Detalles de la cotización</div>

<table class="items-table">
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th class="tl">Descripción</th>
            <th>Unid/Paq</th>
            <th>Precio Unit.</th>
            <th>IVA</th>
            <th>Subtotal</th>
            <th>Total c/IVA</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($cotizacion->detalles as $item)
        @php
            $iva = $item->iva_porcentaje ?? 19;
            $rowClass = $loop->even ? 'even' : 'odd';
        @endphp
        <tr class="{{ $rowClass }}">
            <td class="num">{{ $item->item }}</td>
            <td>
                <span class="bold">{{ mb_strtoupper($item->descripcion) }}</span>
                @if($item->ancho_cm && $item->largo_cm)
                    <br><span style="color:#64748b; font-size:8.5px;">
                        {{ sinCeros($item->ancho_cm) }} × {{ sinCeros($item->largo_cm) }}
                        @if($item->cliente_clb)
                            &nbsp;Cal. {{ sinCeros($item->cliente_clb) }}
                        @endif
                    </span>
                @endif

            </td>
            <td class="tc">{{ number_format($item->cantidad, 0, ',', '.') }}</td>
            <td class="money">{{ money($item->valor_unitario) }}</td>
            <td class="tc">
                <span class="iva-badge">{{ $iva }}%</span>
            </td>
            <td class="money" style="color:#4f46e5;">{{ money($item->valor_paquete) }}</td>
            <td class="money bold" style="color:#15803d;">{{ money($item->valor_total) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ══════════════════════════════════════════ TOTALS ══ --}}
<table class="totals-table">
    <tr class="totals-iva-row">
        <td class="totals-label">Subtotal</td>
        <td class="totals-value">{{ money($subtotal) }}</td>
    </tr>
    <tr class="totals-iva-row">
        <td class="totals-label">IVA</td>
        <td class="totals-value">{{ money($ivaTotal) }}</td>
    </tr>
    <tr class="totals-total-row">
        <td>TOTAL COP</td>
        <td style="text-align:right;">{{ money($grandTotal) }}</td>
    </tr>
</table>
<div class="clearfix"></div>

{{-- ══════════════════════════════════════ OBSERVATIONS ══ --}}
<div class="section-title">Condiciones y observaciones</div>
<div class="obs-box">
    {!! nl2br(e($cotizacion->observaciones ?? 'Sin observaciones')) !!}
</div>

{{-- ══════════════════════════════════════════ FIRMA ══ --}}
@php
$firmante     = $cotizacion->user;
$relativePath = $firmante && $firmante->imagen ? 'app/public/' . $firmante->imagen : null;
$path         = $relativePath && file_exists(storage_path($relativePath))
                ? storage_path($relativePath)
                : public_path('images/firma-por-defecto.png');

$base64 = null;
if (file_exists($path)) {
    $type   = pathinfo($path, PATHINFO_EXTENSION);
    $data   = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}
@endphp

<div class="signature-block">
    <table class="w100 no-border">
        <tr>
            <td class="va-top" style="width:50%;">
                @if($base64)
                    <img src="{{ $base64 }}" alt="Firma" style="max-height:70px; width:auto; display:block;">
                @else
                    <div style="height:40px; color:#cbd5e1; font-size:9px; font-style:italic;">Firma no disponible</div>
                @endif
                <div class="signature-line"></div>
                <div class="bold" style="font-size:10px; margin-top:3px; color:#1e293b;">
                    {{ $firmante?->name ?? '' }}
                </div>
                <div class="signature-label">Asesor comercial autorizado</div>
            </td>
            <td class="va-top tr" style="width:50%;">
                <div style="font-size:9px; color:#64748b;">
                    Este documento es una cotización formal y no<br>
                    constituye un compromiso de venta hasta ser<br>
                    aceptada por ambas partes.
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ══════════════════════════════════════════ FOOTER ══ --}}
<div class="footer-bar">
    Documento generado el {{ now()->format('d/m/Y H:i') }}
    &nbsp;·&nbsp;
    Cotización # {{ str_pad($cotizacion->id, 5, '0', '0') }}
    &nbsp;·&nbsp;
    {{ $cotizacion->empresaReal?->nombre ?? $cotizacion->empresa }}
</div>

</body>
</html>
