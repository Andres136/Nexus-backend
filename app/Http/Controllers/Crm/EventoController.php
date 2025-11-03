<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\EventoRequest;
use App\Models\Crm\Evento;
use App\Models\Crm\Qr;
use BaconQrCode\Encoder\QrCode;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as FacadesQrCode;

class EventoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EventoRequest $request)
    {
        // 
        $create = Evento::create([
            'name' => $request->name,
            'company' => $request->company,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'message' => 'Registro creado exitosamente',
            'data' => $create
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
public function crearQr(Request $request)
{
    $request->validate(['url' => 'required|url']);

    $url = $request->input('url');
    $qrImage = FacadesQrCode::format('svg')->size(300)->margin(2)->generate($url);

    $filename = 'qr_' . md5($url . time()) . '.svg';
    $path = "qrs/{$filename}";

    Storage::disk('public')->put($path, $qrImage);
    $publicUrl = asset("storage/{$path}");

    // 💾 Guardar en base de datos
    $qr = Qr::create([
        'url' => $url,
        'path' => $path,
        'public_url' => $publicUrl,
    ]);

    // 🔁 También retornamos el QR embebido (sin necesidad de fetch)
    $base64 = 'data:image/svg+xml;base64,' . base64_encode($qrImage);

    return response()->json([
        'message' => 'QR generado y guardado correctamente',
        'data' => $qr,
        'qr_base64' => $base64
    ]);
}


}
