    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>SetasPlast BIC</title>
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
            /* ✅ RESET MEJORADO */
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                margin: 0 !important; 
                padding: 0 !important; 
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6;
            }
            table { border-collapse: collapse; border-spacing: 0; width: 100%; }
            img { border: 0; display: block; max-width: 100%; height: auto; }
            a { color: #059669; text-decoration: none; transition: all 0.3s ease; }
            a:hover { color: #047857; }
            
            /* ✅ COMPONENTES REUTILIZABLES */
            .card-shadow { box-shadow: 0 20px 50px rgba(0,0,0,0.1), 0 6px 20px rgba(0,0,0,0.05); }
            .gradient-green { background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%); }
            .gradient-dark { background: linear-gradient(135deg, #1f2937 0%, #374151 50%, #4b5563 100%); }
            .text-shadow { text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            
            /* ✅ RESPONSIVE MEJORADO */
            @media only screen and (max-width: 680px) {
                .mobile-center { text-align: center !important; }
                .mobile-hide { display: none !important; }
                .mobile-padding { padding: 20px !important; }
                .mobile-font-small { font-size: 14px !important; }
                .mobile-font-title { font-size: 20px !important; }
                .mobile-stack { display: block !important; width: 100% !important; }
                .mobile-full-width { width: 100% !important; max-width: 100% !important; }
            }
            
            @media only screen and (max-width: 480px) {
                .mobile-font-title { font-size: 18px !important; }
                .mobile-font-small { font-size: 12px !important; }
                .mobile-padding { padding: 15px !important; }
            }

            /* ✅ ANIMACIONES SUTILES */
            .hover-lift:hover { transform: translateY(-2px); }
            .fade-in { animation: fadeIn 0.6s ease-in; }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>

        <!-- ✅ CONTENEDOR PRINCIPAL CON MEJOR DISEÑO -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); padding: 30px 10px;">
            <tr>
                <td align="center">
                    
                    <!-- ✅ EMAIL CONTAINER MEJORADO -->
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" class="card-shadow" style="width: 680px; max-width: 100%; background-color: #ffffff; border-radius: 24px; overflow: hidden; position: relative;">
                        
                        <!-- ✅ BARRA DECORATIVA SUPERIOR MEJORADA -->
                        <tr>
                            <td style="height: 8px; background: linear-gradient(90deg, #059669);"></td>
                        </tr>

                        <!-- ✅ SALUDO PERSONALIZADO MEJORADO -->
                        @if(!empty($saludo))
                        <tr>
                            <td style="padding: 40px 40px 20px 40px; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);" class="mobile-padding">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td align="center">
                                            <div style="
                                                background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
                                                border: 2px solid #d1fae5;
                                                border-radius: 20px;
                                                padding: 25px 30px;
                                                box-shadow: 0 10px 30px rgba(16, 185, 129, 0.1);
                                                position: relative;
                                                overflow: hidden;
                                            ">
                                                <!-- Decoración de fondo -->
                                                <div style="
                                                    position: absolute;
                                                    top: -50%;
                                                    right: -20px;
                                                    width: 100px;
                                                    height: 100px;
                                                    background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
                                                    border-radius: 50%;
                                                "></div>
                                                
                                                <p style="
                                                    font-size: 20px;
                                                    color: #065f46;
                                                    font-weight: 700;
                                                    margin: 0;
                                                    letter-spacing: 0.3px;
                                                    line-height: 1.4;
                                                    text-align: center;
                                                    position: relative;
                                                    z-index: 2;
                                                " class="mobile-font-title text-shadow">
                                                    ✨ {{ $saludo }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endif
                        
                        <!-- ✅ HEADER CON LOGOS ULTRA MEJORADO -->
                        @if(!empty($logos_empresas))
                        @php $logos = is_array($logos_empresas) ? $logos_empresas : json_decode($logos_empresas, true); @endphp
                        <tr>
                            <td class="gradient-green" style="color: #ffffff; text-align: center; padding: 50px 30px 40px 30px; position: relative; overflow: hidden;" class="mobile-padding">
                                
                                <!-- Elementos decorativos de fondo -->
                                <div style="position: absolute; top: -50px; left: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
                                <div style="position: absolute; bottom: -30px; right: -30px; width: 100px; height: 100px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); border-radius: 50%;"></div>

                                <!-- LOGOS EMPRESARIALES CON DISEÑO PREMIUM -->
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td align="center" style="padding-bottom: 35px; position: relative; z-index: 2;">
                                            <div style="
                                                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                                                border-radius: 24px;
                                                padding: 30px 35px;
                                                box-shadow: 0 20px 40px rgba(0,0,0,0.15), 0 8px 16px rgba(0,0,0,0.1);
                                                display: inline-block;
                                                max-width: 90%;
                                                border: 1px solid rgba(255,255,255,0.2);
                                            ">
                                             <div style="
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: nowrap;
    gap: 40px;
    overflow-x: auto;
    padding: 10px 0;
">
    @foreach($logos as $logo)
        @php
            $url = $logo['url'] ?? null;
            if ($url && !Str::startsWith($url, ['http', 'https'])) {
                $url = asset('storage/' . ltrim(str_replace('storage/', '', $url), '/'));
            }
        @endphp
        @if($url)
            <div class="hover-lift" style="transition: all 0.3s ease;">
                <img src="{{ $url }}" alt="{{ $logo['nombre'] ?? 'Logo' }}" style="
                    height: 80px;
                    width: auto;
                    display: block;
                    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
                ">
            </div>
        @endif
    @endforeach
</div>

                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                
                           
                                
                                <!-- SUBTÍTULO CON MEJOR TIPOGRAFÍA -->
                                <p style="
                                    margin: 0; 
                                    font-size: 16px; 
                                    color: rgba(255,255,255,0.95); 
                                    font-weight: 400; 
                                    letter-spacing: 0.3px; 
                                    max-width: 500px; 
                                    margin: 0 auto;
                                    position: relative; 
                                    z-index: 2;
                                " class="mobile-font-small">
                                    SetasPlast S.A.S BIC · Grupo Empresarial Global Business JS Group<br>
                                    <span style="color: rgba(255,255,255,0.8); font-size: 14px;">Innovación y Sostenibilidad</span>
                                </p>
                                
                                <!-- LÍNEA DECORATIVA MEJORADA -->
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td align="center" style="padding-top: 30px; position: relative; z-index: 2;">
                                            <div style="
                                                width: 120px; 
                                                height: 5px; 
                                                background: linear-gradient(90deg, rgba(255,255,255,0.8), rgba(255,255,255,0.3), rgba(255,255,255,0.8)); 
                                                border-radius: 3px; 
                                                margin: 0 auto;
                                                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                            "></div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endif
                        
                        <!-- ✅ CONTENIDO PRINCIPAL CON MEJOR TIPOGRAFÍA -->
                        @if(!empty($contenido_html))
                        <tr>
                            <td style="padding: 50px 40px;" class="mobile-padding">
                                <div style="
                                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                                    line-height: 1.7; 
                                    color: #374151; 
                                    font-size: 16px;
                                    max-width: 600px;
                                    margin: 0 auto;
                                ">
                                    {!! $contenido_html !!}
                                </div>
                            </td>
                        </tr>
                        @endif
                        
                        <!-- ✅ IMÁGENES DESTACADAS CON DISEÑO PREMIUM -->
                        @if(!empty($imagenes))
                        <tr>
                            <td style="padding: 50px 40px; background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);" class="mobile-padding">
                                <h3 style="
                                    color: #1f2937; 
                                    font-weight: 800; 
                                    text-align: center; 
                                    margin-bottom: 40px; 
                                    font-size: 28px;
                                    letter-spacing: -0.3px;
                                " class="mobile-font-title">
                                    🖼️ Galería Destacada
                                </h3>

                                <div style="
                                    display: grid;
                                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                                    gap: 30px;
                                    max-width: 800px;
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
                                        <div class="hover-lift" style="
                                            background: #ffffff;
                                            border-radius: 20px;
                                            overflow: hidden;
                                            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
                                            border: 1px solid #e5e7eb;
                                            transition: all 0.4s ease;
                                            position: relative;
                                        ">
                                            <div style="position: relative; overflow: hidden;">
                                         <img 
    src="{{ $url }}" 
    alt="{{ $imagen['titulo'] ?? 'Imagen Destacada' }}" 
    style="
        width: 100%;
        height: auto;
        object-fit: contain;
        display: block;
        transition: transform 0.4s ease;
        background-color: #f9fafb;
        border-radius: 12px;
    "
>

                                                <div style="
                                                    position: absolute;
                                                    bottom: 0;
                                                    left: 0;
                                                    right: 0;
                                                    height: 60px;
                                                    background: linear-gradient(transparent, rgba(0,0,0,0.6));
                                                "></div>
                                            </div>
                                         
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif

                        <!-- ✅ VIDEO CON DISEÑO PREMIUM -->
                        @if(!empty($video_url))
                        @php
                            $videoThumbnail = null;
                            if (Str::contains($video_url, ['youtube.com', 'youtu.be'])) {
                                if (preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                                    $videoId = $matches[1];
                                    $videoThumbnail = "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
                                }
                            } elseif (Str::contains($video_url, 'vimeo.com')) {
                                $videoThumbnail = 'https://i.vimeocdn.com/video/default-portrait.jpg';
                            }

                            if (!$videoThumbnail) {
                                $videoThumbnail = asset('storage/plantillas/videos/thumbnail_play.png');
                            }
                        @endphp

                        <tr>
                            <td style="padding: 50px 40px; text-align: center; background: #1f2937;" class="mobile-padding">
                                <h3 style="color: #ffffff; font-weight: 800; text-align: center; margin-bottom: 30px; font-size: 28px;" class="mobile-font-title text-shadow">
                                    🎬 Contenido Audiovisual
                                </h3>

                                <div style="position: relative; display: inline-block; max-width: 100%;">
                                    <a href="{{ $video_url }}" target="_blank" style="text-decoration: none; display: block; position: relative;">
                                        <img 
                                            src="{{ $videoThumbnail }}" 
                                            alt="Ver Video" 
                                            style="
                                                border-radius: 20px; 
                                                box-shadow: 0 25px 50px rgba(0,0,0,0.3); 
                                                max-width: 100%; 
                                                height: auto;
                                                max-height: 400px;
                                                width: auto;
                                            "
                                        >

                                        <!-- Overlay mejorado -->
                                        <div style="
                                            position: absolute;
                                            top: 50%;
                                            left: 50%;
                                            transform: translate(-50%, -50%);
                                            background: linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(220, 38, 38, 0.9));
                                            border-radius: 50%;
                                            width: 90px;
                                            height: 90px;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
                                            transition: all 0.3s ease;
                                        ">
                                            <div style="
                                                width: 0;
                                                height: 0;
                                                border-left: 28px solid white;
                                                border-top: 16px solid transparent;
                                                border-bottom: 16px solid transparent;
                                                margin-left: 6px;
                                            "></div>
                                        </div>
                                    </a>
                                </div>

                                <p style="font-size: 16px; color: #d1d5db; margin-top: 20px; font-weight: 500;">
                                    🎥 Haz clic para reproducir el contenido
                                </p>
                            </td>
                        </tr>
                        @endif

                        <!-- ✅ CERTIFICACIONES CON DISEÑO PREMIUM -->
                        @if(!empty($certificaciones))
                        @php $certs = is_array($certificaciones) ? $certificaciones : json_decode($certificaciones, true); @endphp
                        <tr>
                            <td style="padding: 50px 40px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);" class="mobile-padding">
                                <h3 style="
                                    color: #0c4a6e; 
                                    font-weight: 800; 
                                    text-align: center; 
                                    margin-bottom: 40px; 
                                    font-size: 28px;
                                    letter-spacing: -0.3px;
                                " class="mobile-font-title">
                                    🏆 Certificaciones y Reconocimientos
                                </h3>

                                <div style="
                                    display: grid;
                                    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                                    gap: 25px;
                                    max-width: 900px;
                                    margin: 0 auto;
                                ">
                                    @foreach($certs as $cert)
                                        @php
                                            $logo = $cert['logo'] ?? null;
                                            if ($logo && !Str::startsWith($logo, ['http', 'https'])) {
                                                $logo = asset('storage/' . ltrim(str_replace('storage/', '', $logo), '/'));
                                            }
                                        @endphp

                                        <div class="hover-lift" style="
                                            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                                            border-radius: 20px;
                                            padding: 30px 20px;
                                            text-align: center;
                                            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
                                            border: 2px solid #e0f2fe;
                                            transition: all 0.3s ease;
                                            position: relative;
                                            overflow: hidden;
                                        ">
                                            <!-- Decoración de fondo -->
                                            <div style="
                                                position: absolute;
                                                top: -20px;
                                                right: -20px;
                                                width: 60px;
                                                height: 60px;
                                                background: radial-gradient(circle, rgba(14, 165, 233, 0.1) 0%, transparent 70%);
                                                border-radius: 50%;
                                            "></div>

                                            @if(!empty($logo))
                                                <img src="{{ $logo }}" alt="{{ $cert['nombre'] ?? 'Certificación' }}" style="
                                                    height: 80px;
                                                    width: auto;
                                                    margin: 0 auto 15px;
                                                    display: block;
                                                    object-fit: contain;
                                                    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
                                                ">
                                            @endif
                                            @if(!empty($cert['nombre']))
                                                <p style="
                                                    font-size: 14px; 
                                                    font-weight: 700; 
                                                    color: #0c4a6e; 
                                                    margin: 0;
                                                    line-height: 1.4;
                                                ">
                                                    {{ $cert['nombre'] }}
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif
                        
                        <!-- ✅ DESCARGAS CON MEJOR DISEÑO -->
                   <!-- ✅ DESCARGAS CORPORATIVAS -->
@if(!empty($descargas))
@php $files = is_array($descargas) ? $descargas : json_decode($descargas, true); @endphp
<tr>
    <td style="padding: 50px 40px; text-align: center; background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);" class="mobile-padding">
        <h3 style="color: #ecfdf5; font-weight: 800; text-align: center; margin-bottom: 30px; font-size: 28px;" class="mobile-font-title">
            📚 Recursos Descargables
        </h3>
        
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; max-width: 600px; margin: 0 auto;">
            @foreach($files as $file)
                <a href="{{ $file['link'] ?? '#' }}" target="_blank" class="hover-lift" style="
                    display: inline-flex;
                    align-items: center;
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: #ffffff;
                    padding: 18px 28px;
                    border-radius: 25px;
                    font-weight: 700;
                    text-decoration: none;
                    font-size: 15px;
                    box-shadow: 0 8px 20px rgba(6, 95, 70, 0.4);
                    transition: all 0.3s ease;
                    border: 2px solid rgba(255,255,255,0.1);
                ">
                    <span style="margin-right: 8px; font-size: 18px;">📄</span>
                    {{ ucfirst($file['nombre'] ?? 'Archivo') }}
                </a>
            @endforeach
        </div>
    </td>
</tr>
@endif

                        
                        <!-- ✅ REDES SOCIALES MEJORADAS -->
                   <!-- ✅ REDES SOCIALES CORPORATIVAS -->
@if(!empty($redes_sociales))
@php $redes = is_array($redes_sociales) ? $redes_sociales : json_decode($redes_sociales, true); @endphp
<tr>
    <td style="padding: 50px 40px; text-align: center; background: linear-gradient(135deg, #1f2937 0%, #111827 100%);" class="mobile-padding">
        <h3 style="color: #d1fae5; font-weight: 800; text-align: center; margin-bottom: 30px; font-size: 28px;" class="mobile-font-title">
            🌐 Conéctate con Nosotros
        </h3>
        
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; max-width: 600px; margin: 0 auto;">
            @foreach($redes as $red)
                <a href="{{ $red['url'] ?? '#' }}" target="_blank" class="hover-lift" style="
                    display: inline-flex;
                    align-items: center;
                    padding: 16px 24px;
                    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
                    color: #ffffff;
                    border-radius: 20px;
                    font-weight: 600;
                    border: 2px solid rgba(255,255,255,0.1);
                    text-decoration: none;
                    transition: all 0.3s ease;
                    box-shadow: 0 6px 15px rgba(16, 185, 129, 0.25);
                ">
                    <span style="margin-right: 8px;">🔗</span>
                    {{ ucfirst($red['nombre'] ?? 'Red Social') }}
                </a>
            @endforeach
        </div>
    </td>
</tr>
@endif

                        
                        <!-- ✅ FOOTER PREMIUM -->
                        <tr>
                            <td class="gradient-dark" style="color: #f3f4f6; text-align: center; padding: 50px 40px; position: relative; overflow: hidden;" class="mobile-padding">
                                <!-- Elementos decorativos de fondo -->
                                <div style="position: absolute; top: -30px; left: -30px; width: 120px; height: 120px; background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%); border-radius: 50%;"></div>
                                <div style="position: absolute; bottom: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(52, 211, 153, 0.08) 0%, transparent 70%); border-radius: 50%;"></div>
                                
                                <!-- Barra decorativa superior -->
                                <div style="position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, #059669, #10b981, #34d399, #6ee7b7);"></div>
                                
                                <div style="position: relative; z-index: 2;">
                                    <h4 style="margin: 0 0 20px 0; font-size: 24px; font-weight: 800; color: #ffffff;" class="mobile-font-title text-shadow">
                                        SetasPlast S.A.S BIC
                                    </h4>
                                    
                                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; margin-bottom: 25px;">
                                        <div style="background: rgba(255,255,255,0.1); padding: 15px 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);">
                                            <p style="margin: 0; font-size: 14px; font-weight: 600;">🏢 Empresa B Certificada</p>
                                        </div>
                                        <div style="background: rgba(255,255,255,0.1); padding: 15px 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);">
                                            <p style="margin: 0; font-size: 14px; font-weight: 600;">📋 ISO 9001 - 14001 - 45001</p>
                                        </div>
                                    </div>
                                    
                                    <p style="margin: 15px 0; font-size: 16px; color: #d1d5db; font-weight: 500;" class="mobile-font-small">
                                        Comprometidos con la Innovación y Sostenibilidad
                                    </p>
                                    
                                    <div style="margin: 25px 0;">
                                        <a href="https://setasplast.com.co" target="_blank" style="color: #6ee7b7; font-weight: 700; text-decoration: none; margin: 0 15px; font-size: 16px;">🌐 setasplast.com.co</a>
                                        <a href="mailto:info@setasplast.com.co" style="color: #6ee7b7; font-weight: 700; text-decoration: none; margin: 0 15px; font-size: 16px;">📧 info@setasplast.com.co</a>
                                    </div>
                                    
                                    <p style="margin: 20px 0 0 0; font-size: 14px; color: #9ca3af; font-weight: 400;" class="mobile-font-small">
                                        © {{ date('Y') }} SetasPlast S.A.S BIC - Todos los derechos reservados
                                    </p>
                                </div>
                            </td>
                        </tr>
                        
                    </table>
                    
                </td>
            </tr>
        </table>
        
    </body>
    </html>