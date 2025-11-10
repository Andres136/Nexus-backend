<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\PlantillaRequest;
use App\Models\comunicaciones\Plantilla;
use Illuminate\Http\Request;

class PlantillaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(PlantillaRequest $request)
{
    $validated = $request->validated();

    // 1) Subida de imágenes
    $imagenesPaths = [];
    if ($request->hasFile('imagenes')) {
        foreach ($request->file('imagenes') as $img) {
            $imagenesPaths[] = [
                'url'    => $img->store('plantillas/imagenes', 'public'),
                'titulo' => pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME),
            ];
        }
    }

    // 2) Subida de logos de empresas
    $logosPaths = [];
    if ($request->hasFile('logos_empresas')) {
        foreach ($request->file('logos_empresas') as $logo) {
            $logosPaths[] = [
                'url'    => $logo->store('plantillas/logos', 'public'),
                'nombre' => pathinfo($logo->getClientOriginalName(), PATHINFO_FILENAME),
            ];
        }
    }

    // 3) Subida de certificaciones
    $certsPaths = [];
    if ($request->has('certificaciones')) {
        foreach ($request->certificaciones as $i => $cert) {
            $nombre = $cert['nombre'] ?? null;
            $urlCert = $cert['url_cert'] ?? null;
            $logoPath = null;

            // Caso 1: archivo subido
            if ($request->hasFile("certificaciones.$i.logo")) {
                $file = $request->file("certificaciones.$i.logo");
                $logoPath = 'storage/' . $file->store('plantillas/certificaciones', 'public');
            }
            // Caso 2: URL externa
            elseif (!empty($cert['logo']) && is_string($cert['logo'])) {
                $logoPath = $cert['logo'];
            }

            $certsPaths[] = [
                'nombre' => $nombre,
                'logo'   => $logoPath,
                'url_cert' => $urlCert,
            ];
        }
    }

    // 4) Subida de video (si aplica)
if ($request->hasFile('video_file')) {
    $path = $request->file('video_file')->store('videos', 'public');
    $videoUrl = asset('storage/' . $path); // ✅ genera una URL pública accesible
}


    // 5) Crear la plantilla
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
    ]);

    return response()->json([
        'message' => ' Plantilla creada exitosamente',
        'data'    => $plantilla,
    ], 201);
}


    /**
     * Display the specified resource.
     */
public function show($id)
{
    $plantilla = Plantilla::findOrFail($id);

    // ✅ ASEGURAR QUE TODOS LOS CAMPOS SEAN ARRAYS VÁLIDOS
    $imagenes = is_string($plantilla->imagenes)
        ? json_decode($plantilla->imagenes, true)
        : (is_array($plantilla->imagenes) ? $plantilla->imagenes : []);

    $certificaciones = is_string($plantilla->certificaciones)
        ? json_decode($plantilla->certificaciones, true)
        : (is_array($plantilla->certificaciones) ? $plantilla->certificaciones : []);

    $redes_sociales = is_string($plantilla->redes_sociales)
        ? json_decode($plantilla->redes_sociales, true)
        : (is_array($plantilla->redes_sociales) ? $plantilla->redes_sociales : []);

    // ✅ AGREGAR: Logos de empresas
    $logos_empresas = is_string($plantilla->logos_empresas)
        ? json_decode($plantilla->logos_empresas, true)
        : (is_array($plantilla->logos_empresas) ? $plantilla->logos_empresas : []);

    // ✅ AGREGAR: Descargas
    $descargas = is_string($plantilla->descargas)
        ? json_decode($plantilla->descargas, true)
        : (is_array($plantilla->descargas) ? $plantilla->descargas : []);

    return view('emails.marketing', [
        'titulo' => $plantilla->nombre,
        'contenido_html' => $plantilla->contenido_html,
        
        // ✅ AGREGAR: Video URL que faltaba
        'video_url' => $plantilla->video_url,
        
        'imagenes' => $imagenes,
        'certificaciones' => $certificaciones,
        'redes_sociales' => $redes_sociales,
        
        // ✅ AGREGAR: Variables faltantes
        'logos_empresas' => $logos_empresas,
        'descargas' => $descargas,
        
        'cta_url' => 'https://setasplast.com.co/catalogo',
        'cta_text' => 'Descargar Catálogo',
    ]);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
