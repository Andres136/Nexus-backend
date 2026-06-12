<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>SetasPlast BIC</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style type="text/css">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0 !important; padding: 0 !important; background-color: #e2e8f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        table { border-collapse: collapse; border-spacing: 0; }
        img { border: 0; display: block; max-width: 100%; height: auto; }
        a { color: #059669; text-decoration: none; }

        @media only screen and (max-width: 680px) {
            .email-wrap { width: 100% !important; border-radius: 0 !important; }
            .pad { padding: 20px 15px !important; }
            .h1 { font-size: 20px !important; }
            .h2 { font-size: 16px !important; }
            .sm { font-size: 13px !important; }
            .col-half { width: 100% !important; display: block !important; padding: 0 0 12px 0 !important; }
            .col-third { width: 100% !important; display: block !important; padding: 0 0 12px 0 !important; }
        }
        @media only screen and (max-width: 480px) {
            .h1 { font-size: 18px !important; }
            .pad { padding: 15px 10px !important; }
        }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════
     WRAPPER EXTERIOR
════════════════════════════════════════════════════ --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#e2e8f0">
<tr><td align="center" style="padding: 30px 10px;">

    {{-- CONTENEDOR PRINCIPAL --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" class="email-wrap"
           style="max-width:680px; background-color:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,0.12);">

        {{-- ── BARRA SUPERIOR ── --}}
        <tr>
            <td bgcolor="#059669" style="height:6px; background:linear-gradient(90deg,#064e3b,#059669,#34d399); font-size:0; line-height:0;">&nbsp;</td>
        </tr>

        {{-- ── HEADER: LOGOS DE EMPRESAS ── --}}
        @if(!empty($logos_empresas))
        @php
            $logos = is_array($logos_empresas) ? $logos_empresas : json_decode($logos_empresas, true);
            $logos = array_values(array_filter((array) $logos, fn($l) => !empty($l['url'])));
        @endphp
        @if(count($logos) > 0)
        <tr>
            <td bgcolor="#065f46" align="center"
                style="background:linear-gradient(135deg,#064e3b 0%,#065f46 50%,#047857 100%); padding:30px 20px 25px;">

                {{-- Logos --}}
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                    <tr>
                        @foreach($logos as $logo)
                        @php
                            $lUrl = $logo['url'] ?? '';
                            if ($lUrl && !str_starts_with($lUrl, 'http')) {
                                $lUrl = asset('storage/' . ltrim(str_replace('storage/', '', $lUrl), '/'));
                            }
                        @endphp
                        @if($lUrl)
                        <td align="center" valign="middle" style="padding:0 10px;">
                            <img src="{{ $lUrl }}" alt="{{ $logo['nombre'] ?? 'Logo' }}"
                                 width="95" height="68"
                                 style="width:95px; height:68px; object-fit:contain; display:block;">
                        </td>
                        @endif
                        @endforeach
                    </tr>
                </table>

                {{-- Nombre empresa --}}
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td align="center" style="padding-top:18px;">
                            <p style="margin:0; font-size:21px; font-weight:800; color:#ffffff; font-family:'Segoe UI',Tahoma,sans-serif; letter-spacing:0.3px;" class="h1">
                                SetasPlast S.A.S BIC
                            </p>
                            <p style="margin:5px 0 0; font-size:13px; color:rgba(255,255,255,0.82); font-family:'Segoe UI',Tahoma,sans-serif;">
                                Grupo Empresarial Global Business JS Group
                            </p>
                            <p style="margin:5px 0 0; font-size:12px; color:rgba(255,255,255,0.65); font-style:italic; font-family:'Segoe UI',Tahoma,sans-serif;">
                                Innovación y Sostenibilidad
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top:14px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td bgcolor="#ffffff" width="70"
                                        style="width:70px; height:3px; background:rgba(255,255,255,0.5); border-radius:2px; font-size:0; line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
        @endif
        @endif

        {{-- ── MASCOTA / SALUDO ── --}}
        @if(!empty($saludo) || !empty($imagen_mascota))
        <tr>
            <td bgcolor="#f0fdf4" style="background-color:#f0fdf4; padding:35px 40px 20px;" class="pad">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td align="center" bgcolor="#ffffff"
                            style="background-color:#ffffff; border:2px solid #d1fae5; border-radius:14px; padding:25px 28px;">

                            @if(!empty($imagen_mascota))
                            @php
                                $mascotaUrl = $imagen_mascota;
                                if (!str_starts_with($mascotaUrl, 'http')) {
                                    $mascotaUrl = asset('storage/' . ltrim(str_replace('storage/', '', $mascotaUrl), '/'));
                                }
                            @endphp
                            <img src="{{ $mascotaUrl }}" alt="GAIA"
                                 width="110" height="110"
                                 style="width:110px; height:110px; object-fit:contain; display:block; margin:0 auto {{ !empty($saludo) ? '12px' : '0' }};">
                            @endif

                            @if(!empty($saludo))
                            <p style="margin:0; font-size:18px; font-weight:700; color:#065f46; font-family:'Segoe UI',Tahoma,sans-serif; line-height:1.5;" class="h1">
                                ✨ {{ $saludo }}
                            </p>
                            @endif

                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        {{-- ── BANNER: IMAGEN PRINCIPAL ── --}}
        @if(!empty($imagen_principal))
        @php
            $bannerUrl = $imagen_principal;
            if (!str_starts_with($bannerUrl, 'http')) {
                $bannerUrl = asset('storage/' . ltrim(str_replace('storage/', '', $bannerUrl), '/'));
            }
        @endphp
        <tr>
            <td style="padding:0; font-size:0; line-height:0;">
                <img src="{{ $bannerUrl }}" alt="{{ $titulo ?? 'Banner' }}" width="680"
                     style="width:100%; max-width:680px; height:auto; display:block;">
            </td>
        </tr>
        @endif

        {{-- ── CONTENIDO HTML ── --}}
        @if(!empty($contenido_html))
        <tr>
            <td style="padding:40px;" class="pad">
                <div style="font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; font-size:16px; line-height:1.7; color:#374151;">
                    {!! $contenido_html !!}
                </div>
            </td>
        </tr>
        @endif

        {{-- ── GALERÍA DE IMÁGENES ── --}}
        @if(!empty($imagenes))
        @php
            $imgs = is_array($imagenes) ? $imagenes : json_decode($imagenes, true);
            $imgs = array_values(array_filter((array) $imgs, fn($i) => !empty($i['url'])));
            $imgChunks = array_chunk($imgs, 2);
        @endphp
        @if(count($imgs) > 0)
        <tr>
            <td bgcolor="#f9fafb" style="background-color:#f9fafb; padding:40px;" class="pad">
                @foreach($imgChunks as $chunkIdx => $imgRow)
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                       @if($chunkIdx > 0) style="margin-top:14px;" @endif>
                    <tr>
                        @if(count($imgRow) === 2)
                            @foreach($imgRow as $iIdx => $imagen)
                            @php
                                $iUrl = $imagen['url'];
                                if (!str_starts_with($iUrl, 'http')) {
                                    $iUrl = asset('storage/' . ltrim(str_replace('storage/', '', $iUrl), '/'));
                                }
                            @endphp
                            <td width="50%" valign="top" class="col-half"
                                style="width:50%; {{ $iIdx === 0 ? 'padding-right:10px;' : 'padding-left:10px;' }}">
                                <img src="{{ $iUrl }}" alt="{{ $imagen['titulo'] ?? 'Imagen' }}"
                                     style="width:100%; height:auto; display:block; border-radius:12px; border:1px solid #e5e7eb;">
                            </td>
                            @endforeach
                        @else
                            @php
                                $iUrl = $imgRow[0]['url'];
                                if (!str_starts_with($iUrl, 'http')) {
                                    $iUrl = asset('storage/' . ltrim(str_replace('storage/', '', $iUrl), '/'));
                                }
                            @endphp
                            <td width="100%" style="width:100%;">
                                <img src="{{ $iUrl }}" alt="{{ $imgRow[0]['titulo'] ?? 'Imagen' }}"
                                     style="width:100%; height:auto; display:block; border-radius:12px; border:1px solid #e5e7eb;">
                            </td>
                        @endif
                    </tr>
                </table>
                @endforeach
            </td>
        </tr>
        @endif
        @endif

        {{-- ── VIDEO ── --}}
        @if(!empty($video_url))
        @php
            $thumb = null;
            if (str_contains($video_url, 'youtube.com') || str_contains($video_url, 'youtu.be')) {
                if (preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $m)) {
                    $thumb = "https://img.youtube.com/vi/{$m[1]}/maxresdefault.jpg";
                }
            }
            if (!$thumb) {
                $thumb = asset('storage/plantillas/videos/thumbnail_play.png');
            }
        @endphp
        <tr>
            <td bgcolor="#1f2937" align="center"
                style="background:linear-gradient(135deg,#1f2937 0%,#111827 100%); padding:45px 40px;" class="pad">
                <p style="margin:0 0 22px; font-size:22px; font-weight:800; color:#ffffff; font-family:'Segoe UI',Tahoma,sans-serif; text-align:center;" class="h1">
                    🎬 Contenido Audiovisual
                </p>
                <a href="{{ $video_url }}" target="_blank" style="display:block; text-decoration:none; line-height:0;">
                    <img src="{{ $thumb }}" alt="Ver video" width="560"
                         style="width:100%; max-width:560px; height:auto; display:block; border-radius:14px; box-shadow:0 15px 35px rgba(0,0,0,0.4); margin:0 auto;">
                </a>
                <p style="margin:16px 0 0; font-size:13px; color:#9ca3af; font-family:'Segoe UI',Tahoma,sans-serif; text-align:center;" class="sm">
                    🎥 Haz clic en la imagen para reproducir el video
                </p>
            </td>
        </tr>
        @endif

        {{-- ── CERTIFICACIONES ── --}}
        @if(!empty($certificaciones))
        @php
            $certs = is_array($certificaciones) ? $certificaciones : json_decode($certificaciones, true);
            $certs = array_values(array_filter((array) $certs, fn($c) => !empty($c['logo']) || !empty($c['nombre'])));
            $certChunks = array_chunk($certs, 3);
        @endphp
        @if(count($certs) > 0)
        <tr>
            <td bgcolor="#f8fafc" style="background-color:#f8fafc; padding:45px 40px;" class="pad">
                <p style="margin:0 0 30px; font-size:24px; font-weight:800; color:#1e293b; text-align:center; font-family:'Segoe UI',Tahoma,sans-serif; letter-spacing:-0.3px;" class="h1">
                    Certificaciones
                </p>
                @foreach($certChunks as $cIdx => $certRow)
                @php
                    $nCols = count($certRow);
                    $colPct = $nCols === 3 ? '33' : ($nCols === 2 ? '50' : '100');
                @endphp
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                       @if($cIdx > 0) style="margin-top:14px;" @endif>
                    <tr>
                        @foreach($certRow as $cItemIdx => $cert)
                        @php
                            $cImg = null;
                            if (!empty($cert['logo'])) {
                                $cImg = $cert['logo'];
                                if (!filter_var($cImg, FILTER_VALIDATE_URL)) {
                                    $clean = ltrim(str_replace(['storage/storage/', '//'], ['storage/', '/'], $cImg), '/');
                                    $cImg = asset($clean);
                                }
                            }
                        @endphp
                        <td width="{{ $colPct }}%" valign="top" class="{{ $nCols === 3 ? 'col-third' : 'col-half' }}"
                            style="width:{{ $colPct }}%; {{ $cItemIdx > 0 ? 'padding-left:12px;' : '' }}">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                {{-- Barra de acento superior --}}
                                <tr>
                                    <td bgcolor="#059669"
                                        style="height:4px; background:linear-gradient(90deg,#059669,#10b981,#34d399); border-radius:4px 4px 0 0; font-size:0; line-height:0;">&nbsp;</td>
                                </tr>
                                {{-- Cuerpo de la card --}}
                                <tr>
                                    <td align="center" bgcolor="#ffffff"
                                        style="background-color:#ffffff; border:2px solid #e2e8f0; border-top:none; border-radius:0 0 14px 14px; padding:18px 12px;">
                                        @if($cImg)
                                            <img src="{{ $cImg }}" alt="{{ $cert['nombre'] ?? 'Certificación' }}"
                                                 width="90" height="90"
                                                 style="width:90px; height:90px; object-fit:contain; display:block; margin:0 auto 10px;">
                                        @else
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                                <tr>
                                                    <td width="80" height="80" bgcolor="#f3f4f6"
                                                        style="width:80px; height:80px; background-color:#f3f4f6; border-radius:8px; text-align:center; vertical-align:middle; font-size:26px; line-height:80px; margin-bottom:10px;">
                                                        🏆
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif
                                        @if(!empty($cert['nombre']))
                                        <p style="margin:0 0 8px; font-size:13px; font-weight:700; color:#065f46; font-family:'Segoe UI',Tahoma,sans-serif; text-align:center; line-height:1.3;" class="sm">
                                            {{ $cert['nombre'] }}
                                        </p>
                                        @endif
                                        {{-- Badge "Certificado" --}}
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                            <tr>
                                                <td bgcolor="#d1fae5"
                                                    style="background-color:#d1fae5; border-radius:20px; padding:3px 10px;">
                                                    <p style="margin:0; font-size:10px; font-weight:600; color:#065f46; font-family:'Segoe UI',Tahoma,sans-serif; letter-spacing:0.3px; white-space:nowrap;">
                                                        ✓ Certificado
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        @endforeach
                    </tr>
                </table>
                @endforeach
            </td>
        </tr>
        @endif
        @endif

        {{-- ── DESCARGAS ── --}}
        @if(!empty($descargas))
        @php $files = is_array($descargas) ? $descargas : json_decode($descargas, true); @endphp
        <tr>
            <td bgcolor="#065f46" align="center"
                style="background:linear-gradient(135deg,#064e3b 0%,#065f46 100%); padding:45px 40px;" class="pad">
                <p style="margin:0 0 22px; font-size:22px; font-weight:800; color:#ecfdf5; text-align:center; font-family:'Segoe UI',Tahoma,sans-serif;" class="h1">
                    Recursos Descargables
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                    <tr>
                        @foreach($files as $file)
                        <td align="center" style="padding:5px 7px;">
                            <a href="{{ $file['link'] }}" target="_blank"
                               style="display:block; background:linear-gradient(135deg,#047857 0%,#059669 100%); color:#ffffff; font-weight:700; border-radius:10px; padding:12px 22px; text-decoration:none; font-size:14px; font-family:'Segoe UI',Tahoma,sans-serif; border:2px solid rgba(255,255,255,0.15); white-space:nowrap; text-align:center; box-shadow:0 4px 14px rgba(0,0,0,0.2);">
                                {{ $file['nombre'] }}
                            </a>
                        </td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        {{-- ── REDES SOCIALES ── --}}
        @if(!empty($redes_sociales))
        @php $redes = is_array($redes_sociales) ? $redes_sociales : json_decode($redes_sociales, true); @endphp
        <tr>
            <td bgcolor="#111827" align="center"
                style="background:linear-gradient(135deg,#1f2937 0%,#111827 100%); padding:45px 40px;" class="pad">
                <p style="margin:0 0 22px; font-size:22px; font-weight:800; color:#d1fae5; text-align:center; font-family:'Segoe UI',Tahoma,sans-serif;" class="h1">
                    Conéctate con Nosotros
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                    <tr>
                        @foreach($redes as $red)
                        <td align="center" style="padding:5px 6px;">
                            <a href="{{ $red['url'] }}" target="_blank"
                               style="display:block; background:linear-gradient(135deg,#059669 0%,#10b981 100%); color:#ffffff; font-weight:600; border-radius:24px; padding:11px 20px; font-size:14px; text-decoration:none; font-family:'Segoe UI',Tahoma,sans-serif; border:2px solid rgba(255,255,255,0.1); white-space:nowrap; text-align:center;">
                                {{ $red['nombre'] }}
                            </a>
                        </td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        {{-- ── FOOTER ── --}}
        <tr>
            <td bgcolor="#374151" align="center"
                style="background:linear-gradient(135deg,#1f2937 0%,#374151 50%,#4b5563 100%); padding:35px 25px;" class="pad">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    {{-- Barra de acento --}}
                    <tr>
                        <td bgcolor="#059669"
                            style="height:4px; background:linear-gradient(90deg,#059669,#10b981,#34d399,#6ee7b7); border-radius:2px; font-size:0; line-height:0; margin-bottom:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top:22px;">
                            <p style="margin:0 0 6px; font-size:20px; font-weight:800; color:#ffffff; font-family:'Segoe UI',Tahoma,sans-serif; letter-spacing:-0.3px;" class="h1">
                                SetasPlast S.A.S BIC
                            </p>
                            <p style="margin:0 0 20px; font-size:13px; color:#d1d5db; font-family:'Segoe UI',Tahoma,sans-serif;" class="sm">
                                Comprometidos con la Innovación y Sostenibilidad
                            </p>
                            {{-- Links --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="padding:0 6px;">
                                        <a href="https://setasplast.com.co" target="_blank"
                                           style="display:block; color:#6ee7b7; font-weight:700; text-decoration:none; font-size:13px; font-family:'Segoe UI',Tahoma,sans-serif; background:rgba(110,231,183,0.1); border:1px solid rgba(110,231,183,0.25); padding:7px 13px; border-radius:8px; white-space:nowrap;">
                                            setasplast.com.co
                                        </a>
                                    </td>
                                    <td style="padding:0 6px;">
                                        <a href="mailto:comercialsetasplast@gmail.com"
                                           style="display:block; color:#6ee7b7; font-weight:700; text-decoration:none; font-size:13px; font-family:'Segoe UI',Tahoma,sans-serif; background:rgba(110,231,183,0.1); border:1px solid rgba(110,231,183,0.25); padding:7px 13px; border-radius:8px; white-space:nowrap;">
                                            Contacto
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    {{-- Separador --}}
                    <tr>
                        <td style="padding-top:18px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td bgcolor="#6ee7b7"
                                        style="height:1px; background:linear-gradient(90deg,transparent,rgba(110,231,183,0.45),transparent); font-size:0; line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top:14px;">
                            <p style="margin:0; font-size:11px; color:#9ca3af; font-family:'Segoe UI',Tahoma,sans-serif;" class="sm">
                                &copy; {{ date('Y') }} SetasPlast S.A.S BIC &mdash; Todos los derechos reservados
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>
    {{-- Fin contenedor principal --}}

</td></tr>
</table>
{{-- Fin wrapper exterior --}}

</body>
</html>
