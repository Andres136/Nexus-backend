<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\PlantillaRequest;
use App\Http\Requests\comunicaciones\PlantillaUpdateRequest;
use App\Models\comunicaciones\Plantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PlantillaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    
    $plantillas = Plantilla::select('id', 'nombre', 'tipo', 'created_at')
        ->latest()
        ->get();

    return response()->json($plantillas);
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(PlantillaRequest $request)
{
    $validated = $request->validated();

    // 1️⃣ Subida de imágenes
    $imagenesPaths = [];
    if ($request->hasFile('imagenes')) {
        foreach ($request->file('imagenes') as $img) {
            $imagenesPaths[] = [
                'url'    => 'storage/' . $img->store('plantillas/imagenes', 'public'),
                'titulo' => pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME),
            ];
        }
    }

    // 2️⃣ Subida de logos empresariales
    $logosPaths = [];
    if ($request->hasFile('logos_empresas')) {
        foreach ($request->file('logos_empresas') as $logo) {
            $logosPaths[] = [
                'url'    => 'storage/' . $logo->store('plantillas/logos', 'public'),
                'nombre' => pathinfo($logo->getClientOriginalName(), PATHINFO_FILENAME),
            ];
        }
    }

    // 3️⃣ Subida de certificaciones
    $certsPaths = [];
    if ($request->has('certificaciones')) {
        foreach ($request->certificaciones as $i => $cert) {
            $nombre = $cert['nombre'] ?? null;
            $urlCert = $cert['url_cert'] ?? null;
            $logoPath = null;

            if ($request->hasFile("certificaciones.$i.logo")) {
                $file = $request->file("certificaciones.$i.logo");
                $logoPath = 'storage/' . $file->store('plantillas/certificaciones', 'public');
            } elseif (!empty($cert['logo']) && is_string($cert['logo'])) {
                $logoPath = $cert['logo'];
            }

            $certsPaths[] = [
                'nombre' => $nombre,
                'logo'   => $logoPath,
                'url_cert' => $urlCert,
            ];
        }
    }

    // 4️⃣ Video (solo enlace externo: YouTube, Vimeo, etc.)
    $videoUrl = null;
    if ($request->filled('video_url')) {
        $videoUrl = $request->input('video_url');
    }

    if ($request->hasFile('imagen_principal')) {
    $imagenPath = 'storage/' . $request->file('imagen_principal')->store('plantillas/portadas', 'public');
    $data['imagen_principal'] = $imagenPath;
}


    // 5️⃣ Crear plantilla
    $plantilla = Plantilla::create([
        'nombre'           => $validated['nombre'],
        'tipo'             => $validated['tipo'] ?? null,
        'contenido_html'   => $validated['contenido_html'] ?? null,
        'video_url'        => $videoUrl,
        'imagenes'         => $imagenesPaths,
        'logos_empresas'   => $logosPaths,
        'certificaciones'  => $certsPaths,
        'redes_sociales'   => $request->input('redes_sociales', []),
        'descargas'        => $request->input('descargas', []),
        'publicada'        => (bool) ($validated['publicada'] ?? false),
        'imagen_principal' => $imagenPath ?? null,
    ]);

    return response()->json([
        'message' => 'Plantilla creada exitosamente',
        'data'    => $plantilla,
    ], 201);
}



    /**
     * Display the specified resource.
     */
public function show($id)
{
    $plantilla = Plantilla::findOrFail($id);
   
    $data = $this->preparePlantillaData($plantilla);

    return view('emails.marketing', $data);
}

    /**
     * Update the specified resource in storage.
     */
