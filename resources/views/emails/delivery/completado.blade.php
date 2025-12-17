@extends('layouts.email-limpio')

@section('content')
<!-- ✅ Header principal - COMPLETADO -->
<div style="background: linear-gradient(135deg, #059669 0%, #10B981 100%); padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px;">

    <h2 style="color: white; margin: 0; font-size: 24px; font-weight: 600;">
        ¡Entrega Completada Exitosamente!
    </h2>
    <p style="color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-size: 14px;">
        Tu pedido ha sido entregado correctamente
    </p>
</div>

<!-- ✅ Saludo personalizado -->
<div style="background-color: #F0FDF4; padding: 20px; border-radius: 8px; border-left: 4px solid #10B981; margin-bottom: 25px;">
    <p style="margin: 0; color: #374151; font-size: 16px;">
        Hola
    </p>
    <p style="margin: 8px 0 0 0; color: #6B7280; font-size: 14px;">
        Nos complace confirmar que la entrega de tu pedido ha sido exitosamente.
    </p>
</div>



<!-- ✅ Resumen de la entrega -->
<div style="background-color: #FFFFFF; border: 2px solid #BBF7D0; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h3 style="color: #065F46; margin: 0 0 20px 0; font-size: 18px; font-weight: 600; border-bottom: 2px solid #DCFCE7; padding-bottom: 10px;">
         Resumen de tu entrega
    </h3>
    <!--Solo DAtos del vehiculo y conductor-->
    <table style="width: 100%; border-collapse: collapse;"></table>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #F3F4F6;">
                <div style="display: flex; align-items: center;">
                    <div style="background-color: #DCFCE7; padding: 8px; border-radius: 8px; margin-right: 12px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
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
                        <div style="color: #6B7280; font-size: 12px; font-weight: 500; margin-bottom: 2px;">CONDUCTOR ASIGNADO</div>
                        <div style="color: #374151; font-size: 16px; font-weight: 600;">
                            {{ $delivery->usuario->name ?? 'Conductor asignado' }}
                        </div>
                    </div>
                </div>
            </td>
        </tr>
 
</div>

<

<!-- ✅ SECCIÓN PRINCIPAL: Link para observaciones/solicitudes -->
<div style="background: linear-gradient(135deg, #FEF3C7 0%, #FCD34D 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 2px solid #F59E0B; text-align: center;">
    
    <h4 style="color: #92400E; margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">
        ¿Tienes alguna observación sobre la entrega?
    </h4>
    <p style="color: #78350F; margin: 0 0 20px 0; font-size: 14px; line-height: 1.5;">
        Si encontraste algún inconveniente con tu pedido o tienes alguna observación que reportar, 
        puedes radicar una solicitud directamente en nuestro sistema.
    </p>
    
    <!-- ✅ Botón principal para radicar solicitud -->
    <div style="margin: 20px 0;">
        <a href="{{ url('https://setasplast.com.co/pqr') }}" 
           style="background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%); 
                  color: white; 
                  padding: 15px 30px; 
                  border-radius: 25px; 
                  text-decoration: none; 
                  font-weight: 600; 
                  font-size: 16px;
                  display: inline-block; 
                  box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
                  transition: all 0.3s ease;">
            Radicar Solicitud / Reclamo
        </a>
    </div>
    
    <p style="color: #A16207; margin: 15px 0 0 0; font-size: 12px;">
        También puedes contactarnos directamente al <strong>📱 3112890067</strong>
    </p>
</div>

<!-- ✅ Información adicional -->
<div style="background-color: #F0F9FF; padding: 20px; border-radius: 12px; border-left: 4px solid #0EA5E9; margin-bottom: 25px;">
    <div style="display: flex; align-items: center; margin-bottom: 10px;">
        <div style="font-size: 20px; margin-right: 8px;">💡</div>
        <h4 style="color: #0C4A6E; margin: 0; font-size: 16px; font-weight: 600;">
            Información importante
        </h4>
    </div>
    <ul style="color: #0369A1; margin: 0; font-size: 14px; line-height: 1.5; padding-left: 20px;">
        <li><strong>Tiempo para reportar:</strong> Tienes hasta 24 horas para reportar cualquier inconveniente</li>
        <li><strong>Documentación:</strong> Si hay daños, toma fotos antes de usar el producto</li>
        <li><strong>Garantía:</strong> Todos nuestros productos tienen garantía de calidad</li>
        <li><strong>Seguimiento:</strong> Recibirás actualizaciones sobre cualquier solicitud radicada</li>
    </ul>
</div>

<!-- ✅ Invitación a futuros pedidos -->
<div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #93C5FD; text-align: center;">
   
    <h4 style="color: #1E40AF; margin: 0 0 8px 0; font-size: 16px; font-weight: 600;">
        ¿Necesitas hacer otro pedido?
    </h4>
    <p style="color: #1E3A8A; margin: 0 0 15px 0; font-size: 14px;">
        Estamos listos para atender tus próximas necesidades de productos plásticos
    </p>
    <a href="tel:3112890067" style="background-color: #1D4ED8; color: white; padding: 10px 20px; border-radius: 20px; text-decoration: none; font-weight: 500; display: inline-block; font-size: 14px;">
        📞 Hacer nuevo pedido
    </a>
</div>

<!-- ✅ Mensaje de agradecimiento final -->
<div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%); border-radius: 12px; margin-bottom: 20px;">
    <div style="font-size: 32px; margin-bottom: 10px;">🙏</div>
    <p style="color: #065F46; margin: 0; font-size: 18px; font-weight: 600; margin-bottom: 8px;">
        ¡Gracias por confiar en SETASPLAST!
    </p>
    <p style="color: #059669; margin: 0; font-size: 14px;">
        Esperamos haber superado tus expectativas. Seguimos trabajando para ti.
    </p>
</div>

<!-- ✅ Animación CSS para el checkmark -->
<style>
@keyframes checkmark {
  0% {
    transform: scale(0.8);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}
</style>

@endsection

@section('footer-text')
    <div style="text-align: center; padding: 15px; background-color: #F0FDF4; border-top: 1px solid #BBF7D0;">
        <div style="color: #059669; font-size: 14px; font-weight: 500; margin-bottom: 4px;">
            ✅ Entrega completada exitosamente
        </div>
        <div style="color: #065F46; font-size: 12px;">
            SETASPLAST SAS BIC - Tu satisfacción es nuestra prioridad
        </div>
    </div>
@endsection