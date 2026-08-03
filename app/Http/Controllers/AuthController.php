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
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('departamento.responsable', 'role', 'estado', 'sede'); // Solo usuarios activos por defecto

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
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
        $rutaImagen = $request->file('imagen')->store('usuarios', 'public');
    }

    $rutaFotoPerfil = null;
    if ($request->hasFile('foto_perfil')) {
        $rutaFotoPerfil = $request->file('foto_perfil')
            ->store('usuarios/perfiles', 'public');
    }
    
    
 
        $user = User::create([
            'name' => $request->name,
            'apellidos' => $request->apellidos,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'telefono' => $request->telefono,
            'role_id' => $request->role_id,
            'estado_id' => 3,
            'departamento_id' => $request->departamento_id,
            'sede_id' => $request->sede_id ?? null,
       
            'imagen' => $rutaImagen,
            'foto_perfil' => $rutaFotoPerfil,
       
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

        $request->validate([
            'imagen' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'foto_perfil' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
        ], [
            'imagen.image' => 'La imagen de uso interno debe ser una imagen válida.',
            'imagen.mimes' => 'La imagen de uso interno debe estar en formato JPG, PNG, GIF o WEBP.',
            'imagen.max' => 'La imagen de uso interno no puede pesar más de 2 MB.',
            'foto_perfil.image' => 'La foto de perfil debe ser una imagen válida.',
            'foto_perfil.mimes' => 'La foto de perfil debe estar en formato JPG, PNG, GIF o WEBP.',
            'foto_perfil.max' => 'La foto de perfil no puede pesar más de 2 MB.',
        ]);

    
        // 3) Si envían archivo nuevo, bórralo y guarda la ruta
        if ($request->hasFile('imagen')) {
            if ($user->imagen) {
                Storage::disk('public')->delete($user->imagen);
            }
            $user->imagen = $request->file('imagen')->store('usuarios', 'public');
        }

        if ($request->hasFile('foto_perfil')) {
            // Borra la anterior, si existe
            if ($user->foto_perfil) {
                    Storage::disk('public')->delete($user->foto_perfil);
            }
            // Almacena la nueva y asigna la ruta
            $user->foto_perfil = $request->file('foto_perfil')
                                ->store('usuarios/perfiles', 'public');
        }
    
        // 4) Rellena el resto de campos
        $user->name            = $request->name;
        $user->apellidos       = $request->input('apellidos', $user->apellidos);
        $user->email           = $request->email;
        $user->telefono        = $request->telefono;
        $user->role_id         = $request->role_id;
        $user->departamento_id = $request->departamento_id;
        $user->estado_id = $request->estado_id ?? $user->estado_id;
        $user->sede_id = $request->sede_id ?? $user->sede_id;
        

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
        
        // Crear el token para el usuario autenticado
        try {
            $token = $user->createToken('auth_token')->plainTextToken;
        } catch (\Exception $e) {
            // Si hay problema con createToken, retornar sin token (puede usarse sesión)
            Log::error('Error creando token: ' . $e->getMessage());
            $token = null;
        }
        
        // Enviar notificación al administrador de login (con manejo seguro de errores)
        // Esto NO debe afectar el funcionamiento del login bajo ninguna circunstancia
        try {
            $admins = User::where('role_id', 1)
                         ->where('estado_id', 3) // Solo admins activos
                         ->get();
            
            if ($admins->count() > 0) {
                $ip = $request->ip();
                $device = substr($request->userAgent(), 0, 120);
                foreach ($admins as $admin) {
                    try {
                        if ($admin->email && $admin->id !== $user->id) { // No notificar a sí mismo
                            $admin->notify(new NotifyAdminUserLoggedIn($user->name, $ip,$device ));
                        }
                    } catch (\Exception $notifError) {
                        // Error individual de notificación - continúa con los demás
                        Log::warning('Error notificando al admin ' . $admin->email . ': ' . $notifError->getMessage());
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            // Error general de notificaciones - NO debe afectar el login
            Log::error('Error general enviando notificaciones de login: ' . $e->getMessage());
        }

        // Retornar respuesta (LOGIN SIEMPRE FUNCIONA independiente de las notificaciones)
        $response = [
            'message' => 'Bienvenido a nuestro sistema de gestión, Hola: ' . $user->name,
            'user' => $user,
        ];
        
        if ($token) {
            $response['token'] = $token;
        }
        
        return $response;
    }
    

    // Envía el enlace de restablecimiento. La respuesta es siempre la misma
    // exista o no el correo, para no revelar qué cuentas están registradas.
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRules::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
                $user->save();

                // Cierra las sesiones activas: si alguien más tenía acceso con la
                // contraseña anterior, queda desconectado.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'errors' => ['email' => [__($status)]],
            ], 422);
        }

        return response()->json([
            'message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
        ]);
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
 // ✅ Eliminar todos los tokens del usuario cuando se desactiva
    if ($nuevoEstado == 4) { // Solo cuando se desactiva
        try {
            $user->tokens()->delete();
            Log::info("Tokens eliminados para usuario desactivado: {$user->name} (ID: {$user->id})");
        } catch (\Exception $e) {
            Log::error("Error eliminando tokens del usuario {$user->id}: " . $e->getMessage());
            // No fallar la desactivación por error en tokens
        }
    }
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
    $usuarios = User::where('departamento_id', $id)
    ->where('estado_id', 3) // Solo usuarios activos
    ->with('departamento')->get();

    if ($usuarios->isEmpty()) {
        return response()->json(["Error" => "No hay usuarios en este departamento"], 404);
    }

    return response()->json($usuarios);
}

public function indexUsuarios(Request $request)
{ 
     $search = $request->input('search');
    $query = User::select('id', 'name', 'apellidos', 'sede_id')
                     ->where('estado_id', 3); // Solo usuarios activos

    if ($search && strlen($search) >= 2) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
              ->orWhere('apellidos', 'like', "%$search%");
        });
    }

    return $query->get(); // sin paginar
}

//Traer usuarios que tengan rol 8 de conductor

public function userAll()
{
    $conductores = User::where('estado_id',3)->get();

    if ($conductores->isEmpty()) {
        return response()->json(["Error" => "No hay conductores registrados"], 404);
    }

    return response()->json($conductores);
}
}
