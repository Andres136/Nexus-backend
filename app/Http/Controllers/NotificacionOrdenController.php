<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\Crm\TareaVencidaNotificacion;
use App\Notifications\NotifyAdminUserLoggedIn;
use App\Notifications\OrdenCompraNotificacion;
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
        $usuariosNotificar = User::whereIn('role_id', [4, 5, 6])->get();
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
                'ordenes_compra'  => $agrupadas[\App\Notifications\OrdenesPorVencerNotificacion::class] ?? [],
                'tareas'          => $agrupadas[\App\Notifications\Crm\TareaVencidaNotificacion::class] ?? [],
                'ingresos'        => $agrupadas[\App\Notifications\NotifyAdminUserLoggedIn::class] ?? [],
                'total_no_leidas' => $notificacionesListar->count(),
            ]
        ]);
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

}

