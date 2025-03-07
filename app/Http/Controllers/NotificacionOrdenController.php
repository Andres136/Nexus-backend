<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\OrdenCompraNotificacion;
use App\Services\OrdenCompraService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Notification ;

class NotificacionOrdenController extends Controller
{
    protected OrdenCompraService $ordenCompraService;



    

    public function __construct(OrdenCompraService $ordenCompraService)
    {
        $this->ordenCompraService = $ordenCompraService;
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
        $usuario = auth()->user(); // Obtener usuario autenticado
    
        if (!$usuario) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
    
        // Obtener notificaciones no leídas y marcarlas como leídas
        $notificaciones = $usuario->unreadNotifications ?? []; 

        $usuario->unreadNotifications->markAsRead();
    
        return response()->json(['notificaciones' => $notificaciones], 200);
    }
    
    
}
