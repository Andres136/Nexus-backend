<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactoWebRequest;
use App\Http\Requests\PqrRequest;
use App\Models\Pqr;
use App\Models\User;
use App\Notifications\ContactoNotificacion;
use App\Notifications\Crm\PqrAsignadanotificacion;
use App\Notifications\pqrNotifycaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class PqrController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Pqr::with('estado', 'asignado')
            ->where('estado_id', '!=', 3); // Excluir PQRs con estado "Resuelto"
    
        // Filtro por estado (relación)
        if (request('estado')) {
            $query->whereHas('estado', function ($q) {
                $q->where('nombre', 'like', '%' . request('estado') . '%');
            });
        }
    
        // Filtro por nombre de empresa
        if (request('empresa')) {
            $query->where('empresa', 'like', '%' . request('empresa') . '%');
        }
    
        // Ordenar por fecha descendente
        $pqrs = $query->orderBy('created_at', 'desc')->paginate(10);
    
        return response()->json($pqrs);
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(PqrRequest $request)
    {
        $data = new Pqr();
        $data->nombre = $request->nombre;
        $data->empresa = $request->empresa;
        $data->email = $request->email;
        $data->telefono = $request->telefono;
        $data->mensaje = $request->mensaje;
        $data->estado_id = 1;
        $data->asignado_a = null; // Pendiente para asignación manual
    
        // Guardar archivo
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('uploads/pqr', $filename, 'public');
            $data->archivo = 'uploads/pqr/' . $filename;
        }
    
        // Código de radicado legible
        $fecha = now()->format('Ymd');
        $contador = Pqr::whereDate('created_at', now())->count() + 1;
        $data->codigo_radicado = 'PQR-' . $fecha . '-' . str_pad($contador, 4, '0', STR_PAD_LEFT);
    
        $data->save();
    
        // Notificaciones
        $emailSolicitante = $request->email;
        $admins = User::where('role_id', 1)->get();
    
        Notification::send($admins, new pqrNotifycaciones($data, 'admin', $emailSolicitante));
    
        Notification::route('mail', $emailSolicitante)
            ->notify(new pqrNotifycaciones($data, 'usuario', $admins->first()->email));
    
        return response()->json([
            'message' => 'PQR enviada correctamente',
            'codigo_radicado' => $data->codigo_radicado
        ], 200);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
 

 
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
 
    
    public function contacto(ContactoWebRequest $request)
    {
        $data = [
            'nombre'   => $request->nombre,
            'empresa'  => $request->empresa,
            'telefono' => $request->telefono,
            'email'    => $request->email,
            'mensaje'  => $request->mensaje,
        ];
        
    
        $emailAdmin = 'comercialsetasplast6@gmail.com';
        $emailSolicitante = $request->email;
    
        // Notificar al administrador
        Notification::route('mail', $emailAdmin)
            ->notify(new ContactoNotificacion($data, 'admin', $emailSolicitante));
    
        // Confirmar al usuario solicitante
        Notification::route('mail', $emailSolicitante)
            ->notify(new ContactoNotificacion($data, 'usuario', $emailAdmin));
    
        return response()->json(['message' => 'Correo enviado correctamente'], 200);
    }


    /**
     * Cambia el estado de una PQR.
     */
    public function cambiarEstado(Request $request, $id)
{
    $pqr = Pqr::findOrFail($id);
    $pqr->estado_id = $request->estado_id;
    $pqr->save();

    return response()->json(['message' => 'Estado actualizado correctamente']);
}

    /**
     * Asigna una PQR a un usuario.
     */
    public function asignarArea(Request $request, $id)
    {
        $request->validate([
            'asignado_a' => 'required|exists:users,id'
        ]);
    
        $pqr = Pqr::findOrFail($id);
        $pqr->asignado_a = $request->asignado_a;
        $pqr->save();
    
        // Notificar al usuario asignado
        $userAsignado = \App\Models\User::find($request->asignado_a);
        if ($userAsignado) {
            $userAsignado->notify(new PqrAsignadanotificacion($pqr));
        }
    
        return response()->json(['message' => 'PQR asignada correctamente al usuario']);
    }
    public function responder(Request $request, $id)
{
    $request->validate([
        'respuesta' => 'required|string|min:10',
    ]);

    $pqr = Pqr::findOrFail($id);

    if ($pqr->asignado_a !== auth()->id()) {
        return response()->json(['message' => 'No autorizado'], 403);
    }

    $pqr->respuesta = $request->respuesta;
    $pqr->save();

    return response()->json(['message' => 'Respuesta guardada correctamente']);
}

    
    
}
