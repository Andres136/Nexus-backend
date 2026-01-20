<?php

use App\Http\Controllers\comunicaciones\PlantillaController;
use App\Http\Controllers\Crm\InventorieController;
use App\Http\Controllers\Traslados\TrasladosBodegaEmailController;
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
Route::get('/test-pdf', [App\Http\Controllers\Crm\ProductController::class, 'testPdf']);
// routes/web.php
Route::get('/scan/{code}', [InventorieController::class, 'show']);
Route::prefix('email/traslados')->group(function () {
    Route::get('{traslado}/aprobar', [TrasladosBodegaEmailController::class, 'aprobar'])
        ->name('email.traslados.aprobar');

    Route::get('{traslado}/rechazar', [TrasladosBodegaEmailController::class, 'rechazar'])
        ->name('email.traslados.rechazar');
});