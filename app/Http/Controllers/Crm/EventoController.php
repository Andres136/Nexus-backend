<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\BacordeQrRequest;
use App\Http\Requests\Crm\EventoRequest;
use App\Models\Crm\empresa;
use App\Models\Crm\Evento;
use App\Models\Crm\Inventario;
use App\Models\Crm\product;
use App\Models\Crm\Qr;
use App\Services\PdfEtiquetaService;
use BaconQrCode\Encoder\QrCode;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

//CREAR UNA FUNCION PARA CREAR UN QR POR PRODUCTO CON UNA URL VALIDAR CUANTO STOK TENGO  EN SEDE Y BODEGAS
public function crearQrMasivoConPdf(BacordeQrRequest $request)
{
    

    $empresa = empresa::findOrFail($request->empresa_id);

    $etiquetas = [];

    $logoBase64 = null;

if ($empresa->logo) {
    $logoPath = storage_path('app/public/' . $empresa->logo);

    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(
            file_get_contents($logoPath)
        );
    }
}

    foreach ($request->productos as $item) {

        $producto = product::findOrFail($item['producto_id']);

        // 🔹 URL pública con empresa (CLAVE)
        $url = url("/scan/producto/{$producto->code}");

        // 🔹 QR PNG (estable para PDF)
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size(300)
            ->margin(10)
            ->build();

        $qrPng = $result->getString();

        $etiquetas[] = [
            'nombre' => $producto->name,
            'codigo' => $producto->code,
            'qr' => base64_encode($qrPng),
            'empresa' => $empresa->nombre,
            'logo' => $logoBase64,
        ];
    }

    $pdf = PdfEtiquetaService::generarQrs($etiquetas);

    return response()->streamDownload(
        fn () => print($pdf),
        'etiquetas_qr_' . $empresa->nombre . '.pdf',
        ['Content-Type' => 'application/pdf']
    );
}

public function generarZplMasivo(Request $request)
{
    $request->validate([
        'empresa_id' => 'required|exists:empresas,id',
        'productos' => 'required|array|min:1',
        'productos.*.producto_id' => 'required|exists:products,id',
    ]);

    $empresa = empresa::findOrFail($request->empresa_id);

    $zpl = '';

    foreach ($request->productos as $item) {

        $producto = product::findOrFail($item['producto_id']);

        $url = url("/scan/empresa/{$empresa->id}/producto/{$producto->code}");

        $zpl .= <<<ZPL
^XA
^PW400
^LL600

^FO20,20
^A0N,30,30
^FD{$producto->name}^FS

^FO90,80
^BQN,2,6
^FDLA,{$url}^FS

^FO120,420
^A0N,25,25
^FD{$producto->code}^FS

^XZ

ZPL;
    }

    return response($zpl, 200, [
        'Content-Type' => 'text/plain',
        'Content-Disposition' => 'attachment; filename="etiquetas.zpl"',
    ]);
}

}
