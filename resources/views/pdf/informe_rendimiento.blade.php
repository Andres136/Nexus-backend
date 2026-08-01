<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informe de Rendimiento</title>

<style>
@page {
    size: A4 portrait;
    margin: 10mm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 9px;
    color: #333;
    margin: 0;
}

.header {
    display: flex;
    align-items: center;
    border-bottom: 2px solid #1f6fd2;
    padding-bottom: 4px;
    margin-bottom: 10px;
}
.header img {
    height: 28px;
}
.header h2 {
    flex: 1;
    text-align: center;
    margin: 0;
    font-size: 13px;
    color: #1f2d3d;
}
.header span {
    font-size: 8px;
    color: #6b7280;
}

.seccion {
    border: 1px solid #d1d5db;
    margin-bottom: 8px;
    page-break-inside: avoid;
}
.seccion-titulo {
    background: #f4f9ff;
    border-bottom: 1px solid #d1d5db;
    padding: 4px 6px;
    font-size: 10px;
    font-weight: bold;
    color: #1f2d3d;
    text-transform: uppercase;
}
.seccion-cuerpo {
    padding: 6px;
}
.narrativa {
    white-space: pre-line;
    line-height: 1.4;
    font-size: 9px;
}

table.kpi {
    width: 100%;
    border-collapse: collapse;
}
table.kpi td {
    padding: 2px 4px;
    border-bottom: 1px solid #eef2f7;
}
table.kpi td.etiqueta { color: #6b7280; width: 60%; }
table.kpi td.valor { font-weight: 600; text-align: right; }

table.detalle {
    width: 100%;
    border-collapse: collapse;
}
table.detalle th {
    background: #eef2f7;
    color: #1f2d3d;
    padding: 3px;
    border: 1px solid #e5e7eb;
    font-size: 8px;
}
table.detalle td {
    border: 1px solid #e5e7eb;
    padding: 3px;
    text-align: center;
    word-wrap: break-word;
    font-size: 8px;
}
table.detalle td.izq { text-align: left; }

.chat-turno { margin-bottom: 4px; }
.chat-turno strong { text-transform: uppercase; font-size: 8px; color: #6b7280; }
</style>
</head>

<body>

<div class="header">
    <img src="{{ public_path('images/SETAS.png') }}">
    <h2>Informe de Rendimiento — {{ $periodo['nombre_mes'] }} {{ $periodo['anio'] }}</h2>
    <span>Generado: {{ $generadoEn->format('d/m/Y H:i') }}</span>
</div>

<div class="seccion">
    <div class="seccion-titulo">Resumen ejecutivo (IA)</div>
    <div class="seccion-cuerpo narrativa">{{ $informeTexto ?? 'No disponible en este momento.' }}</div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Ventas</div>
    <div class="seccion-cuerpo">
        <table class="kpi">
            <tr><td class="etiqueta">Vendido en el mes</td><td class="valor">${{ number_format($datos['ventas']['valor_vendido_mes'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">Vendido mes anterior</td><td class="valor">${{ number_format($datos['ventas']['valor_vendido_mes_anterior'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">Variación</td><td class="valor">{{ $datos['ventas']['variacion_porcentual'] !== null ? $datos['ventas']['variacion_porcentual'] . '%' : 'N/A' }}</td></tr>
            <tr><td class="etiqueta">Órdenes del mes</td><td class="valor">{{ $datos['ventas']['cantidad_ordenes'] }}</td></tr>
            <tr><td class="etiqueta">Meta mensual</td><td class="valor">{{ $datos['ventas']['meta_mensual'] !== null ? '$' . number_format($datos['ventas']['meta_mensual'], 0, ',', '.') : 'Sin meta' }}</td></tr>
            <tr><td class="etiqueta">Cumplimiento meta</td><td class="valor">{{ $datos['ventas']['cumplimiento_meta_porcentual'] !== null ? $datos['ventas']['cumplimiento_meta_porcentual'] . '%' : 'N/A' }}</td></tr>
        </table>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Ventas por semana del mes</div>
    <div class="seccion-cuerpo">
        <table class="detalle">
            <thead><tr><th>Semana</th><th>Total vendido</th><th>Órdenes</th></tr></thead>
            <tbody>
            @forelse($datos['ventas_detalle']['por_semana'] as $semana)
                <tr>
                    <td>{{ $semana['semana'] }}</td>
                    <td>${{ number_format($semana['total'], 0, ',', '.') }}</td>
                    <td>{{ $semana['cantidad_ordenes'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin órdenes en el período.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Cartera</div>
    <div class="seccion-cuerpo">
        <table class="kpi">
            <tr><td class="etiqueta">Cartera total actual</td><td class="valor">${{ number_format($datos['cartera']['total_cartera_actual'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">Vencido actual</td><td class="valor">${{ number_format($datos['cartera']['total_vencido_actual'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">% vencido actual</td><td class="valor">{{ $datos['cartera']['porcentaje_vencido_actual'] }}%</td></tr>
            <tr><td class="etiqueta">Vencido generado en el mes</td><td class="valor">${{ number_format($datos['cartera']['vencido_generado_en_mes'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">Recaudo del mes</td><td class="valor">${{ number_format($datos['cartera']['recaudo_mes'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">Órdenes bloqueadas por mora</td><td class="valor">${{ number_format($datos['cartera']['valor_ordenes_bloqueadas_por_mora'], 0, ',', '.') }}</td></tr>
        </table>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Ranking de cartera — Top deudores</div>
    <div class="seccion-cuerpo">
        <table class="detalle">
            <thead><tr><th>Cliente</th><th>Deuda total</th><th>Días vencido (más antiguo)</th><th>Facturas pendientes</th></tr></thead>
            <tbody>
            @forelse($datos['ranking_cartera']['top_deudores'] as $c)
                <tr>
                    <td class="izq">{{ $c['cliente'] }}</td>
                    <td>${{ number_format($c['total_deuda'], 0, ',', '.') }}</td>
                    <td>{{ $c['dias_vencido_mas_antiguo'] }}</td>
                    <td>{{ $c['facturas_pendientes'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin cartera pendiente.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Ranking de cartera — Pagos recientes del mes</div>
    <div class="seccion-cuerpo">
        <table class="detalle">
            <thead><tr><th>Cliente</th><th>Total pagado</th><th>Último pago</th></tr></thead>
            <tbody>
            @forelse($datos['ranking_cartera']['top_pagos_recientes'] as $c)
                <tr>
                    <td class="izq">{{ $c['cliente'] }}</td>
                    <td>${{ number_format($c['total_pagado_mes'], 0, ',', '.') }}</td>
                    <td>{{ $c['ultimo_pago'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin pagos registrados en el mes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Flujo de compras</div>
    <div class="seccion-cuerpo">
        <table class="kpi">
            @foreach($datos['compras']['requerimientos_por_estado'] as $estado => $total)
                <tr><td class="etiqueta">Requerimientos {{ $estado }}</td><td class="valor">{{ $total }}</td></tr>
            @endforeach
            <tr><td class="etiqueta">Órdenes a proveedor generadas</td><td class="valor">{{ $datos['compras']['ordenes_proveedor_generadas_mes'] }}</td></tr>
            <tr><td class="etiqueta">Órdenes con faltantes</td><td class="valor">{{ $datos['compras']['faltantes_pendientes']['total_ordenes_faltantes'] ?? 0 }}</td></tr>
            <tr><td class="etiqueta">Referencias sin cobertura</td><td class="valor">{{ $datos['compras']['faltantes_pendientes']['sin_cobertura'] ?? 0 }}</td></tr>
        </table>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Contabilidad</div>
    <div class="seccion-cuerpo">
        <table class="kpi">
            <tr><td class="etiqueta">Facturas recibidas en el mes</td><td class="valor">{{ $datos['contabilidad']['facturas_recibidas_mes'] }}</td></tr>
            <tr><td class="etiqueta">Total facturado en el mes</td><td class="valor">${{ number_format($datos['contabilidad']['total_facturado_mes'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">Total pagado en el mes</td><td class="valor">${{ number_format($datos['contabilidad']['total_pagado_mes'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">Gastos del mes</td><td class="valor">${{ number_format($datos['contabilidad']['total_gastos_mes'], 0, ',', '.') }}</td></tr>
            <tr><td class="etiqueta">Saldo pendiente a proveedores</td><td class="valor">${{ number_format($datos['contabilidad']['saldo_pendiente_proveedores_actual'], 0, ',', '.') }}</td></tr>
        </table>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Inventario</div>
    <div class="seccion-cuerpo">
        <table class="kpi">
            <tr><td class="etiqueta">Valorización de stock actual</td><td class="valor">${{ number_format($datos['inventario']['valorizacion_stock_actual'], 0, ',', '.') }}</td></tr>
            @foreach($datos['inventario']['movimientos_por_tipo_mes'] as $tipo => $total)
                <tr><td class="etiqueta">Movimientos {{ $tipo }}</td><td class="valor">{{ $total }}</td></tr>
            @endforeach
            <tr><td class="etiqueta">Traslados pendientes</td><td class="valor">{{ $datos['inventario']['traslados_pendientes_actual'] }}</td></tr>
        </table>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Auditoría de inventario — OT sin descuento registrado</div>
    <div class="seccion-cuerpo">
        <table class="kpi">
            <tr><td class="etiqueta">OT revisadas en el mes</td><td class="valor">{{ $datos['auditoria_inventario_ot']['total_ot_revisadas'] }}</td></tr>
            <tr><td class="etiqueta">OT sin descuento registrado</td><td class="valor">{{ $datos['auditoria_inventario_ot']['total_ot_sin_descuento'] }}</td></tr>
        </table>
        <table class="detalle" style="margin-top: 6px;">
            <thead><tr><th>OT</th><th>Orden de compra</th><th>Cliente</th><th>Estado</th><th>Valor</th></tr></thead>
            <tbody>
            @forelse($datos['auditoria_inventario_ot']['detalle'] as $ot)
                <tr>
                    <td>#{{ $ot['orden_trabajo_id'] }}</td>
                    <td>#{{ $ot['orden_compra_id'] }}</td>
                    <td class="izq">{{ $ot['cliente'] ?? 'N/A' }}</td>
                    <td>{{ $ot['estado'] }}</td>
                    <td>${{ number_format($ot['valor_total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No se encontraron inconsistencias en el período.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(count($historialChat))
<div class="seccion">
    <div class="seccion-titulo">Anexo — Preguntas y respuestas</div>
    <div class="seccion-cuerpo">
        @foreach($historialChat as $turno)
            <div class="chat-turno">
                <strong>{{ ($turno['rol'] ?? '') === 'asistente' ? 'Asistente' : 'Administrador' }}:</strong>
                {{ $turno['contenido'] ?? '' }}
            </div>
        @endforeach
    </div>
</div>
@endif

</body>
</html>
