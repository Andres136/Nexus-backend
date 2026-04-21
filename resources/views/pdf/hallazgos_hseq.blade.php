<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4;
            margin: 15mm;
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
            margin-bottom: 30px;
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
            width: 140px;
        }

        .report-title {
            text-align: right;
        }

        .report-title h2 {
            margin: 0;
            color: #1e293b;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* RESUMEN DE FILTROS */
        .summary-box {
            background-color: #f8fafc;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .summary-box span {
            margin-right: 20px;
            font-size: 10px;
            color: #64748b;
        }

        .summary-box strong {
            color: #1e293b;
        }

        /* TABLA ESTILIZADA */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            padding: 10px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            font-size: 10px;
        }

        .row-even {
            background-color: #ffffff;
        }

        .row-odd {
            background-color: #fafafa;
        }

        .date-cell {
            white-space: nowrap;
            color: #64748b;
            font-family: 'Courier', monospace;
        }

        .status-badge {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8px;
        }

        tr { page-break-inside: avoid; }

        /* FOOTER */
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    <div class="header-container">
        <table class="header-table">
            <tr>
                <td>
                    @php $logo = public_path('images/logo.png'); @endphp
                    @if(file_exists($logo))
                        <img src="{{ $logo }}" class="logo">
                    @else
                        <div style="color: #3b82f6; font-weight: bold; font-size: 24px;">NEXUS</div>
                    @endif
                </td>
                <td class="report-title">
                    <h2>Reporte de Hallazgos</h2>
                    <p style="margin: 5px 0 0; color: #64748b;">HSEQ Gestión de Seguridad</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <span><strong>Tipo:</strong> {{ $filters['tipo_inspeccion_id'] ?? 'Todos' }}</span>

        <span><strong>Total:</strong> {{ count($hallazgos ?? []) }} registros</span>
    </div>

    <table>
        <thead>
            <tr>
                <th width="12%">Fecha</th>
                <th width="15%">Sede</th>
                <th width="15%">Inspección</th>
                <th width="25%">Falla Detectada</th>
                <th width="15%">Responsable</th>
                <th width="18%">Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($hallazgos ?? [] as $index => $h)
            <tr class="{{ $index % 2 == 0 ? 'row-even' : 'row-odd' }}">
                <td class="date-cell">{{ $h->fecha ?? '' }}</td>
                <td><strong>{{ $h->sede ?? '' }}</strong></td>
                <td>{{ $h->tipo_inspeccion ?? '' }}</td>
                <td><span style="color: #ef4444;">•</span> {{ $h->pregunta ?? '' }}</td>
                <td>{{ $h->responsable ?? '' }}</td>
                <td style="color: #64748b; font-style: italic;">
                    {{ $h->observaciones ? $h->observaciones : 'Sin observaciones' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                    No se encontraron hallazgos críticos para los filtros seleccionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ date('d/m/Y H:i A') }} - Sistema de Gestión HSEQ
    </div>

</body>
</html>