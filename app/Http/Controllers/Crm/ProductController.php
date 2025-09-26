<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreProductRequest;
use App\Http\Requests\Crm\UpdateProductRequest;
use App\Models\Crm\Inventario;
use App\Models\Crm\product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpParser\Node\Stmt\TryCatch;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    try {
        $products = Product::with([
            'inventarios.empresa',
            'inventarios.sede',
            'inventarios.bodega'
        ])->get();

        return response()->json([
            'message' => 'Listado de productos',
            'data' => $products
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al obtener los productos',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getAllProducts(Request $request)
{
    try{

         return product::query()
        ->when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
            //TAmbien por descripcion y código
            ->orWhere('description', 'like', "%{$request->search}%")
              ->orWhere('code', 'like', "%{$request->search}%")
        )
         
        ->limit(50) // para no saturar la red
        ->get();

        return response()->json([
            'message' => 'Listado de productos',
            'data' => $products
        ], 200);


    }catch(\Exception $e){
        return response()->json([
            'message' => 'Error al obtener los productos',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
     try{
       $product = product::create($request->validated());

       if($request->has('inventarios')){
        foreach($request->inventarios as $inv){
             Inventario::create([
                'producto_id' => $product->id,
                'empresa_id' => $inv['empresa_id'],
                'sede_id' => $inv['sede_id'],
                'bodega_id' => $inv['bodega_id'],
                'stock' => $inv['stock'] ?? 0,
                'precio' => $inv['precio'] ?? null,
                'min_stock' => $inv['min_stock'] ?? 0,
                'max_stock' => $inv['max_stock'] ?? 0,
                'fecha_vencimiento' => $inv['fecha_vencimiento'] ?? null,
             ]);
        }
       }
return response()->json([
    'message' => 'Producto creado exitosamente',
    'data' => $product->load('inventarios.empresa', 'inventarios.sede', 'inventarios.bodega')
], 201);
     } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el producto', 'error' => $e->getMessage()], 500);
    
     }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        return response()->json(['data' => $product], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
         try{
        $product = product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        $product->update($request->validated());

        // Actualizar inventarios 
        if($request->has('inventarios')){
            foreach($request->inventarios as $iv){
                Inventario::updateOrCreate(
                    [
                        'producto_id' => $product->id,
                        'empresa_id' => $iv['empresa_id'],
                        'sede_id' => $iv['sede_id'],
                        'bodega_id' => $iv['bodega_id'],
                    ],
                    [
                        'stock' => $iv['stock'] ?? 0,
                        'precio' => $iv['precio'] ?? null,
                        'min_stock' => $iv['min_stock'] ?? 0,
                        'max_stock' => $iv['max_stock'] ?? 0,
                        'fecha_vencimiento' => $iv['fecha_vencimiento'] ?? null,
                    ]
                );
            }

        return response()->json([
            'message' => 'Producto actualizado exitosamente',
            'data' => $product->load('inventarios.empresa', 'inventarios.sede', 'inventarios.bodega')
        ], 200);

        }

         } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el producto', 'error' => $e->getMessage()], 500);
         }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
