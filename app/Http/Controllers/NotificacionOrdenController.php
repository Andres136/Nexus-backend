<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\Crm\TareaVencidaNotificacion;
use App\Notifications\OrdenCompraNotificacion;
use App\Notifications\OrdenesPorVencerNotificacion;
use App\Services\OrdenCompraService;
use App\Services\TareaVencidaService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Notification ;

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
       $usuariosNotificar= User::whereIn('role_id', [4,5,6])->get();
       //Enviar notificación por correo
       Notification::send($usuariosNotificar, new OrdenCompraNotificacion($orden));
       return response()->json(['message' => 'Notificaciones enviadas'], 200);
    }
    public function listarNotificaciones()
{
    $usuario = auth()->user();
    
    if (!$usuario) {
        return response()->json(['error' => 'Usuario no autenticado'], 401);
    }

    $notificaciones = $usuario->unreadNotifications->groupBy('type'); // Agrupar por tipo

    return response()->json([
        'notificaciones' => [
            'ordenes_compra' => $notificaciones[OrdenesPorVencerNotificacion::class] ?? [],
            'tareas' => $notificaciones[TareaVencidaNotificacion::class] ?? [],
            'total_no_leidas' => $usuario->unreadNotifications->count(),
        ]
    ]);
}

    
    public function EnviarTaskVencida()
    {
        $this->tareaVencidaService->notificarTareasVencidas();
        return response()->json(['message' => 'Notificaciones enviadas'], 200);
    }
    

    
}
