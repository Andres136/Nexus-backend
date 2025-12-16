@extends('layouts.email-limpio')

@section('content')
<!-- ✅ Header principal - EN RUTA -->
<div style="background: linear-gradient(135deg, #059669 0%, #10B981 100%); padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
    <div style="font-size: 48px; margin-bottom: 10px;">🚚</div>
    <h2 style="color: white; margin: 0; font-size: 24px; font-weight: 600;">
        ¡Tu pedido va en camino!
    </h2>
    <p style="color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-size: 14px;">
        El conductor ya salió hacia tu dirección
    </p>
</div>

<!-- ✅ Saludo personalizado -->
<div style="background-color: #F0FDF4; padding: 20px; border-radius: 8px; border-left: 4px solid #10B981; margin-bottom: 25px;">
    <p style="margin: 0; color: #374151; font-size: 16px;">
        Hola <strong style="color: #059669;">{{ $cliente->nombre }}</strong>, 👋
    </p>
    <p style="margin: 8px 0 0 0; color: #6B7280; font-size: 14px;">
        Tenemos una entrega <strong style="color: #059669;">EN RUTA</strong> y va camino a tu ubicación.
    </p>
</div>




<!-- ✅ Detalles de entrega mejorados -->
<div style="background-color: #FFFFFF; border: 2px solid #E5E7EB; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h3 style="color: #374151; margin: 0 0 20px 0; font-size: 18px; font-weight: 600; border-bottom: 2px solid #F3F4F6; padding-bottom: 10px;">
        🚛 Información de tu entrega
    </h3>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #F3F4F6;">
                <div style="display: flex; align-items: center;">
                    <div style="background-color: #DCFCE7; padding: 8px; border-radius: 8px; margin-right: 12px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                        📅
                    </div>
                    <div>
                        <div style="color: #6B7280; font-size: 12px; font-weight: 500; margin-bottom: 2px;">FECHA DE ENTREGA</div>
                        <div style="color: #374151; font-size: 16px; font-weight: 600;">{{ $delivery->fecha_entrega }}</div>
                    </div>
                </div>
            </td>
        </tr>
        
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #F3F4F6;">
                <div style="display: flex; align-items: center;">
                    <div style="background-color: #FEF3E2; padding: 8px; border-radius: 8px; margin-right: 12px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                        👨‍💼
                    </div>
                    <div>
                        <div style="color: #6B7280; font-size: 12px; font-weight: 500; margin-bottom: 2px;">CONDUCTOR ASIGNADO</div>
                        <div style="color: #374151; font-size: 16px; font-weight: 600;">
                            {{ $delivery->usuario->name ?? 'Conductor asignado' }}
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        
        <tr>
            <td style="padding: 12px 0;">
                <div style="display: flex; align-items: center;">
                    <div style="background-color: #E0F2FE; padding: 8px; border-radius: 8px; margin-right: 12px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                        🚛
                    </div>
                    <div>
                        <div style="color: #6B7280; font-size: 12px; font-weight: 500; margin-bottom: 2px;">VEHÍCULO</div>
                        <div style="color: #374151; font-size: 16px; font-weight: 600;">
                            {{ $delivery->vehiculo->placa ?? 'Vehículo asignado' }}
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
            ¿Necesitas contactarnos?
        </h4>
    </div>
    <p style="color: #1E3A8A; margin: 0; font-size: 14px; line-height: 1.5;">
        Si tienes alguna inquietud sobre tu entrega o necesitas coordinar la recepción, 
        puedes contactarnos directamente:
    </p>
    <div style="text-align: center; margin-top: 15px;">
        <a href="tel:3112890067" style="background-color: #1D4ED8; color: white; padding: 12px 24px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-block; box-shadow: 0 2px 10px rgba(29, 78, 216, 0.3);">
            📱 3112890067
        </a>
    </div>
</div>



<!-- ✅ Instrucciones importantes -->
<div style="background-color: #F0F9FF; padding: 20px; border-radius: 12px; border-left: 4px solid #0EA5E9; margin-bottom: 25px;">
    <div style="display: flex; align-items: center; margin-bottom: 10px;">
        <div style="font-size: 20px; margin-right: 8px;">📋</div>
        <h4 style="color: #0C4A6E; margin: 0; font-size: 16px; font-weight: 600;">
            Instrucciones importantes
        </h4>
    </div>
    <ul style="color: #0369A1; margin: 0; font-size: 14px; line-height: 1.5; padding-left: 20px;">
        <li>Mantente disponible para recibir la entrega</li>
        <li>Verifica la cantidad y estado de los productos</li>
        <li>Si no puedes recibir personalmente, autoriza a alguien de confianza</li>
    </ul>
</div>

<!-- ✅ Mensaje de agradecimiento mejorado -->
<div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%); border-radius: 12px; margin-bottom: 20px;">
    <div style="font-size: 32px; margin-bottom: 10px;">🙏</div>
    <p style="color: #065F46; margin: 0; font-size: 18px; font-weight: 600; margin-bottom: 8px;">
        ¡Casi llegamos!
    </p>
    <p style="color: #059669; margin: 0; font-size: 14px;">
        Gracias por tu paciencia, pronto tendrás tu pedido
    </p>
</div>

<!-- ✅ Animación CSS para el pulso -->
<style>
@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
  }
}
</style>

@endsection

@section('footer-text')
    <div style="text-align: center; padding: 15px; background-color: #F0FDF4; border-top: 1px solid #BBF7D0;">
        <div style="color: #059669; font-size: 14px; font-weight: 500; margin-bottom: 4px;">
            🚚 Tu pedido en camino
        </div>
        <div style="color: #065F46; font-size: 12px;">
            SETASPLAST SAS BIC - Entregas confiables y puntuales
        </div>
    </div>
@endsection