@extends('layouts.email-limpio')

@section('content')
<!-- ✅ Header principal -->
<div style="background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
    <div style="font-size: 48px; margin-bottom: 10px;">📦</div>
    <h2 style="color: white; margin: 0; font-size: 24px; font-weight: 600;">
        Entrega Programada
    </h2>
    <p style="color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-size: 14px;">
        Tu pedido está listo para ser entregado
    </p>
</div>

<!-- ✅ Saludo personalizado -->
<div style="background-color: #F8FAFC; padding: 20px; border-radius: 8px; border-left: 4px solid #10B981; margin-bottom: 25px;">
    <p style="margin: 0; color: #374151; font-size: 16px;">
        Hola <strong style="color: #10B981;">{{ $cliente->nombre }}</strong>, 👋
    </p>
    <p style="margin: 8px 0 0 0; color: #6B7280; font-size: 14px;">
        Te confirmamos que tu entrega ha sido programada exitosamente
    </p>
</div>



<!-- ✅ Detalles de entrega mejorados -->
<div style="background-color: #FFFFFF; border: 2px solid #E5E7EB; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h3 style="color: #374151; margin: 0 0 20px 0; font-size: 18px; font-weight: 600; border-bottom: 2px solid #F3F4F6; padding-bottom: 10px;">
         Detalles de tu entrega
    </h3>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #F3F4F6;">
                <div style="display: flex; align-items: center;">
                    <div style="background-color: #EFF6FF; padding: 8px; border-radius: 8px; margin-right: 12px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                        📅
                    </div>
                    <div>
                        <div style="color: #6B7280; font-size: 12px; font-weight: 500; margin-bottom: 2px;">FECHA PROGRAMADA</div>
                        <div style="color: #374151; font-size: 16px; font-weight: 600;">{{ $delivery->fecha_entrega }}</div>
                    </div>
                </div>
            </td>
        </tr>
        
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #F3F4F6;">
                <div style="display: flex; align-items: center;">
                    <div style="background-color: #F0FDF4; padding: 8px; border-radius: 8px; margin-right: 12px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                        🚚
                    </div>
                    <div>
                        <div style="color: #6B7280; font-size: 12px; font-weight: 500; margin-bottom: 2px;">VEHÍCULO</div>
                        <div style="color: #374151; font-size: 16px; font-weight: 600;">
                            {{ $delivery->vehiculo->placa ?? '🔄 Por asignar' }}
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        
        <tr>
            <td style="padding: 12px 0;">
                <div style="display: flex; align-items: center;">
                    <div style="background-color: #FEF3E2; padding: 8px; border-radius: 8px; margin-right: 12px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                        👨‍💼
                    </div>
                    <div>
                        <div style="color: #6B7280; font-size: 12px; font-weight: 500; margin-bottom: 2px;">CONDUCTOR</div>
                        <div style="color: #374151; font-size: 16px; font-weight: 600;">
                            {{ $delivery->usuario->name ?? '🔄 Por asignar' }}
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ✅ Información de contacto destacada -->
<div style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #93C5FD;">
    <div style="display: flex; align-items: center; margin-bottom: 12px;">
        <div style="font-size: 24px; margin-right: 10px;">📞</div>
        <h4 style="color: #1E40AF; margin: 0; font-size: 16px; font-weight: 600;">
            ¿Tienes alguna observación?
        </h4>
    </div>
    <p style="color: #1E3A8A; margin: 0; font-size: 14px; line-height: 1.5;">
        Si tienes observaciones o requerimientos especiales para nuestro personal de entrega, 
        no dudes en contactarnos al número:
    </p>
    <div style="text-align: center; margin-top: 15px;">
        <a href="tel:3112890067" style="background-color: #1D4ED8; color: white; padding: 12px 24px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-block; box-shadow: 0 2px 10px rgba(29, 78, 216, 0.3);">
            📱 3112890067
        </a>
    </div>
</div>

<!-- ✅ Estado del proceso -->
<div style="background-color: #F0F9FF; padding: 20px; border-radius: 12px; border-left: 4px solid #0EA5E9; margin-bottom: 25px;">
    <div style="display: flex; align-items: center; margin-bottom: 10px;">
        <div style="font-size: 20px; margin-right: 8px;">🔔</div>
        <h4 style="color: #0C4A6E; margin: 0; font-size: 16px; font-weight: 600;">
            Próximos pasos
        </h4>
    </div>
    <p style="color: #0369A1; margin: 0; font-size: 14px; line-height: 1.5;">
        Te notificaremos automáticamente cuando el pedido salga a ruta hacia tu dirección. 
        Mantente atento a tu correo y teléfono.
    </p>
</div>

<!-- ✅ Mensaje de agradecimiento mejorado -->
<div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #F3E8FF 0%, #E9D5FF 100%); border-radius: 12px; margin-bottom: 20px;">
    <div style="font-size: 32px; margin-bottom: 10px;">💜</div>
    <p style="color: #6B21A8; margin: 0; font-size: 18px; font-weight: 600; margin-bottom: 8px;">
        Gracias por confiar en nosotros
    </p>
    <p style="color: #7C3AED; margin: 0; font-size: 14px;">
        En SETASPLAST trabajamos para brindarte el mejor servicio
    </p>
</div>


@endsection

@section('footer-text')
    <div style="text-align: center; padding: 15px; background-color: #F9FAFB; border-top: 1px solid #E5E7EB;">
        <div style="color: #6B7280; font-size: 14px; font-weight: 500; margin-bottom: 4px;">
            🚛 Sistema de Entregas
        </div>
        <div style="color: #9CA3AF; font-size: 12px;">
            SETASPLAST SAS BIC - Comprometidos con la excelencia
        </div>
    </div>
@endsection