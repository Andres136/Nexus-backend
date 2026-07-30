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
                'es_responsable_departamento' => $user->role_id == RolEnum::ADMINISTRADOR->value
                    || $user->esResponsableDeSuDepartamento(),
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
        $cliente = Cliente::findOrFail($id);
        $esPrivilegiado = $user->role_id == RolEnum::ADMINISTRADOR->value
            || $user->esResponsableDeSuDepartamento();

        if ($cliente->estado_id == EstadoEnum::ACTIVO->value) {
            // Desactivar: solo el responsable asignado al cliente, o un responsable de departamento/admin.
            if ($cliente->user_id != $user->id && !$esPrivilegiado) {
                return ['autorizado' => false];
            }

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
        } elseif (!$esPrivilegiado) {
            // Activar: solo un responsable de departamento o admin.
            return ['autorizado' => false];
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
        return User::whereIn('role_id', [
                RolEnum::ADMINISTRATIVO->value,
                RolEnum::EJECUTIVO_COMERCIAL->value,
            ])
            ->where('estado_id', 3)
            ->orderBy('name')
            ->get();
    }

    /**
     * La ruta ya está protegida por el middleware `es_responsable_del_departamento`,
     * así que aquí no se restringe por rol: quien llega hasta acá puede ver todos
     * los clientes inactivos (con los filtros de búsqueda/responsable que aplique).
     */
    public function exportarInactivos(Request $request)
    {
        $search = $request->input('search');
        $usuarioFiltro = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        return Cliente::with(['usuario:id,name', 'ultimaGestion'])
            ->where('estado_id', EstadoEnum::INACTIVO->value)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%{$search}%")
                        ->orWhere('nit', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('telefono', 'LIKE', "%{$search}%");
                });
            })
            ->when($usuarioFiltro, function ($query) use ($usuarioFiltro) {
                $query->where('user_id', $usuarioFiltro);
            })
            ->orderBy('nombre')
            ->get();
    }

    public function asignarPorExcel(array $nits, int $userId): array
    {
        $nits = array_values(array_unique(array_filter(array_map('strval', $nits))));

        $clientes = Cliente::whereIn('nit', $nits)->get();
        $noEncontrados = array_values(array_diff($nits, $clientes->pluck('nit')->all()));

        Cliente::whereIn('nit', $nits)->update(['user_id' => $userId]);

        return [
            'asignados' => $clientes->count(),
            'no_encontrados' => $noEncontrados,
        ];
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
