<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra #{{ $orden->id }}</title>
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
        .w100 { width: 100%; }
        .no-border td, .no-border th { border: none; padding: 0; }
        .va-top { vertical-align: top; }
        .va-mid { vertical-align: middle; }
        .tr  { text-align: right; }
        .tc  { text-align: center; }
        .bold { font-weight: bold; }

        /* ── Dividers ───────────────────────────────── */
        .divider {
            border: none;
            border-top: 2px solid #1e3a5f;
            margin: 10px 0;
        }
        .divider-light {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 6px 0;
        }

        /* ── Document title ─────────────────────────── */
        .doc-title  { font-size: 20px; font-weight: bold; color: #1e3a5f; letter-spacing: 2px; }
        .doc-number { font-size: 13px; color: #3b7bc8; font-weight: bold; margin-top: 2px; }
        .doc-meta   { font-size: 9px; color: #64748b; margin-top: 4px; }

        /* ── Info cards ─────────────────────────────── */
        .card {
            border: 1px solid #d1dce8;
            padding: 8px 12px;
            background: #f8fafc;
        }
        .card-dark {
            border: 1px solid #1e3a5f;
            padding: 8px 12px;
            background: #1e3a5f;
            color: #fff;
        }
        .field-label       { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: bold; letter-spacing: .5px; margin-bottom: 2px; }
        .field-label-light { font-size: 8px; text-transform: uppercase; color: #93c5fd; font-weight: bold; letter-spacing: .5px; margin-bottom: 2px; }
        .field-value       { font-size: 11px; color: #1e293b; font-weight: bold; }
        .field-value-light { font-size: 11px; color: #fff; font-weight: bold; }
        .field-sub         { font-size: 9px; color: #475569; margin-top: 1px; }

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
            font-size: 8.5px;
            table-layout: fixed;
            word-wrap: break-word;
        }
        .items-table thead tr { background-color: #1e3a5f; }
        .items-table th {
            color: #fff;
            padding: 6px 5px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            border: none;
            letter-spacing: .3px;
        }
        .items-table td {
            padding: 5px 5px;
            border-bottom: 1px solid #e8eef5;
            vertical-align: middle;
        }
        .items-table tbody tr.even td { background-color: #f1f5f9; }
        .items-table tbody tr.odd  td { background-color: #ffffff; }
        .items-table .num   { text-align: center; color: #64748b; width: 18px; }
        .items-table .money { text-align: right; }
        .dim-text    { font-size: 7.5px; color: #64748b; margin-top: 1px; }
        .emb-badge {
            font-size: 7px;
            background: #e0e7ff;
            color: #3730a3;
            padding: 1px 4px;
            font-weight: bold;
        }

        /* ── Total block ────────────────────────────── */
        .total-bar {
            background-color: #1e3a5f;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            padding: 7px 12px;
            text-align: right;
            margin-top: 8px;
        }

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
        .signature-line  { border-top: 1px solid #94a3b8; width: 160px; margin-top: 4px; }
        .signature-label { font-size: 8px; color: #64748b; margin-top: 3px; }

        /* ── Footer ─────────────────────────────────── */
        .footer-bar {
            background: #1e3a5f;
            color: #93c5fd;
            font-size: 8px;
            text-align: center;
            padding: 5px;
            margin-top: 20px;
            letter-spacing: .5px;
        }

        .logo { height: 48px; width: auto; }
    </style>
</head>
<body>

{{-- ════════════════════════════════════════════ HEADER ══ --}}
<table class="w100 no-border">
    <tr>
        <td class="va-mid" style="width:55%;">
            <img src="{{ public_path('images/SETAS.png') }}" class="logo" alt="Logo SETAS">
        </td>
        <td class="va-mid tr" style="width:45%;">
            <div class="doc-title">ORDEN DE COMPRA</div>
            <div class="doc-number"># {{ str_pad($orden->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div class="doc-meta">
                Entrega: {{ $orden->fecha_entrega }}
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
                <div class="field-value" style="font-size:13px;">{{ $orden->cliente->nombre }}</div>
                <hr class="divider-light">
                <div class="field-label" style="margin-top:4px;">Ubicación de entrega</div>
                <div class="field-sub">{{ $orden->ubicacion_entrega }}</div>
            </div>
        </td>
        {{-- Meta card --}}
        <td class="va-top" style="width:35%;">
            <div class="card-dark">
                <div class="field-label-light">Creado por</div>
                <div class="field-value-light">{{ $orden->usuario->name }}</div>
                <hr style="border:none; border-top:1px solid rgba(255,255,255,.2); margin:6px 0;">
                <div class="field-label-light">Fecha de entrega</div>
                <div class="field-value-light" style="font-size:9px;">{{ $orden->fecha_entrega }}</div>
            </div>
        </td>
    </tr>
</table>

@if($orden->observaciones)
<div class="obs-box" style="margin-bottom:12px;">
    <span class="bold" style="color:#1e3a5f;">Observaciones: </span>
    {!! nl2br(e($orden->observaciones)) !!}
</div>
@endif

{{-- ══════════════════════════════════════════ ITEMS ══ --}}
<div class="section-title">Detalles del pedido</div>

<table class="items-table">
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th style="text-align:left; width:22%;">Descripción / Medidas</th>
            <th style="width:6%;">CLB</th>
            <th style="width:7%;">Peso/B (g)</th>
            <th style="width:7%;"># Bolsas</th>
            <th style="width:8%;">Kg req.</th>
            <th style="width:8%;">Embalaje</th>
            <th style="width:7%;">Cant.</th>
            <th style="width:12%;">P. Unitario</th>
            <th style="width:12%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orden->detalles as $index => $detalle)
        @php $rowClass = $loop->even ? 'even' : 'odd'; @endphp
        <tr class="{{ $rowClass }}">
            <td class="num">{{ $detalle->observaciones }}</td>
            <td>
                <span class="bold">{{ mb_strtoupper($detalle->descripcion) }}</span>
                @if($detalle->ancho_cm || $detalle->largo_cm || $detalle->calibre)
                <div class="dim-text">
                    @if($detalle->ancho_cm && $detalle->largo_cm)
                        {{ $detalle->ancho_cm }} × {{ $detalle->largo_cm }} cm
                    @endif
                    @if($detalle->calibre)
                        &nbsp;Cal.{{ $detalle->calibre }}
                    @endif
                </div>
                @endif
            </td>
            <td class="tc">{{ $detalle->cliente_clb }}</td>
            <td class="tc">{{ $detalle->peso_bolsa }}</td>
            <td class="tc">{{ $detalle->numero_bolsas }}</td>
            <td class="tc">{{ $detalle->cantidad_requerida_kg }}</td>
            <td class="tc">
                @if($detalle->tipo_embalaje)
                    <span class="emb-badge">{{ ucfirst($detalle->tipo_embalaje) }}</span>
                @endif
            </td>
            <td class="tc">{{ $detalle->cantidad }}</td>
            <td class="money">${{ number_format($detalle->valor_unitario, 2, ',', '.') }}</td>
            <td class="money bold" style="color:#15803d;">${{ number_format($detalle->valor_total, 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ═════════════════════════════════════════ TOTAL ══ --}}
<div class="total-bar">
    Valor Total Orden: &nbsp; ${{ number_format($orden->valor_total, 0, ',', '.') }} COP
</div>

{{-- ═══════════════════════════════════════ FOOTER ══ --}}
<div class="footer-bar">
    Documento generado el {{ now()->format('d/m/Y H:i') }}
    &nbsp;·&nbsp;
    Orden de Compra # {{ str_pad($orden->id, 5, '0', STR_PAD_LEFT) }}
</div>

</body>
</html>
