<!-- filepath: /home/tic/Escritorio/Proyectos Setas/SIG_SETAS/setasplast-api/resources/views/emails/traslados/respuesta.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Respuesta Procesada - SIG-SETAS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #3b82f6, #8b5cf6);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            margin: 0 auto 24px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }

        .success-icon::after {
            content: '';
            width: 24px;
            height: 12px;
            border-left: 4px solid #ffffff;
            border-bottom: 4px solid #ffffff;
            transform: rotate(-45deg);
            margin-top: -6px;
        }

        .title {
            color: #1f2937;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .message {
            color: #059669;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 24px;
            padding: 16px 20px;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            line-height: 1.4;
        }

        .description {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 32px;
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

        /* Animación sutil */
        .container {
            animation: slideUp 0.4s ease-out;
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

        /* Auto-cerrar después de 5 segundos */
        .auto-close {
            color: #8b5cf6;
            font-size: 14px;
            font-weight: 500;
            margin-top: 16px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
            border-radius: 20px;
            display: inline-block;
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
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Icono de éxito -->
        <div class="success-icon"></div>
        
        <!-- Título principal -->
        <h1 class="title">Proceso Completado</h1>
        
        <!-- Mensaje personalizado -->
        <div class="message">
            {{ $mensaje }}
        </div>
        
        <!-- Descripción -->
        <p class="description">
            Su respuesta ha sido registrada correctamente en el sistema.<br>
            El traslado continuará con el flujo correspondiente.
        </p>
        
        <!-- Acciones -->
        <div class="actions">
            <button class="btn btn-primary" onclick="window.close()">
                Cerrar Ventana
            </button>
            <a href="{{ url('/') }}" class="btn btn-secondary">
                Volver al Sistema
            </a>
        </div>
        
        <!-- Auto-cerrar -->
        <div class="auto-close" id="autoClose">
            Esta ventana se cerrará automáticamente en <span id="countdown">5</span> segundos
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="logo">SIG-SETAS</div>
            <div>Sistema de Gestión de Inventarios</div>
        </div>
        
    </div>

    <script>
        // Auto-cerrar ventana después de 5 segundos
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        const autoCloseElement = document.getElementById('autoClose');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                autoCloseElement.textContent = 'Cerrando...';
                setTimeout(() => {
                    window.close();
                }, 500);
            }
        }, 1000);
        
        // Permitir cerrar manualmente
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.close();
            }
        });
        
        // Mostrar mensaje de confirmación si no se puede cerrar automáticamente
        window.addEventListener('beforeunload', (e) => {
            clearInterval(timer);
        });
    </script>
</body>
</html>