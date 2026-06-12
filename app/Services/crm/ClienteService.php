<?php

namespace App\Services\crm;

use App\EstadoEnum;
use App\Models\Crm\Cliente;
use App\Models\User;
use App\RolEnum;
use App\Services\Crm\ComercialDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteService
{
    public function __construct(
        private readonly ComercialDashboardService $comercialDashboardService
    ) {
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $user = Auth::user();
        $rolesConVistaGlobal = [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
            RolEnum::COMERCIAL->value,
        ];
        $puedeVerTodos = in_array($user->role_id, $rolesConVistaGlobal);
        $usuarioFiltro = $puedeVerTodos && $request->filled('user_id')
            ? (int) $request->input('user_id')
            : null;
        $usuarioEstadistica = $usuarioFiltro ?? ($puedeVerTodos ? null : $user->id);
        $estadoFiltro = $request->filled('estado_id')
            ? (int) $request->input('estado_id')
            : null;

        $clientes = Cliente::with([
                'usuario:id,name',
                'estado',
                'seguimientos' => function ($query) use ($user, $puedeVerTodos) {
                    if (!$puedeVerTodos) {
                        $query->where('user_id', $user->id);
                    }

                    $query->latest();
                },
                'seguimientos.usuario:id,name',
                'ultimaGestion.usuario:id,name',
            ])
            ->withCount([
                'seguimientos as gestiones_usuario_count' => function ($query) use ($usuarioEstadistica) {
                    $query->when(
                        $usuarioEstadistica,
                        fn ($q) => $q->where('user_id', $usuarioEstadistica),
                        fn ($q) => $q->whereColumn('seguimiento_clientes.user_id', 'clientes.user_id')
                    );
                },
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
            ->when($puedeVerTodos && $usuarioFiltro, function ($query) use ($usuarioFiltro) {
                $query->where('user_id', $usuarioFiltro);
            })
            ->when($puedeVerTodos && $estadoFiltro, function ($query) use ($estadoFiltro) {
                $query->where('estado_id', $estadoFiltro);
            })
            ->when(!$puedeVerTodos, function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('estado_id', EstadoEnum::ACTIVO->value);
            })
            ->orderByRaw("
                CASE
                    WHEN seguimientos_max_created_at IS NULL THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('seguimientos_max_created_at')
            ->orderByDesc('created_at')
            ->paginate($search ? 25 : 10);

        $clientesActivos = Cliente::where('estado_id', EstadoEnum::ACTIVO->value)
            ->when($usuarioEstadistica, fn ($query) => $query->where('user_id', $usuarioEstadistica))
            ->withCount([
                'seguimientos as gestiones_usuario_count' => function ($query) use ($usuarioEstadistica) {
                    $query->when(
                        $usuarioEstadistica,
                        fn ($q) => $q->where('user_id', $usuarioEstadistica),
                        fn ($q) => $q->whereColumn('seguimiento_clientes.user_id', 'clientes.user_id')
                    );
                },
            ])
            ->get(['id']);

        $clientesListos = $clientesActivos
            ->where('gestiones_usuario_count', '>=', 3)
            ->count();
        $gestionesFaltantes = $clientesActivos
            ->sum(fn ($cliente) => max(0, 3 - $cliente->gestiones_usuario_count));

        return array_merge($clientes->toArray(), [
            'estadisticas' => [
                'usuario_id' => $usuarioEstadistica,
                'clientes_activos' => $clientesActivos->count(),
                'clientes_listos' => $clientesListos,
                'clientes_pendientes' => $clientesActivos->count() - $clientesListos,
                'gestiones_faltantes' => $gestionesFaltantes,
            ],
            'resumen_mensual_usuarios' => $this->comercialDashboardService
                ->getResumenMesActual($usuarioEstadistica),
            'filtros' => [
                'puede_filtrar_usuarios' => $puedeVerTodos,
                'usuarios' => $puedeVerTodos
                    ? User::whereIn('id', Cliente::query()->select('user_id')->distinct())
                        ->orderBy('name')
                        ->get(['id', 'name'])
                    : [],
                'estados' => [
                    ['id' => EstadoEnum::ACTIVO->value, 'nombre' => 'Activo'],
                    ['id' => EstadoEnum::INACTIVO->value, 'nombre' => 'Inactivo'],
                ],
            ],
        ]);
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
            RolEnum::EJECUTIVO_COMERCIAL->value,
        ])) {
            return ['autorizado' => false];
        }

        $cliente = Cliente::findOrFail($id);

        if ($cliente->estado_id == EstadoEnum::ACTIVO->value) {
            $totalGestiones = $cliente->seguimientos()
                ->where('user_id', $user->id)
                ->count();

            if ($totalGestiones < 3) {
                return [
                    'autorizado' => true,
                    'puede_cambiar_estado' => false,
                    'total_gestiones' => $totalGestiones,
                    'gestiones_requeridas' => 3,
                ];
            }
        }

        $cliente->estado_id = $cliente->estado_id == EstadoEnum::ACTIVO->value
            ? EstadoEnum::INACTIVO->value
            : EstadoEnum::ACTIVO->value;

        $cliente->save();

        return [
            'autorizado' => true,
            'puede_cambiar_estado' => true,
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