public function edit($id)
{
    try {
        $plantilla = Plantilla::findOrFail($id);
        
        // ✅ Función helper para decodificar JSON
        $jsonDecode = function($field) {
            if (is_string($field)) {
                $decoded = json_decode($field, true);
                return is_array($decoded) ? $decoded : [];
            }
            return is_array($field) ? $field : [];
        };

        // ✅ Preparar datos para el formulario del frontend
        $response = [
            'id' => $plantilla->id,
            'nombre' => $plantilla->nombre ?? '',
            'tipo' => $plantilla->tipo ?? '',
            'contenido_html' => $plantilla->contenido_html ?? '',
            'video_url' => $plantilla->video_url ?? '',
            'publicada' => (bool) $plantilla->publicada,
            
            // ✅ Arrays para el frontend (ya decodificados)
            'imagenes' => $jsonDecode($plantilla->imagenes),
            'logos_empresas' => $jsonDecode($plantilla->logos_empresas),
            'certificaciones' => $jsonDecode($plantilla->certificaciones),
            'redes_sociales' => $jsonDecode($plantilla->redes_sociales),
            'descargas' => $jsonDecode($plantilla->descargas),
            
            // ✅ Metadatos
            'created_at' => $plantilla->created_at,
            'updated_at' => $plantilla->updated_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => 'Plantilla cargada correctamente'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar la plantilla',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ MÉTODO UPDATE MEJORADO
 */
private function jsonDecodeSafe($value): array
{
    if (is_array($value)) return $value;
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    return [];
}

private function storageRelativePath(?string $url): ?string
{
    if (!$url) return null;

    // esperas "storage/xxx/yyy.ext"
    if (str_starts_with($url, 'storage/')) {
        return substr($url, strlen('storage/')); // => "xxx/yyy.ext" (para disk public)
    }

    return null;
}
public function update(PlantillaUpdateRequest $request, $id)
{
    try {
        $plantilla = Plantilla::findOrFail($id);

        // -------------------------
        // 1) Estado anterior (BD)
        // -------------------------
        $oldImages = $this->jsonDecodeSafe($plantilla->imagenes);
        $oldLogos  = $this->jsonDecodeSafe($plantilla->logos_empresas);

        // -------------------------
        // 2) Nuevo estado = keep + nuevos uploads
        // -------------------------
        $keepImages = $this->jsonDecodeSafe($request->input('imagenes_keep'));
        $keepLogos  = $this->jsonDecodeSafe($request->input('logos_keep'));

        // Normaliza por si vienen strings o incompletos
        $newImages = array_values(array_filter($keepImages, fn($x) => is_array($x) && !empty($x['url'])));
        $newLogos  = array_values(array_filter($keepLogos, fn($x) => is_array($x) && !empty($x['url'])));

        // Agregar imágenes nuevas
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $img) {
                $path = $img->store('plantillas/imagenes', 'public');
                $newImages[] = [
                    'url' => 'storage/' . $path,
                    'titulo' => pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME),
                ];
            }
        }

        // Agregar logos nuevos
        if ($request->hasFile('logos_empresas')) {
            foreach ($request->file('logos_empresas') as $logo) {
                $path = $logo->store('plantillas/logos', 'public');
                $newLogos[] = [
                    'url' => 'storage/' . $path,
                    'nombre' => pathinfo($logo->getClientOriginalName(), PATHINFO_FILENAME),
                ];
            }
        }

        // -------------------------
        // 3) Eliminar del disco lo que ya no está
        // -------------------------
        $oldImageUrls = array_map(fn($x) => $x['url'] ?? null, $oldImages);
        $newImageUrls = array_map(fn($x) => $x['url'] ?? null, $newImages);

        $toDeleteImages = array_diff(array_filter($oldImageUrls), array_filter($newImageUrls));
        foreach ($toDeleteImages as $url) {
            $rel = $this->storageRelativePath($url);
            if ($rel) Storage::disk('public')->delete($rel);
        }

        $oldLogoUrls = array_map(fn($x) => $x['url'] ?? null, $oldLogos);
        $newLogoUrls = array_map(fn($x) => $x['url'] ?? null, $newLogos);

        $toDeleteLogos = array_diff(array_filter($oldLogoUrls), array_filter($newLogoUrls));
        foreach ($toDeleteLogos as $url) {
            $rel = $this->storageRelativePath($url);
            if ($rel) Storage::disk('public')->delete($rel);
        }

        // -------------------------
        // 4) Imagen principal (si reemplazas, borra la anterior)
        // -------------------------
        $imagenPath = $plantilla->imagen_principal;
        if ($request->hasFile('imagen_principal')) {
            // borra anterior si existía
            $oldMain = $this->storageRelativePath($plantilla->imagen_principal);
            if ($oldMain) Storage::disk('public')->delete($oldMain);

            $imagenPath = 'storage/' . $request->file('imagen_principal')->store('plantillas/portadas', 'public');
        }

        // -------------------------
        // 5) Update
        // -------------------------
        $plantilla->update([
            'nombre' => $request->input('nombre'),
            'tipo' => $request->input('tipo'),
            'contenido_html' => $request->input('contenido_html'),
            'video_url' => $request->filled('video_url') ? $request->input('video_url') : $plantilla->video_url,
            'imagenes' => $newImages,
            'logos_empresas' => $newLogos,
            'redes_sociales' => $request->input('redes_sociales', $plantilla->redes_sociales),
            'descargas' => $request->input('descargas', $plantilla->descargas),
            'publicada' => (bool) $request->input('publicada', $plantilla->publicada),
            'imagen_principal' => $imagenPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla actualizada correctamente',
            'data' => $plantilla->fresh()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar la plantilla',
            'error' => $e->getMessage()
        ], 500);
    }
}


 /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $plantilla = Plantilla::findOrFail($id);
        $plantilla->delete();
        return response()->json([
            'message' => 'Plantilla eliminada correctamente'
        ]);
    }

    /**
     * ✅ NUEVA FUNCIÓN: Vista previa de la plantilla como JSON
     */
    public function preview($id)
    {
        $plantilla = Plantilla::findOrFail($id);
        $data = $this->preparePlantillaData($plantilla);
        
        return response()->json([
            'plantilla' => $plantilla,
            'data' => $data
        ]);
    }

    /**
     * ✅ FUNCIÓN DE ENVÍO MEJORADA
     */
