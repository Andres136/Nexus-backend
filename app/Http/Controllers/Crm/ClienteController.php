<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ClientesRequest;
use App\Http\Requests\Crm\ImportarClientesExelRequest;
use App\Models\Crm\Cliente;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function clientesTodos(Request $request)
    {
        $search = $request->input('search', '');
    
        $clientes = Cliente::when($search, function ($query, $search) {
                return $query->where('nombre', 'LIKE', "%$search%");
            })
            ->orderBy('created_at', 'desc') // 🔽 Clientes más recientes primero
            ->get();
    
        return response()->json(['data' => $clientes]);
    }
    

    public function index(Request $request)
    {
        //paginarlos y tener un buscador por nombre

          // Capturar el valor del parámetro de búsqueda
    $search = $request->input('search');

    // Filtrar clientes si hay un término de búsqueda
    $clientes = Cliente::when($search, function ($query) use ($search) {
        return $query->where('nombre', 'LIKE', "%$search%");
    })->paginate(5);

    return response()->json($clientes);
        
    }

    /**
     * Show the form for creating a new resource.
     */
   
    /**
     * Store a newly created resource in storage.
     */
    public function store(ClientesRequest $request)
    {
        //
         $clientes = Cliente::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'nit' => $request->nit,
            'user_id' => $request->user_id,
         ]);
         return response()->json([
            'message' => 'Cliente creado con exito',
            'cliente' => $clientes
         ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Buscar el cliente con solo los últimos 5 seguimientos y la información del usuario que los creó
        $cliente = Cliente::with([
            'seguimientos' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(3);
            },
            'seguimientos.usuario' // Relación para traer el usuario del seguimiento
        ])->find($id);
    
        return response()->json($cliente);
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
        //Editar un cliente
        $clientes = Cliente::find($id);
        $clientes->nombre = $request->nombre;
        $clientes->email = $request->email;
        $clientes->telefono = $request->telefono;
        $clientes->direccion = $request->direccion;
        $clientes->nit = $request->nit;
        $clientes->user_id = $request->user_id;
        $clientes->save();
        return response()->json([
            'message' => 'Cliente actualizado correctamente',
            'cliente' => $clientes
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //Eliminar un cliente
        $clientes = Cliente::find($id);
        $clientes->delete();
        return response()->json([
            'message' => 'Cliente eliminado correctamente'
        ]);
    }

    // Traer Cientes  asociados al usuario que los registro

    public function clientesUsuario(Request $request,)
    {
      // Capturar el usuario autenticado
    $userId = Auth::id(); // Obtiene el ID del usuario autenticado

    // Capturar parámetro de búsqueda
    $search = $request->input('search');

    // Construir la consulta para filtrar solo los clientes del usuario autenticado
    $clientes = Cliente::where('user_id', $userId) // Solo los clientes del usuario logueado
        ->when($search, function ($query) use ($search) {
            return $query->where('nombre', 'LIKE', "%$search%");
        })
        ->with('usuario:id,name') // Incluir el nombre del usuario
        ->paginate(5);

    return response()->json($clientes);
}

//traer usuarios solo con rol 9
public function usuariosComerciales()
{
    $usuarios = User::where('role_id', 9)->get();
    return response()->json($usuarios);
}
// app/Http/Controllers/ClienteController.php
public function importExcel(ImportarClientesExelRequest $request)
{
    $data = $request->validated();

    // Opcional: evitar duplicados por nit o email
    $insertados = 0;
    foreach ($data['clientes'] as $row) {
        Cliente::updateOrCreate(
            ['nit' => $row['nit']],                     // criterio único
            [
                'nombre'    => $row['nombre'],
                'email'     => $row['email'],
                'telefono'  => $row['telefono'],
                'direccion' => $row['direccion'],
                'user_id'   => Auth::id(), 
            ]
        );
        $insertados++;
    }

    return response()->json([
        'message'   => "Se importaron/actualizaron $insertados clientes",
    ]);
}


}
