<!-- filepath: /home/tic/Escritorio/Proyectos Setas/SIG_SETAS/setasplast-api/resources/views/errors/estado-traslado.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estado de Traslado - SIG-SETAS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
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
            max-width: 550px;
            width: 100%;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #a855f7);
        }

        .status-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 50%;
            margin: 0 auto 24px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .status-icon::before {
            content: '';
            width: 24px;
            height: 24px;
            border: 3px solid #ffffff;
            border-radius: 50%;
            position: relative;
        }

        .status-icon::after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: #ffffff;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .title {
            color: #1f2937;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .subtitle {
            color: #6366f1;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .message {
            color: #4c1d95;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            padding: 20px 24px;
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            border: 1px solid #c4b5fd;
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
            border-left: 4px solid #6366f1;
        }

        .status-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
        }

        .status-info h3 {
            color: #374151;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: center;
        }

        .status-flow {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .status-step {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-step.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-step.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-step.current {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-step.completed {
            background: #dcfce7;
            color: #166534;
        }

        .arrow {
            color: #9ca3af;
            font-size: 16px;
            font-weight: bold;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }

        .info-item {
            text-align: center;
            padding: 12px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }

        .info-label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin-bottom: 4px;
        }

        .info-value {
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
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

        .auto-close {
            color: #8b5cf6;
            font-size: 14px;
            font-weight: 500;
            margin-top: 16px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border-radius: 20px;
            display: inline-block;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
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

            .info-grid {
                grid-template-columns: 1fr;
            }

            .status-flow {
                flex-direction: column;
                gap: 8px;
            }

            .arrow {
                transform: rotate(90deg);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Icono de estado -->
        <div class="status-icon"></div>
        
        <!-- Título principal -->
        <h1 class="title">Estado del Traslado</h1>
        <div class="subtitle">Verificación de Estado</div>
        
        <!-- Mensaje principal -->
        <div class="message">
            {{ $mensaje }}
        </div>
        
        <!-- Explicación -->
        <div class="explanation">
            Este traslado ya fue procesado anteriormente o su estado actual no permite la acción solicitada desde este enlace.
        </div>
        
        <!-- Información del estado -->
        <div class="status-info">
            <h3>Flujo del Traslado</h3>
            
            <div class="status-flow">
                <div class="status-step pending">Pendiente Bodega</div>
                <div class="arrow">→</div>
                <div class="status-step approved">Pendiente Inventario</div>
                <div class="arrow">→</div>
                <div class="status-step current">Aprobado</div>
                <div class="arrow">→</div>
                <div class="status-step completed">Despachado</div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Estado Actual</div>
                    <div class="info-value">Ya Procesado</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Acción</div>
                    <div class="info-value">No Disponible</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Motivo</div>
                    <div class="info-value">Estado Cambiado</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Enlace</div>
                    <div class="info-value">Expirado</div>
                </div>
            </div>
        </div>
        
        <!-- Acciones -->
        <div class="actions">
            <button class="btn btn-primary" onclick="window.close()">
                Cerrar Ventana
            </button>
            <a href="{{ url('/') }}" class="btn btn-secondary">
                Ir al Sistema Principal
            </a>
        </div>
        
        <!-- Auto-cerrar -->
        <div class="auto-close" id="autoClose">
            Esta ventana se cerrará automáticamente en <span id="countdown">8</span> segundos
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="logo">SIG-SETAS</div>
            <div>Sistema de Gestión de Inventarios y Traslados</div>
            <div class="timestamp">
                {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
        
    </div>

    <script>
        // Auto-cerrar ventana después de 8 segundos
        let countdown = 8;
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
        
        // Cancelar auto-cierre si hay interacción
        document.addEventListener('click', () => {
            clearInterval(timer);
            autoCloseElement.style.display = 'none';
        });
        
        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                clearInterval(timer);
                window.close();
            }
        });
        
        // Prevenir cierre accidental del navegador
        window.addEventListener('beforeunload', () => {
            clearInterval(timer);
        });
    </script>
</body>
</html>