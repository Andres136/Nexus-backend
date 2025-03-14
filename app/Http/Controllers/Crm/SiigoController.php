<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\SiigoService;
use Illuminate\Http\Request;

class SiigoController extends Controller
{
  protected $siigoService;
 

  
  
  public function __construct(SiigoService $siigoService)
  {
        $this->siigoService = $siigoService;
  }
    
  
    public function index(Request $request)
    {
         // Llama al servicio que consulta /v1/products de Siigo
    $data = $this->siigoService->getProducts(); 
    // $data será algo como: ["pagination"=>..., "results"=> [...]] si la API de Siigo respondió bien

    if (!$data || !isset($data['results'])) {
        return response()->json(['message' => 'No se pudo obtener productos'], 500);
    }

    return response()->json($data, 200);
    
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
}
