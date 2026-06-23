<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRutasRequest;
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
public function guardarRutas(StoreRutasRequest $request)
{
    $validated = $request->validated();
    $rutas = $validated['rutas'] ?? [];

    if (!is_array($rutas) || empty($rutas)) {
        return response()->json([
            'message' => 'Debes enviar al menos una ruta.',
            'errors' => [
                'rutas' => ['Debes enviar al menos una ruta.'],
            ],
        ], 422);
    }

    $rutasNormalizadas = [];
    $pathsVistos = [];
    $namesVistos = [];
    $errors = [];

    foreach ($rutas as $index => $ruta) {
        $clean = trim($ruta['path'] ?? '');
        $clean = trim($clean, "/");
        $path = $clean === '' ? '' : "/" . $clean;

        $name = trim($ruta['name'] ?? '');
        $module = trim($ruta['module'] ?? '');

        if ($path === '') {
            $errors["rutas.$index.path"][] = 'El campo ruta es obligatorio.';
            continue;
        }

        if (isset($pathsVistos[$path])) {
            $errors["rutas.$index.path"][] = "La ruta {$path} está repetida en el formulario.";
        }

        $pathsVistos[$path] = true;

        if ($name === '') {
            $name = $path;
        }

        if ($module === '') {
            $module = 'General';
        }

        if (isset($namesVistos[$name])) {
            $errors["rutas.$index.name"][] = "El nombre {$name} está repetido en el formulario.";
        }

        $namesVistos[$name] = true;

        $existeNombreEnOtraRuta = Permission::where('name', $name)
            ->where('path', '<>', $path)
            ->exists();

        if ($existeNombreEnOtraRuta) {
            $errors["rutas.$index.name"][] = "El nombre {$name} ya está registrado en otra ruta.";
        }

        $rutasNormalizadas[] = [
            'path' => $path,
            'name' => $name,
            'module' => $module,
            'enabled' => $ruta['enabled'] ?? true,
        ];
    }

    if (!empty($errors)) {
        return response()->json([
            'message' => 'Hay errores en las rutas enviadas.',
            'errors' => $errors,
        ], 422);
    }

    DB::beginTransaction();

    try {
        foreach ($rutasNormalizadas as $ruta) {
            Permission::updateOrCreate(
                ['path' => $ruta['path']],
                [
                    'name' => $ruta['name'],
                    'module' => $ruta['module'],
                    'enabled' => (bool) $ruta['enabled'],
                ]
            );
        }

        DB::commit();

        return response()->json([
            'message' => 'Rutas registradas correctamente',
            'total' => count($rutasNormalizadas),
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
