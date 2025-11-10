<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $titulo ?? 'SetasPlast BIC' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        /* ✅ RESET BÁSICO PARA EMAILS */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0 !important; padding: 0 !important; background-color: #f5f7fa; font-family: Arial, Helvetica, sans-serif; }
        table { border-collapse: collapse; border-spacing: 0; width: 100%; }
        img { border: 0; display: block; max-width: 100%; height: auto; }
        a { color: #27ae60; text-decoration: none; }
        
        /* ✅ RESPONSIVE BÁSICO */
        @media only screen and (max-width: 680px) {
            .mobile-center { text-align: center !important; }
            .mobile-hide { display: none !important; }
            .mobile-padding { padding: 15px !important; }
            .mobile-font-small { font-size: 14px !important; }
            .mobile-font-title { font-size: 20px !important; }
            .mobile-stack { display: block !important; width: 100% !important; }
        }
        
        @media only screen and (max-width: 480px) {
            .mobile-font-title { font-size: 18px !important; }
            .mobile-font-small { font-size: 12px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f7fa; font-family: Arial, Helvetica, sans-serif; color: #2c3e50;">
@if(!empty($saludo))
<tr>
  <td style="padding: 35px 40px 15px 40px; background-color: #ffffff; text-align: center;" class="mobile-padding">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
      <tr>
        <td align="center" style="padding: 15px 20px; background: rgba(25,135,84,0.07); border-radius: 15px; max-width: 600px; margin: 0 auto;">
          <p style="
            font-size: 18px;
            color: #004225;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.3px;
            line-height: 1.5;
            text-align: center;
          ">
            {{ $saludo }}
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endif


    <!-- ✅ CONTENEDOR PRINCIPAL -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f5f7fa;">
        <tr>
            <td align="center" style="padding: 20px 10px;">
                
                <!-- ✅ EMAIL CONTAINER -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" style="width: 680px; max-width: 100%; background-color: #ffffff; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.15); position: relative; overflow: hidden;">
                    
                    <!-- ✅ BARRA DECORATIVA SUPERIOR -->
                    <tr>
                        <td style="height: 6px; background: linear-gradient(90deg, #004225, #198754, #28a745, #20c997);"></td>
                    </tr>
                    
                    <!-- ✅ HEADER CON LOGOS MEJORADO -->
                    @if(!empty($logos_empresas))
                    @php $logos = is_array($logos_empresas) ? $logos_empresas : json_decode($logos_empresas, true); @endphp
                    <tr>
                        <td style="background: linear-gradient(135deg, #004225 0%, #198754 50%, #28a745 100%); color: #ffffff; text-align: center; padding: 45px 30px 30px 30px; position: relative;" class="mobile-padding">
                            
                            <!-- LOGOS EMPRESARIALES CENTRADOS CON FONDO BLANCO -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom: 30px;">
                                        <!-- Contenedor con fondo blanco redondeado -->
                                        <div style="background: #ffffff; border-radius: 20px; padding: 25px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); display: inline-block; max-width: 90%;">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                                                <tr>
                                                    @foreach($logos as $index => $logo)
                                                        @php
                                                            $url = $logo['url'] ?? null;
                                                            if ($url && !Str::startsWith($url, ['http', 'https'])) {
                                                                $url = asset('storage/' . ltrim(str_replace('storage/', '', $url), '/'));
                                                            }
                                                        @endphp
                                                        @if($url)
                                                            <td align="center" style="padding: 0 15px;" class="mobile-stack">
                                                                <img src="{{ $url }}" alt="{{ $logo['nombre'] ?? 'Logo' }}" style="height: 70px; width: auto; display: block; margin: 0 auto;">
                                                            </td>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- TÍTULO PRINCIPAL -->
                     
                            
                            <!-- SUBTÍTULO -->
                            <p style="margin: 0; font-size: 15px; color: rgba(255,255,255,0.9); font-weight: 400; letter-spacing: 0.5px;" class="mobile-font-small">
                                SetasPlast S.A.S BIC · Grupo Empresarial Global Business JS Group · Innovación y Sostenibilidad
                            </p>
                            
                            <!-- LÍNEA DECORATIVA -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-top: 25px;">
                                        <div style="width: 100px; height: 4px; background: linear-gradient(90deg, #20c997, #17a2b8); border-radius: 2px; margin: 0 auto;"></div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif
                    
                    <!-- ✅ CONTENIDO PRINCIPAL -->
                    @if(!empty($contenido_html))
                    <tr>
                        <td style="padding: 40px 30px;" class="mobile-padding">
                            <div style="font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #2c3e50;">
                                {!! $contenido_html !!}
                            </div>
                        </td>
                    </tr>
                    @endif
                    
              <!-- ✅ IMÁGENES DESTACADAS SIN OVERLAY -->
@if(!empty($imagenes))
<tr>
  <td style="padding: 40px 30px;" class="mobile-padding">
 

    <!-- ✅ Contenedor flexible y adaptable -->
    <div style="
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: flex-start;
      gap: 25px;
      width: 100%;
      margin: 0 auto;
    ">
      @foreach($imagenes as $imagen)
        @php
          $url = $imagen['url'] ?? null;
          if ($url && !Str::startsWith($url, ['http', 'https'])) {
              $url = asset('storage/' . ltrim(str_replace('storage/', '', $url), '/'));
          }
        @endphp

        @if($url)
        <div style="
          flex: 1 1 calc(45% - 25px);
          max-width: calc(45% - 25px);
          background: #ffffff;
          border-radius: 15px;
          overflow: hidden;
          text-align: center;
          border: 2px solid transparent;
          box-shadow: 0 6px 20px rgba(0,0,0,0.08);
          transition: all 0.35s ease;
        "
        onmouseover="this.style.transform='scale(1.03)'; this.style.border='2px solid #198754'; this.style.boxShadow='0 10px 30px rgba(25,135,84,0.3)';"
        onmouseout="this.style.transform='scale(1)'; this.style.border='2px solid transparent'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.08)';"
        >
          <img 
            src="{{ $url }}" 
            alt="{{ $imagen['nombre'] ?? 'Imagen Destacada' }}" 
            style="
              width: 100%;
              height: auto;
              display: block;
              border-bottom: 2px solid #198754;
            "
          >
          @if(!empty($imagen['nombre']))
            <p style="margin: 12px 0 15px; font-size: 14px; font-weight: 600; color: #34495e;">
              {{ $imagen['nombre'] }}
            </p>
          @endif
        </div>
        @endif
      @endforeach
    </div>

    <!-- ✅ Ajuste responsive -->
    <style>
      @media only screen and (max-width: 680px) {
        .mobile-padding div[style*="flex-wrap"] > div {
          flex: 1 1 100% !important;
          max-width: 100% !important;
        }
      }
    </style>
  </td>
</tr>
@endif


                    
@if(!empty($video_url))
@php
    $videoThumbnail = null;

    // Detectar si es un enlace de YouTube y generar thumbnail
    if (Str::contains($video_url, ['youtube.com', 'youtu.be'])) {
        // Extraer el ID del video (para ambas variantes)
        if (preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
            $videoId = $matches[1];
            $videoThumbnail = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
        }
    } 
    // Detectar Vimeo
    elseif (Str::contains($video_url, 'vimeo.com')) {
        $videoThumbnail = 'https://i.vimeocdn.com/video/default.jpg';
    }

    // Si no es YouTube ni Vimeo, usar imagen genérica local
    if (!$videoThumbnail) {
        $videoThumbnail = asset('storage/plantillas/videos/thumbnail_play.png');
    }
@endphp

<tr>
  <td style="padding: 40px 30px; text-align: center;" class="mobile-padding">


    <!-- Imagen de portada dinámica con enlace al video -->
    <a href="{{ $video_url }}" target="_blank" style="text-decoration: none; display: inline-block; position: relative;">
      <img 
        src="{{ $videoThumbnail }}" 
        alt="Ver Video" 
        style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); max-width: 100%; height: auto;"
      >

      <!-- Overlay tipo botón play -->
      <div style="
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0,0,0,0.5);
        border-radius: 50%;
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
      ">
        <div style="
          width: 0;
          height: 0;
          border-left: 20px solid white;
          border-top: 12px solid transparent;
          border-bottom: 12px solid transparent;
        "></div>
      </div>
    </a>

    <p style="font-size: 14px; color: #2c3e50; margin-top: 10px;">
      🎥 Haz clic en la imagen para ver el video completo
    </p>
  </td>
</tr>
@endif


                    
                <!-- ✅ CERTIFICACIONES RESPONSIVE MEJORADAS -->
<!-- ✅ CERTIFICACIONES RESPONSIVE CENTRADAS Y CON ESPACIO -->
@if(!empty($certificaciones))
@php $certs = is_array($certificaciones) ? $certificaciones : json_decode($certificaciones, true); @endphp
<tr>
  <td style="padding: 40px 30px; background: #f8f9fa;" class="mobile-padding">
    <h3 style="color: #34495e; font-weight: 700; text-align: center; margin-bottom: 30px; font-size: 22px;" class="mobile-font-title">
      Certificaciones y Reconocimientos
    </h3>

    <!-- Contenedor flexible centrado -->
    <div style="
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: flex-start;
      gap: 30px;
      max-width: 800px;
      margin: 0 auto;
    ">
      @foreach($certs as $cert)
        @php
          $logo = $cert['logo'] ?? null;
          if ($logo && !Str::startsWith($logo, ['http', 'https'])) {
              $logo = asset('storage/' . ltrim(str_replace('storage/', '', $logo), '/'));
          }
        @endphp

        <div style="
          flex: 1 1 160px;
          max-width: 180px;
          background: #ffffff;
          border-radius: 15px;
          padding: 25px 18px;
          text-align: center;
          box-shadow: 0 8px 25px rgba(0,0,0,0.08);
          border: 1.5px solid #e0e0e0;
          transition: all 0.3s ease;
        ">
          @if(!empty($logo))
            <img src="{{ $logo }}" alt="{{ $cert['nombre'] ?? 'Certificación' }}" style="
              height: 75px;
              width: auto;
              margin: 0 auto 12px;
              display: block;
              object-fit: contain;
            ">
          @endif
          @if(!empty($cert['nombre']))
            <p style="font-size: 13px; font-weight: 600; color: #34495e; margin: 0;">
              {{ $cert['nombre'] }}
            </p>
          @endif
        </div>
      @endforeach
    </div>

    <!-- Ajuste responsive -->
    <style>
      @media only screen and (max-width: 680px) {
        .mobile-padding div[style*="flex-wrap"] > div {
          flex: 1 1 calc(45% - 15px) !important;
          max-width: 45% !important;
        }
      }
      @media only screen and (max-width: 480px) {
        .mobile-padding div[style*="flex-wrap"] > div {
          flex: 1 1 100% !important;
          max-width: 100% !important;
        }
      }
    </style>
  </td>
</tr>
@endif


                    
                    <!-- ✅ DESCARGAS -->
                    @if(!empty($descargas))
                    @php $files = is_array($descargas) ? $descargas : json_decode($descargas, true); @endphp
                    <tr>
                        <td style="padding: 40px 30px; text-align: center;" class="mobile-padding">
                            <h3 style="color: #34495e; font-weight: 700; text-align: center; margin-bottom: 25px; font-size: 22px;" class="mobile-font-title">Recursos Descargables</h3>
                            
                            @foreach($files as $file)
                                <a href="{{ $file['link'] ?? '#' }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: #ffffff; padding: 16px 32px; border-radius: 50px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; text-decoration: none; margin: 8px; font-size: 14px;">
                                    📄 {{ ucfirst($file['nombre'] ?? 'Archivo') }}
                                </a>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                    
                    <!-- ✅ REDES SOCIALES -->
                    @if(!empty($redes_sociales))
                    @php $redes = is_array($redes_sociales) ? $redes_sociales : json_decode($redes_sociales, true); @endphp
                    <tr>
                        <td style="padding: 40px 30px; text-align: center;" class="mobile-padding">
                            <h3 style="color: #34495e; font-weight: 700; text-align: center; margin-bottom: 25px; font-size: 22px;" class="mobile-font-title">Conéctate con Nosotros</h3>
                            
                            @foreach($redes as $red)
                                <a href="{{ $red['url'] ?? '#' }}" target="_blank" style="display: inline-block; margin: 0 12px 12px 12px; padding: 15px 25px; background: rgba(39,174,96,0.1); color: #27ae60; border-radius: 30px; font-weight: 600; border: 2px solid rgba(39,174,96,0.2); text-decoration: none;">
                                    {{ ucfirst($red['nombre'] ?? 'Red Social') }}
                                </a>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                    
                    <!-- ✅ FOOTER -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: #ecf0f1; text-align: center; padding: 45px 30px; position: relative;" class="mobile-padding">
                            <!-- Barra decorativa superior -->
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #27ae60, #2ecc71, #20c997, #17a2b8);"></div>
                            
                            <p style="margin: 8px 0; font-size: 15px; line-height: 1.6;" class="mobile-font-small">
                                <strong>© {{ date('Y') }} SetasPlast S.A.S BIC</strong>
                            </p>
                            <p style="margin: 8px 0; font-size: 15px; line-height: 1.6;" class="mobile-font-small">
                                Empresa B Certificada | ISO 9001 - 14001 - 45001
                            </p>
                            <p style="margin: 8px 0; font-size: 15px; line-height: 1.6;" class="mobile-font-small">
                                Comprometidos con la Innovación y Sostenibilidad
                            </p>
                            <p style="margin: 20px 0 8px 0; font-size: 15px; line-height: 1.6;" class="mobile-font-small">
                                <a href="https://setasplast.com.co" target="_blank" style="color: #20c997; font-weight: 700; text-decoration: none;">www.setasplast.com.co</a> | 
                                <a href="mailto:info@setasplast.com.co" style="color: #20c997; font-weight: 700; text-decoration: none;">info@setasplast.com.co</a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>