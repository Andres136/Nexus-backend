<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Corporate\StoreCorporateDocumentRequest;
use App\Http\Requests\Corporate\UpdateCorporateDocumentRequest;
use App\Services\Corporate\CorporateDocumentService;
use Illuminate\Http\Request;

class CorporateDocumentController extends Controller
{
    public function __construct(private CorporateDocumentService $service)
    {
    }

    public function index()
    {
        return response()->json([
            'data' => $this->service->publicList(),
        ]);
    }

    public function show(string $slug)
    {
        return response()->json([
            'data' => $this->service->publicShow($slug),
        ]);
    }

    public function download(string $slug, Request $request)
    {
        return response()->json([
            'message' => 'Descarga registrada',
            'data' => $this->service->registerDownload($slug, $request),
        ]);
    }

    public function downloadFile(string $slug, Request $request)
    {
        return $this->service->downloadFile($slug, $request);
    }

    public function adminIndex(Request $request)
    {
        return response()->json([
            'data' => $this->service->adminList($request->only('search')),
        ]);
    }

    public function adminStore(StoreCorporateDocumentRequest $request)
    {
        return response()->json([
            'message' => 'Documento corporativo creado correctamente',
            'data' => $this->service->store($request->validated()),
        ], 201);
    }

    public function adminShow(int $corporateDocument)
    {
        return response()->json([
            'data' => $this->service->adminShow($corporateDocument),
        ]);
    }

    public function adminUpdate(UpdateCorporateDocumentRequest $request, int $corporateDocument)
    {
        return response()->json([
            'message' => 'Documento corporativo actualizado correctamente',
            'data' => $this->service->update($corporateDocument, $request->validated()),
        ]);
    }

    public function adminDestroy(int $corporateDocument)
    {
        $this->service->deactivate($corporateDocument);

        return response()->json([
            'message' => 'Documento corporativo inactivado correctamente',
        ]);
    }
}
