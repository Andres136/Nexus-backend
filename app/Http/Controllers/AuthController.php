<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistroRequest;
use App\Http\Requests\TareaRequest;
use App\Models\Crm\Sede;
use App\Models\Tareas;
use App\Models\User;
use App\Notifications\NotifyAdminUserLoggedIn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('departamento', 'role', 'estado', 'sede');
    
        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telefono', 'like', "%{$search}%");
            });
        }
    
        return response()->json($query->paginate(10));
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegistroRequest $request)
    {

            // si subieron un archivo, lo almacenamos y guardamos la ruta
    $rutaImagen = null;
    if ($request->hasFile('imagen')) {
        $rutaImagen = $request->file('imagen')
            ->store('usuarios', 'public'); // guarda en storage/app/public/usuarios
    }
    
    //Crear la Sede
    $sede= Sede::create([
        'nombre' => $request->sede_nombre,
        'direccion' => $request->sede_direccion,
    ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'telefono' => $request->telefono,
            'role_id' => $request->role_id,
            'estado_id' => 3,
            'departamento_id' => $request->departamento_id,
            'sede_id' => $sede->id, // Asignar la sede creada
            'imagen' => $rutaImagen,
       
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
        // 1) Busca el usuario o falla
        $user = User::findOrFail($id);
    

    
        // 3) Si envían archivo nuevo, bórralo y guarda la ruta
        if ($request->hasFile('imagen')) {
            // Borra la anterior, si existe
            if ($user->imagen) {
                    Storage::disk('public')->delete($user->imagen);
            }
            // Almacena la nueva y asigna la ruta
            $user->imagen = $request->file('imagen')
                                ->store('usuarios','public');
        }
    
        // 4) Rellena el resto de campos
        $user->name            = $request->name;
        $user->email           = $request->email;
        $user->telefono        = $request->telefono;
        $user->role_id         = $request->role_id;
        $user->departamento_id = $request->departamento_id;
        $user->estado_id = $request->estado_id ?? $user->estado_id;
        if ($request->filled('sede_nombre')) {
            $sede = Sede::firstOrCreate([
                'nombre' => $request->sede_nombre,
                'direccion' => $request->sede_direccion ?? 'Sin dirección',
            ]);
            $user->sede_id = $sede->id;
        } else {
            $user->sede_id = $request->sede_id;
        }
        

        // 5) Sólo cambia password si llegó uno nuevo
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
    
        // 6) Guarda todo
        $user->save();
    
        // 7) Devuelve respuesta
        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user'    => $user,
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
    $usuarios = User::where('departamento_id', $id)->with('departamento')->get();

    if ($usuarios->isEmpty()) {
        return response()->json(["Error" => "No hay usuarios en este departamento"], 404);
    }

    return response()->json($usuarios);
}

public function indexUsuarios(Request $request)
{ 
     $search = $request->input('search');
    $query = User::select('id', 'name');

    if ($search && strlen($search) >= 2) {
        $query->where('name', 'like', "%$search%");
    }

    return $query->get(); // sin paginar
}

//Traer usuarios que tengan rol 8 de conductor

public function conductores()
{
    $conductores = User::whereIn('role_id',[8,6,5,4])->get(['id', 'name', 'email', 'telefono']);

    if ($conductores->isEmpty()) {
        return response()->json(["Error" => "No hay conductores registrados"], 404);
    }

    return response()->json($conductores);
}
}
