<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistroRequest;
use App\Http\Requests\TareaRequest;
use App\Models\Tareas;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
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
    public function store(RegistroRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'telefono' => $request->telefono,
            'role_id' => $request->role_id,
            'estado_id' => 3,
            'departamento_id' => $request->departamento_id,
            'estado_id' => $request->estado_id
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'access_token' => $token,
      
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //listar documentos relacionados a un usuario
        $user = User::with('documentos','procesos')->find($id);
        if (!$user) {
            return response()->json(["Error" => "Usuario no encontrado"], 404);
        }
        return response()->json($user);
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
