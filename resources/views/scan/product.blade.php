<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $producto->name }} - SETASPLAST Scanner</title>
    <style>
        /* ✅ Variables CSS */
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --success-light: #dcfce7;
            --success-dark: #065f46;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --danger-dark: #991b1b;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        /* ✅ Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 1rem;
            line-height: 1.5;
            color: var(--gray-800);
        }

        /* ✅ Container principal */
        .container {
            max-width: 420px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out;
        }

        /* ✅ Header del escáner */
        .scanner-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
            position: relative;
            overflow: hidden;
        }

        .scanner-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: rotate 3s linear infinite;
        }

        .scanner-icon {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            animation: scanPulse 2s ease-in-out infinite;
        }

        .product-title {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .product-code {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            display: inline-block;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* ✅ Card de stock total */
        .total-stock {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--gray-200);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .total-stock::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--success) 0%, var(--primary) 100%);
        }

        .stock-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .stock-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .stock-number {
            color: var(--gray-900);
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }

        /* ✅ Sección de bodegas */
        .warehouses-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--gray-200);
            position: relative;
        }

        .warehouses-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--warning) 0%, var(--primary) 100%);
        }

        .section-title {
            color: var(--gray-800);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ✅ Warehouse item mejorado */
        .warehouse-item {
            background: linear-gradient(135deg, var(--gray-50) 0%, #f8fafc 100%);
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .warehouse-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            border-color: var(--primary);
        }

        .warehouse-item:last-child {
            margin-bottom: 0;
        }

        .warehouse-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .warehouse-name {
            color: var(--gray-800);
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .warehouse-stock {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .stock-value {
            color: var(--gray-900);
            font-weight: 800;
            font-size: 1.25rem;
        }

        .stock-unit {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* ✅ Status badges mejorados */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .status-ok {
            background: var(--success-light);
            color: var(--success-dark);
            border: 2px solid var(--success);
        }

        .status-low {
            background: var(--danger-light);
            color: var(--danger-dark);
            border: 2px solid var(--danger);
            animation: warningPulse 2s ease-in-out infinite;
        }

        /* ✅ Footer del escáner */
        .scanner-footer {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .footer-text {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .timestamp {
            color: var(--gray-500);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        /* ✅ Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes scanPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes warningPulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.02);
            }
        }

        /* ✅ Responsive */
        @media (max-width: 480px) {
            body {
                padding: 0.75rem;
            }

            .scanner-header {
                padding: 1.5rem 1rem;
                border-radius: 16px;
            }

            .product-title {
                font-size: 1.25rem;
            }

            .scanner-icon {
                font-size: 3rem;
            }

            .stock-number {
                font-size: 2rem;
            }

            .warehouse-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .warehouse-stock {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 360px) {
            .container {
                padding: 0;
            }

            .warehouse-item {
                padding: 0.75rem;
            }

            .product-code {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
            }
        }

        /* ✅ Dark mode soporte */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            }

            .total-stock,
            .warehouses-section {
                background: #374151;
                border-color: #4b5563;
                color: #f9fafb;
            }

            .warehouse-item {
                background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
                border-color: #6b7280;
            }

            .warehouse-name,
            .stock-value,
            .section-title {
                color: #f9fafb;
            }

            .stock-label {
                color: #d1d5db;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        
        <!-- ✅ Header del escáner -->
        <div class="scanner-header">
            <div class="scanner-icon">📱</div>
            <h1 class="product-title">{{ $producto->name }}</h1>
            <div class="product-code">🏷️ {{ $producto->code }}</div>
        </div>

        <!-- ✅ Stock total mejorado -->
        @php
            $stockTotal = $producto->inventarios->sum('stock');
        @endphp
        
        <div class="total-stock">
            <div class="stock-icon">📦</div>
            <div class="stock-label">Stock Total Disponible</div>
            <div class="stock-number">{{ number_format($stockTotal) }}</div>
        </div>

        <!-- ✅ Sección de bodegas -->
        <div class="warehouses-section">
            <h3 class="section-title">
                📍 Disponibilidad por bodega
            </h3>

            @foreach ($inventariosPorBodega as $bodega => $items)
                @php
                    $totalBodega = $items->sum('stock');
                    $minStock = $items->min('min_stock') ?? 10;
                    $isLowStock = $totalBodega < $minStock;
                @endphp

                <div class="warehouse-item">
                    <div class="warehouse-header">
                        <div class="warehouse-name">
                            🏪 {{ ucfirst($bodega) }}
                        </div>
                        
                        <div class="warehouse-stock">
                            <div>
                                <span class="stock-value">{{ number_format($totalBodega) }}</span>
                                <span class="stock-unit">unidades</span>
                            </div>
                            
                            <span class="status-badge {{ $isLowStock ? 'status-low' : 'status-ok' }}">
                                {{ $isLowStock ? '⚠️ Bajo' : '✅ OK' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ✅ Footer del escáner -->
        <div class="scanner-footer">
            <div class="footer-text">
                🏢 <strong>SETASPLAST Scanner System</strong>
            </div>
            <div class="timestamp">
                Escaneado el {{ date('d/m/Y H:i:s') }}
            </div>
        </div>
        
    </div>
</body>
</html>