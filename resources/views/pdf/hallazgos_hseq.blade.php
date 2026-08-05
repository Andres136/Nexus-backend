<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4;
            margin: 18mm 15mm 20mm 15mm;

            @bottom-left {
                content: "Generado el {{ $generadoEn->format('d/m/Y H:i') }}{{ $generadoPor ? ' por ' . $generadoPor : '' }}";
                font-size: 8px;
                color: #94a3b8;
                font-family: 'Helvetica', 'Arial', sans-serif;
            }
            @bottom-right {
                content: "Página " counter(page) " de " counter(pages);
                font-size: 8px;
                color: #94a3b8;
                font-family: 'Helvetica', 'Arial', sans-serif;
            }
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.5;
        }

        /* HEADER ESTRUCTURADO */
        .header-container {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border: none;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo {
            width: 130px;
        }

        .report-title {
            text-align: right;
        }

        .report-title h2 {
            margin: 0;
            color: #1e293b;
            font-size: 19px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .report-title p {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 10px;
        }

        .company-tag {
            margin-top: 4px;
            font-size: 8px;
            letter-spacing: 0.5px;
            color: #94a3b8;
            text-transform: uppercase;
        }

        /* RESUMEN DE FILTROS */
        .summary-box {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .summary-box table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .summary-box td {
            border: none;
            padding: 8px 14px;
            font-size: 9.5px;
            color: #64748b;
        }

        .summary-box .summary-value {
            display: block;
            margin-top: 2px;
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
        }

        .summary-divider {
            border-left: 1px solid #e2e8f0;
        }

        /* TABLA ESTILIZADA */
        table.hallazgos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        thead { display: table-header-group; }

        th {
            background-color: #1e293b;
            color: #f8fafc;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 9px 8px;
            text-align: left;
        }

        td {
            padding: 9px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            font-size: 9.5px;
        }

        .row-even {
            background-color: #ffffff;
        }

        .row-odd {
            background-color: #f8fafc;
        }

        .date-cell {
            white-space: nowrap;
            color: #64748b;
            font-family: 'Courier', monospace;
            font-size: 9px;
        }

        .falla-cell {
            color: #7f1d1d;
        }

        .falla-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ef4444;
            margin-right: 5px;
        }

        .falla-respuesta {
            margin-top: 3px;
            font-size: 8px;
            font-weight: bold;
            color: #991b1b;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-abierto {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-cerrado {
            background: #dcfce7;
            color: #166534;
        }

        tr { page-break-inside: avoid; }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #94a3b8;
            font-size: 11px;
        }
    </style>
</head>

<body>

    <div class="header-container">
        <table class="header-table">
            <tr>
                <td>
                    @php $logo = $empresaLogo ?? public_path('images/SETAS.png'); @endphp
                    @if(file_exists($logo))
                        <img src="{{ $logo }}" class="logo">
                    @else
                        <div style="color: #3b82f6; font-weight: bold; font-size: 22px;">SETASPLAST</div>
                    @endif
                </td>
                <td class="report-title">
                    <h2>Informe de Hallazgos</h2>
                    <p>Inspecciones HSEQ — Seguridad, Salud y Medio Ambiente</p>

                </td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <table>
            <tr>
                <td>
                    Total de hallazgos
                    <span class="summary-value">{{ count($hallazgos ?? []) }}</span>
                </td>
                <td class="summary-divider">
                    Inspección
                    <span class="summary-value">{{ $inspeccionFiltro ?? 'Todas las inspecciones' }}</span>
                </td>
                @if(!empty($bodegaFiltro))
                <td class="summary-divider">
                    Bodega
                    <span class="summary-value">{{ $bodegaFiltro }}</span>
                </td>
                @endif
                <td class="summary-divider">
                    Fecha de generación
                    <span class="summary-value">{{ $generadoEn->format('d/m/Y H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="hallazgos">
        <thead>
            <tr>
                <th width="10%">Fecha</th>
                <th width="14%">Sede</th>
                <th width="14%">Inspección</th>
                <th width="24%">Falla Detectada</th>
                <th width="13%">Responsable</th>
                <th width="15%">Observación</th>
                <th width="10%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($hallazgos ?? [] as $index => $h)
            <tr class="{{ $index % 2 == 0 ? 'row-even' : 'row-odd' }}">
                <td class="date-cell">{{ $h->fecha ?? '' }}</td>
                <td>
                    <strong>{{ $h->sede ?? '' }}</strong>
                    @if(!empty($h->bodega))
                        <div style="margin-top:2px; font-size:8px; color:#94a3b8;">{{ $h->bodega }}</div>
                    @endif
                </td>
                <td>{{ $h->tipo_inspeccion ?? '' }}</td>
                <td class="falla-cell">

                    <div class="falla-respuesta"> {{ ($h->respuesta ?? 0) ? 'Sí' : 'No' }}    <span class="falla-dot"></span>{{ $h->pregunta ?? '' }}</div>
                </td>
                <td>{{ $h->responsable ?? '' }}</td>
                <td style="color: #64748b; font-style: italic;">
                    {{ $h->observaciones ? $h->observaciones : 'Sin observaciones' }}
                </td>
                <td>
                    @if(!empty($h->cerrado_en))
                        <span class="status-badge status-cerrado">Cerrado</span>
                    @else
                        <span class="status-badge status-abierto">Abierto</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-state">
                    No se encontraron hallazgos críticos para los filtros seleccionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
