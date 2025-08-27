<?php

namespace App\Http\Controllers;

use App\Models\Departamentos;
use App\Models\User;
use App\Notifications\Crm\TareaVencidaNotificacion;
use App\Notifications\NotifyAdminUserLoggedIn;
use App\Notifications\OrdenCompraNotificacionMejorada;
use App\Notifications\OrdenesPorVencerNotificacion;
use App\Services\OrdenCompraService;
use App\Services\TareaVencidaService;


use Illuminate\Support\Facades\Notification;

class NotificacionOrdenController extends Controller
{
    protected OrdenCompraService $ordenCompraService;

    protected TareaVencidaService $tareaVencidaService;






    public function __construct(OrdenCompraService $ordenCompraService, TareaVencidaService $tareaVencidaService)
    {
        $this->ordenCompraService = $ordenCompraService;
        $this->tareaVencidaService = $tareaVencidaService;
    }



    public function notificarOrdenes()
    {
        $this->ordenCompraService->notificarOrdenesPorVencer();
        return response()->json(['message' => 'Notificaciones enviadas'], 200);
    }

    public function enviarOrdenCreada($orden)
    {
        $operacionesId = Departamentos::where('nombre', 'Operaciones')->value('id');

        // CORRECCIÓN: SOLO departamento Operaciones (todos los roles)
        $usuariosNotificar = User::where('departamento_id', $operacionesId)
            ->whereNotNull('email') // Solo usuarios con email válido
            ->get();
            
        // Enviar notificación por correo
        if ($usuariosNotificar->count() > 0) {
            Notification::send($usuariosNotificar, new OrdenCompraNotificacionMejorada($orden));
        }
        
        return response()->json(['message' => 'Notificaciones enviadas'], 200);
    }
    //Lista las notificaciones de un usuario
    public function listarNotificaciones()
    {
        $notificaciones = auth()->user()->unreadNotifications;

        //Limpiar notificaciones
        auth()->user()->unreadNotifications;
        return response()->json([
            'notificaciones' => $notificaciones,
            'total_no_leidas' => $notificaciones->count(),
        ]);
    }
    
//Marcar notificaciones como leídas
public function marcarTodasComoLeidas()
{
    auth()->user()->unreadNotifications->markAsRead();

    return response()->json(['success' => true]);
}





    public function EnviarTaskVencida()
    {
        $this->tareaVencidaService->notificarTareasVencidas();
        return response()->json(['message' => 'Notificaciones enviadas'], 200);
    }

    public function notificacionesPqrs($tipo='pqr')
{
    $usuario = auth()->user();

    if (!$usuario) {
        return response()->json(['error' => 'Usuario no autenticado'], 401);
    }

    // Obtén las notificaciones sin leer
    $notificaciones = $usuario->unreadNotifications;

    // Si quieres verlas en el debug:
    // dd($notificaciones);

    // Clona la colección para agrupar
    $notificacionesListar = collect($notificaciones->all());

    // Agrupa por type (asegúrate de usar el namespace correcto)
    $agrupadas = $notificacionesListar->groupBy('type');

    // (Opcional) Marca como leídas
    $usuario->unreadNotifications->markAsRead();

    // Retorna la respuesta
    return response()->json([
        'notificaciones' => [
            'pqrs' => $agrupadas[\App\Notifications\PqrNotifycaciones::class] ?? [],
            'total_no_leidas' => $notificacionesListar->count(),
        ]
    ]);
}

    // Método para listar notificaciones de PQRs
public function listarNotificacionesPqrs()
{
    $user = auth()->user();

    $notificacionesPqrs = $user->unreadNotifications->filter(function ($noti) {
        return $noti->type === \App\Notifications\pqrNotifycaciones::class;
    });

    return response()->json([
        'notificaciones'  => $notificacionesPqrs->values(), // limpiar índices
        'total'           => $notificacionesPqrs->count(),
    ]);
}

}

