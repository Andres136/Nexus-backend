<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; color: #212529; margin:0; padding:0; }
        .wrapper { width:100%; padding:30px 0; }
        .content { max-width:600px; margin:auto; background:white; border-radius:10px; padding:30px; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
        .header img { max-height:80px; margin-bottom:20px; }
        .footer { text-align:center; font-size:12px; color:#6c757d; margin-top:20px; }
        
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            {{--             <div class="header" style="text-align:center;">
                         
                      
                            <img src="{{ asset('images/SETAS.png') }}" alt="Setasplast">


                        </div> --}}

            @yield('content')
        </div>
        <div class="footer">
            Este correo fue generado automáticamente por el Sistema de Gestión – {{ config('app.name') }}
        </div>
    </div>
</body>
</html>
