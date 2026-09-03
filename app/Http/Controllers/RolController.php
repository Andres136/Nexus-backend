<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\RolEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RolController extends Controller
{
    //

    public function index()
    {
        //
        $roles = Roles::all();
        return response()->json($roles);

    }


    public function store(Request $request)
{
    // 1️⃣ Primero validar
    $validator = Validator::make($request->all(), [
        'nombre' => 'required|string|max:255',
    ],
    [
        'nombre.required' => 'El nombre del rol es obligatorio.',
        'nombre.string' => 'El nombre del rol debe ser una cadena de texto.',
        'nombre.max' => 'El nombre del rol no debe exceder los 255 caracteres.',
    ]
);
//Mensaje de error si falla la validacion


    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    // 2️⃣ Luego crear el rol
    $rol = Roles::create([
        'nombre' => $request->input('nombre'),
    ]);

    // 3️⃣ Todo rol nuevo hereda los permisos del rol "Invitado" como base,
    //     así no queda en blanco (Acceso Denegado en todas las rutas) hasta
    //     que un admin lo configure en "Asignar Permisos a Roles".
    $invitado = Roles::find(RolEnum::INVITADO->value);
    $permisosBase = $invitado
        ? $invitado->permissions()->pluck('permission.id')->all()
        : [];

    $rol->permissions()->sync($permisosBase);

    return response()->json([
        'message' => 'Rol creado exitosamente',
        'rol' => $rol
    ], 201);
}


///Actualizar rol
public function update(Request $request, $id)
{
    // 1️⃣ Primero validar
    $validator = Validator::make($request->all(), [
        'nombre' => 'required|string|max:255',
    ],
    [
        'nombre.required' => 'El nombre del rol es obligatorio.',
        'nombre.string' => 'El nombre del rol debe ser una cadena de texto.',
        'nombre.max' => 'El nombre del rol no debe exceder los 255 caracteres.',
    ]
);
    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    // 2️⃣ Luego actualizar el rol
    $rol = Roles::find($id);
    if (!$rol) {
        return response()->json([
            'message' => 'Rol no encontrado'
        ], 404);
    }

    $rol->nombre = $request->input('nombre');
    $rol->save();

    return response()->json([
        'message' => 'Rol actualizado exitosamente',
        'rol' => $rol
    ], 200);

}

}
