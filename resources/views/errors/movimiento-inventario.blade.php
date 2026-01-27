<!-- filepath: /home/tic/Escritorio/Proyectos Setas/SIG_SETAS/setasplast-api/resources/views/errors/movimiento-inventario.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acción no Permitida - SIG-SETAS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            background: #ffffff;
            border-radius: 16px;
            padding: 48px 40px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 500px;
            width: 100%;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.4s ease-out;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #d97706, #92400e);
        }

        .warning-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 50%;
            margin: 0 auto 24px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
        }

        .warning-icon::before {
            content: '!';
            color: #ffffff;
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
        }

        .title {
            color: #1f2937;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .subtitle {
            color: #d97706;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .message {
            color: #d97706;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            padding: 20px 24px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #fbbf24;
            border-radius: 12px;
            line-height: 1.5;
        }

        .explanation {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 32px;
            padding: 16px 20px;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 32px;
            text-align: left;
        }

        .info-title {
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .info-list {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-list li {
            margin-bottom: 8px;
            padding-left: 16px;
            position: relative;
        }

        .info-list li::before {
            content: '•';
            color: #f59e0b;
            font-size: 16px;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: #f8fafc;
            color: #475569;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .footer {
            border-top: 1px solid #f1f5f9;
            padding-top: 24px;
            color: #9ca3af;
            font-size: 14px;
        }

        .logo {
            font-weight: 700;
            color: #374151;
            margin-bottom: 4px;
        }

        .timestamp {
            margin-top: 12px;
            color: #9ca3af;
            font-size: 12px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 32px 24px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .message {
                font-size: 16px;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Icono de advertencia -->
        <div class="warning-icon"></div>
        
        <!-- Título principal -->
        <h1 class="title">Acción no Permitida</h1>
        <div class="subtitle">Movimiento de Inventario</div>
        
        <!-- Mensaje principal -->
        <div class="message">
            {{ $mensaje }}
        </div>
        
        <!-- Explicación -->
        <div class="explanation">
            Este traslado ya fue procesado anteriormente o su estado ha cambiado. No es posible realizar movimientos de inventario en este momento.
        </div>
        
        <!-- Información adicional -->
        <div class="info-box">
            <div class="info-title">Posibles Causas:</div>
            <ul class="info-list">
                <li>El traslado ya fue despachado completamente</li>
                <li>Otro usuario procesó esta solicitud al mismo tiempo</li>
                <li>El estado del traslado cambió desde que se envió la notificación</li>
                <li>El enlace del email ya expiró o fue usado anteriormente</li>
            </ul>
        </div>
        
        <!-- Recomendaciones -->
        <div class="info-box">
            <div class="info-title">Recomendaciones:</div>
            <ul class="info-list">
                <li>Verifique el estado actual del traslado en el sistema</li>
                <li>Contacte al administrador si considera que es un error</li>
                <li>Revise si hay notificaciones más recientes</li>
            </ul>
        </div>
        
        <!-- Acciones -->
     
        
        <!-- Footer -->
        <div class="footer">
            <div class="logo">SIG-SETAS</div>
            <div>Sistema de Gestión de Inventarios</div>
            <div class="timestamp">
                {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
        
    </div>

    <script>
        // Auto-cerrar en 10 segundos si no hay interacción
        let autoCloseTimer = setTimeout(() => {
            window.close();
        }, 10000);
        
        // Cancelar auto-cierre si el usuario interactúa
        document.addEventListener('click', () => {
            clearTimeout(autoCloseTimer);
        });
        
        // Permitir cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>