<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    //Funcion para  actualizar un usuario

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    // Asignaciones directas
    $user->name = $request->name;
    $user->email = $request->email;
    $user->telefono = $request->telefono;
    $user->role_id = $request->role_id;
    $user->departamento_id = $request->departamento_id;
    $user->estado_id = $request->estado_id;

    // Solo actualizar la contraseña si fue enviada
    if ($request->filled('password')) {
        $user->password = bcrypt($request->password);
    }

    $user->save();

    return response()->json([
        'message' => 'Usuario actualizado correctamente',
        'user' => $user
    ]);
}



}