<?php

namespace App\Services\crm;

use App\EstadoEnum;
use App\Models\Crm\Cliente;
use App\Models\User;
use App\RolEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteService
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $user = Auth::user();
return Cliente::with([
    'usuario:id,name',
    'estado',

    'seguimientos' => function ($query) use ($user) {

        if (!in_array($user->role_id, [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
            RolEnum::COMERCIAL->value,
        ])) {

            $query->where('user_id', $user->id);
        }

        $query->latest();
    },

    'ultimaGestion.usuario:id,name'
])

    ->withMax('seguimientos', 'created_at')

    ->when($search, function ($query) use ($search) {

        $query->where(function ($q) use ($search) {

            $q->where('nombre', 'LIKE', "%{$search}%")
              ->orWhere('nit', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('telefono', 'LIKE', "%{$search}%");

        });

    })

    ->when(
        !in_array($user->role_id, [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
            RolEnum::COMERCIAL->value,
        ]),
        function ($query) use ($user) {

            $query->where('user_id', $user->id);

        }
    )

    ->when(
        !in_array($user->role_id, [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
            RolEnum::COMERCIAL->value,
        ]),
        function ($query) {

            $query->where(
                'estado_id',
                EstadoEnum::ACTIVO->value
            );

        }
    )

    // 🔥 SIN GESTIÓN PRIMERO
    ->orderByRaw("
        CASE
            WHEN seguimientos_max_created_at IS NULL THEN 0
            ELSE 1
        END
    ")

    // 🔥 GESTIONES MÁS ANTIGUAS
    ->orderBy('seguimientos_max_created_at', 'asc')

    // 🔥 INACTIVOS
    ->when(
        in_array($user->role_id, [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
            RolEnum::COMERCIAL->value,
        ]),
        function ($query) {

            $query->orderByRaw("
                CASE
                    WHEN estado_id = " . EstadoEnum::INACTIVO->value . " THEN 0
                    ELSE 1
                END
            ");

        }
    )

    ->orderByDesc('created_at')

    ->paginate($search ? 25 : 10);
    }

    public function clientesTodos(Request $request): array
    {
        $search = $request->input('search', '');

        $clientes = Cliente::when($search, function ($query, $search) {
                return $query->where('nombre', 'LIKE', "%$search%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return ['data' => $clientes];
    }

    public function store(array $data): Cliente
    {
        return Cliente::create([
            'nombre'    => $data['nombre'],
            'email'     => $data['email'],
            'telefono'  => $data['telefono'],
            'direccion' => $data['direccion'],
            'nit'       => $data['nit'],
            'user_id'   => $data['user_id'],
        ]);
    }

    public function show(string $id): ?Cliente
    {
        return Cliente::with([
            'seguimientos' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(3);
            },
            'seguimientos.usuario'
        ])->find($id);
    }

    public function update(Request $request, string $id): Cliente
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->nombre    = $request->nombre;
        $cliente->email     = $request->email;
        $cliente->telefono  = $request->telefono;
        $cliente->direccion = $request->direccion;
        $cliente->nit       = $request->nit;
        $cliente->user_id   = $request->user_id;
        $cliente->estado_id = $request->estado_id ?? EstadoEnum::ACTIVO->value;
        $cliente->save();

        return $cliente;
    }

    public function destroy(string $id): void
    {
        Cliente::findOrFail($id)->delete();
    }

    public function cambiarEstado(string $id): array
    {
        $user = Auth::user();

        if (!in_array($user->role_id, [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
            RolEnum::COMERCIAL->value,
        ])) {
            return ['autorizado' => false];
        }

        $cliente = Cliente::findOrFail($id);

        $cliente->estado_id = $cliente->estado_id == EstadoEnum::ACTIVO->value
            ? EstadoEnum::INACTIVO->value
            : EstadoEnum::ACTIVO->value;

        $cliente->save();

        return [
            'autorizado' => true,
            'estado_id'  => $cliente->estado_id,
            'cliente'    => $cliente->nombre,
        ];
    }

    public function clientesUsuario(Request $request)
    {
        $userId = Auth::id();
        $search = $request->input('search');

        return Cliente::where('user_id', $userId)
            ->when($search, function ($query) use ($search) {
                return $query->where('nombre', 'LIKE', "%$search%");
            })
            ->with('usuario:id,name')
            ->paginate(5);
    }

    public function usuariosComerciales()
    {
        return User::where('role_id', 9)->get();
    }

    public function importExcel(array $clientes): int
    {
        $insertados = 0;

        foreach ($clientes as $row) {
            Cliente::updateOrCreate(
                ['nit' => $row['nit']],
                [
                    'nombre'    => $row['nombre'],
                    'email'     => $row['email'],
                    'telefono'  => $row['telefono'],
                    'direccion' => $row['direccion'],
                    'user_id'   => Auth::id(),
                    'estado_id' => EstadoEnum::ACTIVO->value,
                ]
            );
            $insertados++;
        }

        return $insertados;
    }
}
