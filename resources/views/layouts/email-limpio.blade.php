<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Notificación</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background: #f8f9fa;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        /* Header simplificado */
        .email-header {
            background: linear-gradient(135deg, #fff0db 0%, #208040 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .company-logo {
            max-height: 50px;
            width: auto;
            margin-bottom: 15px;
            filter: brightness(0) invert(1);
        }

        .company-name {
            color: #ffffff;
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .tagline {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            margin-top: 5px;
        }

        /* Contenido principal */
        .email-body {
            padding: 40px 30px;
        }

        .notification-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .notification-icon {
            font-size: 40px;
            margin-bottom: 12px;
            display: inline-block;
            padding: 16px;
            background: linear-gradient(135deg, #208040 0%, #27ae60 100%);
            border-radius: 50%;
            color: white;
            box-shadow: 0 3px 12px rgba(32, 128, 64, 0.3);
        }

        .notification-icon.urgent {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            box-shadow: 0 3px 12px rgba(231, 76, 60, 0.3);
        }

        .notification-icon.task {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            box-shadow: 0 3px 12px rgba(52, 152, 219, 0.3);
        }

        .notification-icon.pqr {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            box-shadow: 0 3px 12px rgba(155, 89, 182, 0.3);
        }

        .notification-icon.work-order {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            box-shadow: 0 3px 12px rgba(243, 156, 18, 0.3);
        }

        .notification-header h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .notification-subtitle {
            color: #7f8c8d;
            font-size: 15px;
        }

        /* Botones */
        .actions {
            text-align: center;
            margin: 30px 0;
        }

        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #208040 0%, #27ae60 100%);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 3px 10px rgba(32, 128, 64, 0.3);
            transition: all 0.3s ease;
            margin: 0 5px;
        }

        .btn-secondary {
            display: inline-block;
            background: #ecf0f1;
            color: #2c3e50;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            margin: 0 5px;
        }

        .btn-urgent {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.3);
        }

        .btn-pqr {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            box-shadow: 0 3px 10px rgba(155, 89, 182, 0.3);
        }

        /* Footer */
        .email-footer {
            background: #34495e;
            padding: 20px 30px;
            text-align: center;
        }

        .footer-content {
            color: #bdc3c7;
            font-size: 13px;
            line-height: 1.5;
        }

        .footer-content strong {
            color: #ecf0f1;
        }

        /* Responsive */
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .email-container {
                border-radius: 4px;
            }
            
            .email-body {
                padding: 25px 20px;
            }
            
            .notification-icon {
                font-size: 32px;
                padding: 12px;
            }
            
            .notification-header h1 {
                font-size: 20px;
            }
            
            .btn-primary, .btn-secondary, .btn-urgent, .btn-pqr {
                display: block;
                margin: 8px 0;
            }
        }

        /* Utilidades de color */
        .text-green { color: #208040; }
        .text-red { color: #e74c3c; }
        .text-blue { color: #3498db; }
        .text-purple { color: #9b59b6; }
        .text-orange { color: #f39c12; }

        .bg-green-light { background: rgba(32, 128, 64, 0.1); }
        .bg-red-light { background: rgba(231, 76, 60, 0.1); }
        .bg-blue-light { background: rgba(52, 152, 219, 0.1); }
        .bg-purple-light { background: rgba(155, 89, 182, 0.1); }
        .bg-orange-light { background: rgba(243, 156, 18, 0.1); }

        .border-green { border-left: 3px solid #208040; }
        .border-red { border-left: 3px solid #e74c3c; }
        .border-blue { border-left: 3px solid #3498db; }
        .border-purple { border-left: 3px solid #9b59b6; }
        .border-orange { border-left: 3px solid #f39c12; }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <img src="{{ asset('images/SETAS.png') }}" alt="{{ config('app.name') }}" class="company-logo">
            <h1 class="company-name">{{ config('app.name') }}</h1>
            <p class="tagline">Sistema Integral de Gestión</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-content">
                <p><strong>{{ config('app.name') }}</strong> - Sistema de Gestión</p>
                <p style="margin-top: 8px; font-size: 12px;">
                    © {{ date('Y') }} Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
