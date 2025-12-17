@extends('layouts.email-limpio')

@section('content')
<!-- ✅ Header principal - CANCELADO -->
<div style="background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%); padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
    <div style="font-size: 48px; margin-bottom: 10px;">❌</div>
    <h2 style="color: white; margin: 0; font-size: 24px; font-weight: 600;">
        Entrega Cancelada
    </h2>
    <p style="color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-size: 14px;">
        Lamentamos informarte sobre esta situación
    </p>
</div>

<!-- ✅ Saludo personalizado -->
<div style="background-color: #FEF2F2; padding: 20px; border-radius: 8px; border-left: 4px solid #DC2626; margin-bottom: 25px;">
    <p style="margin: 0; color: #374151; font-size: 16px;">
        Hola 👋
    </p>
    <p style="margin: 8px 0 0 0; color: #6B7280; font-size: 14px;">
        Lamentamos informarte que la entrega de tu Entrega ha sido <strong style="color: #DC2626;">CANCELADA</strong>
    </p>
</div>




<!-- ✅ SECCIÓN PRINCIPAL: Opciones para el cliente -->
<div style="background: linear-gradient(135deg, #FEF3C7 0%, #FCD34D 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 2px solid #F59E0B; text-align: center;">
    <div style="font-size: 32px; margin-bottom: 15px;">🔄</div>
    <h4 style="color: #92400E; margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">
        ¿Qué puedes hacer ahora?
    </h4>
    <p style="color: #78350F; margin: 0 0 20px 0; font-size: 14px; line-height: 1.5;">
        No te preocupes, podemos ayudarte a reprogramar tu entrega o resolver cualquier inquietud que tengas.
    </p>
    

        
        <a href="tel:3112890067" 
           style="background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); 
                  color: white; 
                  padding: 12px 25px; 
                  border-radius: 20px; 
                  text-decoration: none; 
                  font-weight: 600; 
                  font-size: 14px;
                  display: inline-block; 
                  box-shadow: 0 3px 10px rgba(59, 130, 246, 0.3);">
             Solicitar información
        </a>
    </div>
</div>

<!-- ✅ Información de contacto -->
<div style="background-color: #F0F9FF; padding: 20px; border-radius: 12px; border-left: 4px solid #3B82F6; margin-bottom: 25px;">
    <div style="display: flex; align-items: center; margin-bottom: 12px;">
        <div style="font-size: 20px; margin-right: 8px;">📞</div>
        <h4 style="color: #1E3A8A; margin: 0; font-size: 16px; font-weight: 600;">
            Estamos aquí para ayudarte
        </h4>
    </div>
    <div style="color: #1E40AF; font-size: 14px; line-height: 1.6;">
        <p style="margin: 0 0 10px 0;">
            <strong>Nuestro equipo de servicio al cliente está disponible para:</strong>
        </p>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Explicarte el motivo de la cancelación</li>
            <li>Reprogramar tu entrega para una nueva fecha</li>
            <li>Resolver cualquier inquietud sobre tu pedido</li>
            <li>Ofrecerte alternativas de solución</li>
        </ul>
        <div style="text-align: center; margin-top: 15px;">
            <strong style="color: #1D4ED8; font-size: 16px;">📱 3112890067</strong>
        </div>
    </div>
</div>

<!-- ✅ Compromiso de la empresa -->
<div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #93C5FD; text-align: center;">
    <div style="font-size: 24px; margin-bottom: 10px;">🤝</div>
    <h4 style="color: #1E40AF; margin: 0 0 8px 0; font-size: 16px; font-weight: 600;">
        Nuestro compromiso contigo
    </h4>
    <p style="color: #1E3A8A; margin: 0; font-size: 14px; line-height: 1.5;">
        En SETASPLAST nos esforzamos por cumplir todos nuestros compromisos. 
        Cuando algo no sale según lo planeado, trabajamos el doble para solucionarlo.
    </p>
</div>

<!-- ✅ Disculpas y mensaje de esperanza -->
<div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%); border-radius: 12px; margin-bottom: 20px;">
    <div style="font-size: 32px; margin-bottom: 10px;">🙏</div>
    <p style="color: #991B1B; margin: 0; font-size: 18px; font-weight: 600; margin-bottom: 8px;">
        Lamentamos los inconvenientes
    </p>
    <p style="color: #DC2626; margin: 0; font-size: 14px;">
        Valoramos tu comprensión y esperamos poder servirte mejor muy pronto
    </p>
</div>

@endsection

@section('footer-text')
    <div style="text-align: center; padding: 15px; background-color: #FEF2F2; border-top: 1px solid #FECACA;">
        <div style="color: #DC2626; font-size: 14px; font-weight: 500; margin-bottom: 4px;">
            ❌ Entrega cancelada - Estamos para ayudarte
        </div>
        <div style="color: #991B1B; font-size: 12px;">
            SETASPLAST SAS BIC - Tu satisfacción es nuestra prioridad
        </div>
    </div>
@endsection