public function enviar(Request $request, $id)
{
    // Validar datos
    $request->validate([
        'destinatarios' => 'required|array|min:1',
        'asunto' => 'required|string|max:255',
    ]);


    try {
       
        $plantilla = Plantilla::findOrFail($id);
        
        $dataBase = $this->preparePlantillaData($plantilla);

        $destinatarios = $request->input('destinatarios');
        $asunto = $request->input('asunto');

        $enviados = [];
        $errores = [];

        foreach ($destinatarios as $key => $value) {
            // ✅ Soporta tanto lista simple ["a@b.com"] como {"a@b.com": "Nombre"}
            if (is_numeric($key)) {
                $email = $value;
                $nombre = null;
            } else {
                $email = $key;
                $nombre = $value;
            }

            // Personaliza saludo según corresponda
            $data = $dataBase;
            $data['saludo'] = $nombre
                ? "Hola {$nombre},  soy GAIA esperamos que te encuentres muy bien."
                : "Hola, soy GAIA esperamos que te encuentres muy bien.";

            try {
                Mail::send('emails.marketing', $data, function ($message) use ($email, $asunto) {
                    $message->to($email)
                            ->subject($asunto)
                            ->from(config('mail.from.address'), config('mail.from.name'));
                });

                $enviados[] = [
                    'email' => $email,
                    'nombre' => $nombre
                ];

            } catch (\Exception $e) {
                $errores[] = [
                    'email' => $email,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Correos enviados exitosamente',
            'total' => count($destinatarios),
            'enviados' => $enviados,
            'errores' => $errores,
            'plantilla_usada' => $plantilla->nombre
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al enviar correos',
            'error' => $e->getMessage()
        ], 500);
    }
}



    /**
     * ✅ FUNCIÓN UNIFICADA PARA PREPARAR DATOS
     */
    private function preparePlantillaData(Plantilla $plantilla): array
    {
        $jsonDecode = fn($field) =>
            is_string($field) ? json_decode($field, true) : (is_array($field) ? $field : []);

        return [
            'imagen_principal' => $plantilla->imagen_principal,
            'titulo' => $plantilla->nombre,
            'contenido_html' => $plantilla->contenido_html,
            'video_url' => $plantilla->video_url,
            'imagenes' => $jsonDecode($plantilla->imagenes),
            'certificaciones' => $jsonDecode($plantilla->certificaciones),
            'redes_sociales' => $jsonDecode($plantilla->redes_sociales),
            'logos_empresas' => $jsonDecode($plantilla->logos_empresas),
            'descargas' => $jsonDecode($plantilla->descargas),
            'cta_url' => 'https://setasplast.com.co/catalogo',
            'cta_text' => 'Descargar Catálogo',
        ];
    }

    /**
     * ✅ NUEVA FUNCIÓN: Envío masivo desde el frontend
     */
    public function enviarMasivo(Request $request)
    {
        $request->validate([
            'plantilla_id' => 'required|exists:plantillas,id',
            'destinatarios' => 'required|array|min:1',
            'destinatarios.*' => 'required|email',
            'asunto' => 'required|string|max:255',
            'programado' => 'nullable|date|after:now'
        ]);

        $plantilla = Plantilla::findOrFail($request->plantilla_id);
        
        if ($request->filled('programado')) {
            // Para envío programado (necesitarías implementar jobs/queues)
            return response()->json([
                'message' => 'Correo programado para: ' . $request->programado,
                'plantilla' => $plantilla->nombre
            ]);
        }

        // Envío inmediato
        return $this->enviar($request, $request->plantilla_id);
    }


    //Crear funcion que cree un qr por productos con una url determinada

 
}
