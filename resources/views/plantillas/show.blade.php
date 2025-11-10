<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $plantilla->nombre ?? 'Plantilla Corporativa' }} - SetasPlast BIC</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f6f8;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            background: #fff;
            margin: 40px auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* Encabezado */
        .header {
            background: linear-gradient(90deg, #004225, #198754);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 0.5px;
        }

        /* Sección de logos */
        .logos {
            text-align: center;
            background: #f0f0f0;
            padding: 15px;
        }
        .logos img {
            max-height: 60px;
            margin: 0 10px;
        }

        /* Contenido principal */
        .content {
            padding: 40px 30px;
            line-height: 1.6;
        }

        /* Video */
        .video {
            margin: 30px 0;
            text-align: center;
        }
        .video iframe {
            width: 100%;
            height: 320px;
            border-radius: 8px;
        }

        /* Certificaciones */
        .certificaciones {
            background: #f7f7f7;
            padding: 20px;
            text-align: center;
            border-top: 2px solid #e0e0e0;
        }
        .certificaciones img {
            max-height: 60px;
            margin: 10px;
        }

        /* Redes sociales */
        .social {
            text-align: center;
            padding: 20px;
        }
        .social a {
            margin: 0 10px;
            text-decoration: none;
            color: #198754;
            font-weight: bold;
        }

        /* Descargas */
        .downloads {
            background: #f9f9f9;
            padding: 15px 20px;
            border-top: 1px solid #ddd;
        }
        .downloads a {
            display: block;
            color: #004225;
            text-decoration: none;
            margin-bottom: 8px;
        }

        /* Pie */
        .footer {
            background: #004225;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 13px;
        }

        .footer a {
            color: #00e68a;
            text-decoration: none;
        }

    </style>
</head>
<body>
    <div class="container">

        {{-- Encabezado --}}
        <div class="header">
            <h1>{{ strtoupper($plantilla->nombre) }}</h1>
        </div>

        {{-- Logos de empresas --}}
        @if(!empty($plantilla->logos_empresas))
            @php $logos = is_array($plantilla->logos_empresas) ? $plantilla->logos_empresas : json_decode($plantilla->logos_empresas, true); @endphp
            <div class="logos">
                @foreach($logos as $logo)
                    <img src="{{ asset('storage/'.$logo['url']) }}" alt="Logo">
                @endforeach
            </div>
        @endif

        {{-- Contenido principal --}}
        <div class="content">
            {!! $plantilla->contenido_html !!}
        </div>

        {{-- Video --}}
        @if(!empty($plantilla->video_url))
            <div class="video">
                <iframe src="{{ $plantilla->video_url }}" frameborder="0" allowfullscreen></iframe>
            </div>
        @endif

        {{-- Certificaciones --}}
        @if(!empty($plantilla->certificaciones))
            @php $certs = is_array($plantilla->certificaciones) ? $plantilla->certificaciones : json_decode($plantilla->certificaciones, true); @endphp
            <div class="certificaciones">
                <h3>Certificaciones y Reconocimientos</h3>
                @foreach($certs as $cert)
                    @if(!empty($cert['logo']))
                        <img src="{{ asset('storage/'.$cert['logo']) }}" alt="{{ $cert['nombre'] }}">
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Redes sociales --}}
        @if(!empty($plantilla->redes_sociales))
            @php $redes = is_array($plantilla->redes_sociales) ? $plantilla->redes_sociales : json_decode($plantilla->redes_sociales, true); @endphp
            <div class="social">
                <h3>Síguenos</h3>
                @foreach($redes as $r)
                    <a href="{{ $r['url'] ?? '#' }}" target="_blank">{{ ucfirst($r['nombre']) }}</a>
                @endforeach
            </div>
        @endif

        {{-- Descargas --}}
        @if(!empty($plantilla->descargas))
            @php $descargas = is_array($plantilla->descargas) ? $plantilla->descargas : json_decode($plantilla->descargas, true); @endphp
            <div class="downloads">
                <h4>Descargas disponibles</h4>
                @foreach($descargas as $d)
                    <a href="{{ $d['link'] ?? '#' }}" target="_blank">📎 {{ $d['nombre'] ?? 'Archivo' }}</a>
                @endforeach
            </div>
        @endif

        {{-- Pie de página --}}
        <div class="footer">
            © {{ date('Y') }} SetasPlast S.A.S BIC | Sistema Integrado de Gestión <br>
            Certificada ISO 9001 - ISO 14001 - ISO 45001 |
            <a href="https://setasplast.com.co" target="_blank">www.setasplast.com.co</a>
        </div>
    </div>
</body>
</html>
