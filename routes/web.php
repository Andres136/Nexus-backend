<?php

use App\Http\Controllers\comunicaciones\PlantillaController;
use App\Mail\PlantillaPreviewMail;
use App\Models\comunicaciones\Plantilla;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/plantillas/{id}', [PlantillaController::class, 'show']);

Route::get('/test-email', function () {
    $plantilla = Plantilla::latest()->first();

    if (!$plantilla) {
        return '⚠️ No hay plantillas en la base de datos';
    }

    Mail::to('elveral100@gmail.com')->send(new PlantillaPreviewMail($plantilla));

    return '✅ Correo de prueba enviado a tu bandeja de entrada';
});
Route::get('/test-plantilla', function () {
    $plantilla = App\Models\comunicaciones\Plantilla::find(20); // ID de ejemplo

    $data = [
       'saludo' => 'Hola, este es un ejemplo de vista previa local 👋',
        'imagen_principal' => $plantilla->imagen_principal,
        'contenido_html' => '<p>Estamos felices de compartir nuestros nuevos avances sostenibles 🌱</p>',
        'logos_empresas' => $plantilla->logos_empresas,
        'imagenes' => $plantilla->imagenes,
        'video_url' => $plantilla->video_url,
        'certificaciones' => $plantilla->certificaciones,
        'descargas' => $plantilla->descargas,
        'redes_sociales' => $plantilla->redes_sociales,
    ];

    return view('emails.marketing', $data);
});
