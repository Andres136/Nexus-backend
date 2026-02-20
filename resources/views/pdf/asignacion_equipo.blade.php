<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de Devolución de Equipo</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 28px;
            font-size: 12px;
            color: #1f2937;
            background: #ffffff;
        }

        .container {
            border: 1px solid #d1d5db;
            padding: 22px 26px;
            border-radius: 8px;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }

        .header-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }

        .logo {
            max-height: 60px;
        }

        .empresa-nombre {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .empresa-info {
            font-size: 10.5px;
            color: #4b5563;
            line-height: 1.4;
        }

        .doc-title {
            margin-top: 16px;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-sub {
            text-align: center;
            font-size: 10.5px;
            color: #6b7280;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 18px 0 8px;
            border-left: 4px solid #111827;
            padding-left: 8px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e5e7eb;
        }

        .info-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-table tr:nth-child(even) td {
            background: #f9fafb;
        }

        .label {
            width: 200px;
            font-weight: 700;
            color: #374151;
        }

        .paragraph {
            margin-top: 14px;
            font-size: 11px;
            text-align: justify;
            line-height: 1.6;
        }

        .signatures {
            margin-top: 60px;
            width: 100%;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding: 0 15px;
        }

        .line {
            border-top: 1px solid #111827;
            margin-bottom: 6px;
        }

        .sign-label {
            font-size: 10.5px;
            font-weight: 600;
        }

        .footer {
            margin-top: 25px;
            font-size: 9.5px;
            color: #6b7280;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <div class="header-left">
            @if($logoPath)
                <img src="{{ $logoPath }}" class="logo">
            @else
                <div class="empresa-nombre">{{ $empresa->nombre }}</div>
            @endif

            <div class="empresa-info">
                NIT: {{ $empresa->nit }}<br>
                Dirección: {{ $empresa->direccion }}<br>
                Teléfono: {{ $empresa->telefono }}<br>
                Correo: {{ $empresa->email }}
            </div>
        </div>

        <div class="header-right">
            Fecha de emisión:<br>
            <strong>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</strong>
        </div>
    </div>

    <div class="doc-title">Acta de Asignación de Equipo</div>
    <div class="doc-sub">Documento oficial de control de activos tecnológicos</div>

    <div class="section-title">Información General</div>
    <table class="info-table">
        <tr>
            <td class="label">Empresa</td>
            <td>{{ $empresa->nombre }}</td>
        </tr>
        <tr>
            <td class="label">Usuario que Entrega</td>
            <td>{{ $usuario->name }}</td>
        </tr>
        <tr>
            <td class="label">Usuario que Recibe</td>
            <td>{{ $usuarioRecibe->name }}</td>
        </tr>
        <tr>
            <td class="label">Producto</td>
            <td>{{ $producto->name }}</td>
        </tr>
        <tr>
            <td class="label">Código Interno</td>
            <td>{{ $producto->code }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Asignación</td>
            <td>{{ $asignacion->fecha_asignacion }}</td>
        </tr>
    </table>

    <div class="section-title">Observaciones</div>
    <div class="paragraph">
        {{ $asignacion->observaciones ?? 'No se registran observaciones adicionales.' }}
    </div>

    <div class="section-title">Declaración</div>
    <div class="paragraph">
        Yo, {{ $usuarioRecibe->name }}, declaro haber recibido el equipo descrito anteriormente en buen estado, y me comprometo a cuidarlo y devolverlo en las mismas condiciones al finalizar su uso o cuando la empresa lo requiera.
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                <div class="sign-label">Firma Usuario que Entrega</div>
            </td>
            <td>
                <div class="line"></div>
                <div class="sign-label">Firma Usuario que Recibe</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Gestión Interno | {{ $empresa->nombre }}
    </div>

</div>

</body>
</html>