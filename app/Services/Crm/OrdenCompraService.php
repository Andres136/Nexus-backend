<?php

namespace App\Services\Crm;

use App\Models\Crm\Orden_Compra;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrdenCompraService
{
  public function obtenerDocumentoPreview(Orden_Compra $orden)
{
       $rutaArchivo = $orden->cliente_documento;
    //dd($rutaArchivo);
        if (!$rutaArchivo) {
            abort(404, 'Documento no disponible');
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($rutaArchivo)) {
            abort(404, 'Archivo no encontrado');
        }

        $contenido = $disk->get($rutaArchivo);
        $mimeType = $disk->mimeType($rutaArchivo);

        return response($contenido, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
}

}
