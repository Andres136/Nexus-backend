<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Notificación</title>
    <style>
        /* Reset y base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        /* Header con logo y branding */
        .email-header {
            background: linear-gradient(135deg, #fff0db 0%, #208040 100%);
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .email-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.05"><path d="M0 0v99c134 0 153-99 296-99s162 99 296 99 162-99 296-99 162 99 296 99V0H0z"/></svg>') no-repeat center;
            background-size: cover;
        }

        .logo-container {
            position: relative;
            z-index: 2;
        }

        .company-logo {
            max-height: 60px;
            width: auto;
            margin-bottom: 15px;
            filter: brightness(0) invert(1);
        }

        .company-name {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .tagline {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-top: 5px;
        }

        /* Contenido principal */
        .email-body {
            padding: 40px 30px;
        }

        .notification-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .notification-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: inline-block;
            padding: 20px;
            background: linear-gradient(135deg, #208040 0%, #166b32 100%);
            border-radius: 50%;
            color: white;
            box-shadow: 0 4px 15px rgba(32, 128, 64, 0.3);
        }

        .notification-icon.urgent {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            animation: pulse 2s infinite;
        }

        .notification-icon.task {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .notification-icon.pqr {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
        }

        .notification-icon.work-order {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }

        .notification-header h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .notification-subtitle {
            color: #7f8c8d;
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Tarjeta de detalles de la orden */
        .order-details-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            border: 1px solid #e9ecef;
            position: relative;
        }

        .order-details-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px 10px 0 0;
        }

        .order-id {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .details-grid {
            display: grid;
            gap: 15px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-item.total {
            background: white;
            margin: 15px -10px -10px -10px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .label {
            font-weight: 600;
            color: #555;
        }

        .value {
            font-weight: 500;
            color: #2c3e50;
        }

        .detail-item.total .value {
            font-size: 18px;
            color: #27ae60;
            font-weight: 700;
        }

        /* Botón de acción */
        .action-section {
            text-align: center;
            margin: 35px 0;
        }

        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #208040 0%, #166b32 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(32, 128, 64, 0.3);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(32, 128, 64, 0.4);
        }

        .btn-urgent {
            display: inline-block;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            transition: all 0.3s ease;
            animation: glow 2s ease-in-out infinite alternate;
        }

        .btn-secondary {
            display: inline-block;
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 500;
            margin: 0 5px;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(149, 165, 166, 0.3);
        }

        .btn-task {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-pqr {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
        }

        /* Próximos pasos */
        .next-steps {
            background: #fff5f5;
            border-left: 4px solid #e74c3c;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }

        .next-steps h3 {
            color: #c0392b;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .next-steps ul {
            list-style: none;
            padding-left: 0;
        }

        .next-steps li {
            padding: 5px 0;
            position: relative;
            padding-left: 25px;
        }

        .next-steps li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #e74c3c;
            font-weight: bold;
        }

        /* Sección de ayuda */
        .help-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: center;
        }

        .help-section p {
            margin: 8px 0;
        }

        /* Footer */
        .email-footer {
            background: #2c3e50;
            padding: 25px 30px;
            text-align: center;
        }

        .footer-content {
            color: #bdc3c7;
            font-size: 14px;
            line-height: 1.6;
        }

        .footer-links {
            margin-top: 15px;
        }

        .footer-links a {
            color: #3498db;
            text-decoration: none;
            margin: 0 10px;
        }

        .social-links {
            margin-top: 15px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 5px;
            color: #95a5a6;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .email-body {
                padding: 25px 20px;
            }
            
            .notification-icon {
                font-size: 36px;
                padding: 15px;
            }
            
            .notification-header h1 {
                font-size: 24px;
            }
            
            .order-details-card {
                padding: 20px 15px;
            }
            
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }

        /* Estilos específicos para diferentes tipos de notificaciones */
        .notification-card.urgent {
            border-top: 4px solid #e74c3c;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%);
        }

        .notification-card.task {
            border-top: 4px solid #3498db;
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
        }

        .notification-card.pqr {
            border-top: 4px solid #9b59b6;
            background: linear-gradient(135deg, #f8f0ff 0%, #f0e6ff 100%);
        }

        .notification-card.work-order {
            border-top: 4px solid #f39c12;
            background: linear-gradient(135deg, #fff8e6 0%, #fff0cc 100%);
        }

        .notification-card.success {
            border-top: 4px solid #208040;
            background: linear-gradient(135deg, #f0fff4 0%, #e6ffe6 100%);
        }

        .priority-indicator {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .priority-indicator.alta {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
        }

        .priority-indicator.media {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(243, 156, 18, 0.3);
        }

        .priority-indicator.baja {
            background: linear-gradient(135deg, #208040 0%, #166b32 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(32, 128, 64, 0.3);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            gap: 5px;
        }

        .status-badge.pendiente {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-badge.en-proceso {
            background: #cce5ff;
            color: #0056b3;
            border: 1px solid #99d6ff;
        }

        .status-badge.completado {
            background: #d4edda;
            color: #155724;
            border: 1px solid #b3e5c0;
        }

        .status-badge.urgente {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f1b8bc;
            animation: pulse-soft 2s infinite;
        }

        @keyframes pulse-soft {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.02); opacity: 0.9; }
        }

        @keyframes glow {
            from { box-shadow: 0 0 20px rgba(231, 76, 60, 0.3); }
            to { box-shadow: 0 0 30px rgba(231, 76, 60, 0.6), 0 0 40px rgba(231, 76, 60, 0.4); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 3px solid #208040;
        }

        .metric-number {
            font-size: 24px;
            font-weight: bold;
            color: #208040;
            display: block;
        }

        .metric-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            padding: 10px 0;
            border-left: 2px solid #e9ecef;
            padding-left: 15px;
            margin-left: 10px;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 15px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #208040;
        }

        .timeline-item:last-child {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="logo-container">
                <img src="{{ asset('images/SETAS.png') }}" alt="{{ config('app.name') }}" class="company-logo">
                <h1 class="company-name">{{ config('app.name') }}</h1>
                <p class="tagline">Sistema Integral de Gestión</p>
            </div>
        </div>

        <!-- Body -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-content">
                <p><strong>{{ config('app.name') }}</strong> - sistema de gestion integral</p>
                <p>Este correo fue generado automáticamente. Por favor, no responder a este mensaje.</p>
                
                <div class="footer-links">
                    <a href="{{ config('app.frontend_url') }}">Portal Web</a>
                    |
                    <a href="{{ config('app.frontend_url') }}/soporte">Soporte Técnico</a>
                </div>
                
                <p style="margin-top: 15px; font-size: 12px; color: #95a5a6;">
                    © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
