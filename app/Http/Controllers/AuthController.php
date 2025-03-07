<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistroRequest;
use App\Http\Requests\TareaRequest;
use App\Models\Tareas;
use App\Models\User;
use App\Notifications\NotifyAdminUserLoggedIn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //traer registros de usuarios con el departamento y el rol
        $users = User::with('departamento', 'role', 'estado')->paginate(10);
        return response()->json($users);
       

       
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
        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->telefono = $request->telefono;
        $user->password = bcrypt($request->password);
        $user->role_id = $request->role_id;
        $user->departamento_id = $request->departamento_id;
        $user->estado_id = $request->estado_id;
        $user->save();
        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        


    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
    
        // Intentar autenticación con las credenciales proporcionadas
        if (!Auth::attempt($data)) {
            return response([
                'errors' => ['Email o contraseña incorrectos']
            ], 422);
        }
    
        // Obtener el usuario autenticado
        $user = Auth::user();
    
        // Verificar si el usuario está inactivo
        if ($user->estado_id === 4) { // 4: Inactivo
            Auth::logout(); // Cierra la sesión actual si el usuario está inactivo
            return response([
                'errors' => ['Tu cuenta está inactiva. Contacta al administrador.']
            ], 403);
        }
        // Enviar notificación al administrador de que un usuario ha iniciado sesión

        $admin = User::where('role_id', 1)->first();  // Ajusta a tu criterio
     
        if ($admin) {
            $admin->notify(new NotifyAdminUserLoggedIn( $user->name));
        }

        // Retornar el token y datos del usuario
        return [
            'token' => $user->createToken('auth_token')->plainTextToken,
            'message' => 'Bienvenido a nuestro sistema de gestión, Hola: ' . $user->name,
            'user' => $user,
        ];
    }
    

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return [
            'message' => 'Token eliminado'
        ];
    }
    // funcion para desactivar usuarios
    public function desactivar(string $id)
{
    $user = User::find($id);
    if (!$user) {
        return response()->json(["error" => "Usuario no encontrado"], 404);
    }

    // Alternar estado entre activo (3) e inactivo (4)
    $nuevoEstado = $user->estado_id == 3 ? 4 : 3;
    $user->estado_id = $nuevoEstado;
    $user->save();

    return response()->json([
        "message" => $nuevoEstado == 3 ? "Usuario activado correctamente" : "Usuario desactivado correctamente",
        "user" => $user
    ]);
}
public  function DeparamentosUsuario($id){
    $departamento = User::with('departamento')->find($id);
    if (!$departamento) {
        return response()->json(["Error" => "Departamento no encontrado"], 404);
    }
    return response()->json($departamento);

}

public function DepartamentoUsuario($id)
{
    $departamento = User::with('departamento')->find($id);
    if (!$departamento) {
        return response()->json(["Error" => "Departamento no encontrado"], 404);
    }
    return response()->json($departamento);}

}
