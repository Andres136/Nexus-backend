@extends('layouts.email-limpio')

@section('content')

<!-- Header principal -->
<div style="background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%); padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
    <div style="font-size: 44px; margin-bottom: 10px;">📦</div>
    <h2 style="color: #78350F; margin: 0; font-size: 24px; font-weight: 600;">
        Actualización de tu entrega
    </h2>
    <p style="color: rgba(120,53,15,0.9); margin: 8px 0 0 0; font-size: 14px;">
        Queremos mantenerte informado
    </p>
</div>

<!-- Saludo -->
<div style="background-color: #FFFBEB; padding: 20px; border-radius: 8px; border-left: 4px solid #F59E0B; margin-bottom: 25px;">
    <p style="margin: 0; color: #374151; font-size: 16px;">
        Hola 👋
    </p>
    <p style="margin: 8px 0 0 0; color: #6B7280; font-size: 14px; line-height: 1.6;">
        Te informamos que tu pedido programado para <strong>entrega el día de hoy</strong> presenta una novedad,
        por lo cual <strong style="color:#D97706;">no podrá ser entregado en la fecha prevista</strong>.
    </p>
</div>

<!-- Mensaje principal -->
<div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #93C5FD; text-align: center;">
  
    <h4 style="color: #1E40AF; margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">
        Estamos gestionando la reprogramación
    </h4>
    <p style="color: #1E3A8A; margin: 0 0 20px 0; font-size: 14px; line-height: 1.6;">
        Nuestro equipo ya se encuentra validando una nueva fecha de entrega.
        <strong>Te notificaremos oportunamente</strong> tan pronto la reprogramación esté confirmada.
    </p>

    <a href="tel:3112890067"
       style="background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
              color: white;
              padding: 12px 26px;
              border-radius: 20px;
              text-decoration: none;
              font-weight: 600;
              font-size: 14px;
              display: inline-block;
              box-shadow: 0 3px 10px rgba(59,130,246,0.3);">
        Contactar servicio al cliente
    </a>
</div>

<!-- Soporte -->
<div style="background-color: #F0F9FF; padding: 20px; border-radius: 12px; border-left: 4px solid #3B82F6; margin-bottom: 25px;">
    <h4 style="color: #1E3A8A; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">
        ¿Necesitas más información?
    </h4>
    <ul style="margin: 0; padding-left: 20px; color: #1E40AF; font-size: 14px;">
        <li>Estado actual de tu pedido</li>
        <li>Nueva fecha estimada de entrega</li>
        <li>Alternativas de solución</li>
    </ul>
    <div style="text-align:center; margin-top:12px;">
        <strong style="font-size:15px;">📱 311 289 0067</strong>
    </div>
</div>

<!-- Cierre -->
<div style="text-align: center; padding: 22px; background: #FFFBEB; border-radius: 12px;">
    <div style="font-size: 28px; margin-bottom: 8px;">🙏</div>
    <p style="color: #92400E; margin: 0; font-size: 16px; font-weight: 600;">
        Gracias por tu comprensión
    </p>
    <p style="color: #78350F; margin: 6px 0 0 0; font-size: 14px;">
        En SETASPLAST trabajamos para brindarte una mejor experiencia
    </p>
</div>

@endsection

@section('footer-text')
<div style="text-align: center; padding: 15px; background-color: #FFFBEB; border-top: 1px solid #FDE68A;">
    <div style="color: #92400E; font-size: 13px;">
        Notificación de entrega – SETASPLAST S.A.S. BIC
    </div>
</div>
@endsection
