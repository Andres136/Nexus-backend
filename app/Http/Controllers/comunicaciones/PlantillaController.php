<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\PlantillaRequest;
use App\Models\comunicaciones\Plantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

public function update(Request $request, $id)
{
    try {
        $plantilla = Plantilla::findOrFail($id);

        // ✅ Validaciones básicas
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'nullable|string|max:100',

            'contenido_html' => 'nullable|string',
            'video_url' => 'nullable|url',
            'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logos_empresas.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'imagen_principal' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        //  Helper para decodificar JSON o arrays
        $jsonDecode = fn($field) =>
            is_string($field) ? json_decode($field, true) : (is_array($field) ? $field : []);

        // ✅ Mantener imágenes existentes
        $imagenesPaths = $jsonDecode($plantilla->imagenes);
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $img) {
                $imagenesPaths[] = [
                    'url' => 'storage/' . $img->store('plantillas/imagenes', 'public'),
                    'titulo' => pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME),
                ];
            }
        }

        // ✅ Mantener logos existentes
        $logosPaths = $jsonDecode($plantilla->logos_empresas);
        if ($request->hasFile('logos_empresas')) {
            foreach ($request->file('logos_empresas') as $logo) {
                $logosPaths[] = [
                    'url' => 'storage/' . $logo->store('plantillas/logos', 'public'),
                    'nombre' => pathinfo($logo->getClientOriginalName(), PATHINFO_FILENAME),
                ];
            }
        }

        // ✅ Mantener certificaciones existentes
        $certsPaths = $jsonDecode($plantilla->certificaciones);
        if ($request->has('certificaciones')) {
            foreach ($request->certificaciones as $i => $cert) {
                $nombre = $cert['nombre'] ?? null;
                $urlCert = $cert['url_cert'] ?? null;
                $logoPath = $cert['logo'] ?? null;

                // Si sube un nuevo logo, reemplaza el anterior
                if ($request->hasFile("certificaciones.$i.logo")) {
                    $file = $request->file("certificaciones.$i.logo");
                    $logoPath = 'storage/' . $file->store('plantillas/certificaciones', 'public');
                }

                $certsPaths[$i] = [
                    'nombre' => $nombre,
                    'logo' => $logoPath,
                    'url_cert' => $urlCert,
                ];
            }
        }

        // ✅ Actualizar imagen principal
        if ($request->hasFile('imagen_principal')) {
            $imagenPath = 'storage/' . $request->file('imagen_principal')->store('plantillas/portadas', 'public');
        }

        // ✅ Actualizar video
        $videoUrl = $request->filled('video_url') ? $request->input('video_url') : $plantilla->video_url;

        // ✅ Actualizar datos generales
        $plantilla->update([
            'nombre' => $request->input('nombre'),
            'tipo' => $request->input('tipo'),
            'contenido_html' => $request->input('contenido_html'),
            'video_url' => $videoUrl,
            'imagenes' => $imagenesPaths,
            'logos_empresas' => $logosPaths,
            'certificaciones' => $certsPaths,
            'redes_sociales' => $request->input('redes_sociales', $plantilla->redes_sociales),
            'descargas' => $request->input('descargas', $plantilla->descargas),
            'publicada' => (bool) $request->input('publicada', $plantilla->publicada),
            'imagen_principal' => $imagenPath ?? $plantilla->imagen_principal,
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




}
