<?php

namespace App\Http\Controllers;

use App\Models\Roles\Permission;
use App\Models\Roles\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
   public function index()
   {
         $permissions = Permission::all();
         return response()->json($permissions);
   }
   
public function userPermissions(Request $request)
{
    $role = $request->user()->role;

    $permissions = $role->permissions()->pluck('path');

    return response()->json([
        'permissions' => $permissions
    ]);
}


    public function store(Request $request)
    {
         // Lógica para crear un nuevo permiso
    }

    //Guardar las rutas
public function guardarRutas(Request $request)
{
    $rutas = $request->input('rutas', []);

    if (!is_array($rutas)) {
        return response()->json([
            'message' => 'Formato inválido. Se esperaba un arreglo de rutas.'
        ], 422);
    }

    DB::beginTransaction();

    try {
        foreach ($rutas as $ruta) {

            if (!isset($ruta['path'])) {
                continue;
            }

            // -----------------------------------
            // NORMALIZAR path → siempre con '/'
            // -----------------------------------
            $clean = trim($ruta['path']);      // quita espacios
            $clean = trim($clean, "/");        // quita slashes extra
            $path  = "/" . $clean;             // siempre se guarda con slash inicial
            // -----------------------------------

            Permission::updateOrCreate(
                ['path' => $path],
                [
                    'name'      => $ruta['name']    ?? 'Sin Nombre',
                    'module'    => $ruta['module']  ?? 'General',
                    'enabled'   => $ruta['enabled'] ?? true,
                ]
            );
        }

        DB::commit();

        return response()->json([
            'message' => 'Rutas registradas correctamente',
        ], 200);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Error al guardar rutas',
            'error'   => $e->getMessage()
        ], 500);
    }
}

//ASIGNAR PERMISOS A ROLES
public function asignar(Request $request)
{


     $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permission,id'
        ]);

        $role = Role::findOrFail($request->role_id);

        $role->permission()->sync($request->permission_ids ?? []);

        return response()->json(['message' => 'Permisos asignados correctamente']);

}
   public function obtenerPorRol($role_id)
    {
        $role = Role::findOrFail($role_id);

        return $role->permission()->pluck('permission_id');
    }


//Obtener los permisos por usuario

public function permissions()
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'permissions' => []
        ], 200);
    }

    // Permisos por rol (si el usuario tiene rol asignado)
    $permisosRol = $user->role
        ? $user->role->permissions->pluck('path')->toArray()
        : [];

    // Permisos individuales asignados directamente al usuario
    $permisosUsuario = $user->permisos
        ? $user->permisos->pluck('path')->toArray()
        : [];

    // Unir y limpiar duplicados
    $permisos = array_values(array_unique(array_merge($permisosRol, $permisosUsuario)));

    return response()->json([
        'permissions' => $permisos
    ]);
}


public function asignarPermisosUsuario(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'permission_ids' => 'array'
    ]);

    $user = User::find($request->user_id);

    $user->permisos()->sync($request->permission_ids);

    return response()->json(['message' => 'Permisos asignados al usuario']);
}

public function permisosDeUsuario($user_id)
{
    $user = User::findOrFail($user_id);

    // permisos por rol
    $permisosRol = $user->role
        ? $user->role->permissions->pluck('path')->toArray()
        : [];

    // permisos asignados directamente al usuario
    $permisosUsuario = $user->permisos
        ? $user->permisos->pluck('path')->toArray()
        : [];

    // unir sin duplicados
    $permisos = array_values(array_unique(array_merge($permisosRol, $permisosUsuario)));

    return response()->json([
        'permissions' => $permisos
    ]);
}

}
