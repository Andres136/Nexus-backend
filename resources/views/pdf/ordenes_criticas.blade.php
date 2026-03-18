<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Órdenes Críticas</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { text-align: center; }
        h3 { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
        .oc-header { margin: 6px 0 4px; }
        .parcial {
            background-color: #ffeeba;
            border-left: 4px solid #ffc107;
            padding-left: 6px;
        }
    </style>
</head>
<body>
<h2>Órdenes Críticas del {{ $fecha }}</h2>

@foreach (['vencidas' => 'Órdenes Vencidas', 'faltantes' => 'Órdenes con Faltantes', 'hoy' => 'Órdenes Entregar Hoy'] as $tipo => $titulo)
    @php 
        $grupo = $$tipo;
        $grupoOrdenado = $grupo->sortBy(function($orden) {
            return [$orden->estado_id == 5 ? 1 : 0, \Carbon\Carbon::parse($orden->fecha_entrega)];
        });
    @endphp
    @if($grupoOrdenado->isNotEmpty())
        <h3>{{ $titulo }}</h3>
        @foreach ($grupoOrdenado as $orden)
            <div class="oc-header{{ $orden->estado_id == 5 ? ' parcial' : '' }}">
                <strong>OC #{{ $orden->id }}</strong> ·
                Cliente: {{ data_get($orden, 'cliente.nombre', 'N/A') }} ·
                Sede: {{ data_get($orden, 'sede.nombre', 'N/A') }} ·
                Entrega: {{ \Carbon\Carbon::parse($orden->fecha_entrega)->format('Y-m-d') }}
                <br>
                Orden de Trabajo: {{ data_get($orden, 'ordenTrabajo.id', 'No asignada') }}
                <br>
                Observaciones: {{ $orden->observaciones ?? '—' }}
                @if($orden->estado_id == 5)
                    <span style="color:#b8860b; font-weight:bold;">(Entrega Parcial)</span>
                @endif
            </div>

            @if($orden->detalles->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cant. Requerida</th>
                            <th>Especificación</th>
                            <th>Cant.</th>
                            <th>Enviada</th>
                            <th>Faltantes</th>
                            <th>Inventario</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orden->detalles as $detalle)
                            <tr>
                                <td>
                                    <strong>{{ $detalle->product->code ?? '' }}</strong><br>
                                    {{ $detalle->product->name ?? '' }}<br>
                                    <small>{{ $detalle->product->description ?? '' }}</small>
                                </td>
                                <td>
                                    {{ rtrim(rtrim(number_format($detalle->cantidad_requerida_kg ?? 0, 2, '.', ''), '0'), '.') }}
                                </td>
                                <td>
                                    <strong>{{ rtrim(rtrim(number_format($detalle->ancho_cm ?? 0, 2, '.', ''), '0'), '.') }} x {{ rtrim(rtrim(number_format($detalle->largo_cm ?? 0, 2, '.', ''), '0'), '.') }}</strong>
                                    Cal {{ $detalle->cliente_clb ?? '—' }} | {{ $detalle->descripcion ?? '—' }}
                                </td>
                                <td>
                                    {{ rtrim(rtrim(number_format($detalle->cantidad ?? 0, 2, '.', ''), '0'), '.') }}
                                </td>
                                <td>
                                    {{ rtrim(rtrim(number_format($detalle->cantidad_enviada ?? 0, 2, '.', ''), '0'), '.') }}
                                </td>
                                <td>
                                    {{ rtrim(rtrim(number_format($detalle->faltantes ?? 0, 2, '.', ''), '0'), '.') }}
                                </td>
                                <td>
                                    {{ \App\Models\Crm\Inventario::getStockOrSimilarFromCollection(
                                        $detalle->product,
                                        $inventarios,
                                        $orden->empresa_id ?? null,
                                        $orden->sede_id ?? null
                                    ) }}
                                </td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif
@endforeach
</body>
</html>