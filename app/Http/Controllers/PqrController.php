<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactoWebRequest;
use App\Http\Requests\PqrRequest;
use App\Models\Pqr;
use App\Models\User;
use App\Notifications\ContactoNotificacion;
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
        $query = Pqr::with('estado');
    
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
        $data->estado_id = 1; // Estado inicial, puedes cambiarlo según tu lógica
        $data->save();

        $emailSolicitante = $request->email;
        $admins = User::where('role_id', 5)->get();

        // Notificar al administrador
        Notification::send($admins, new pqrNotifycaciones($data, 'admin', $emailSolicitante));

        Notification::route('mail', $emailSolicitante)
        ->notify(new pqrNotifycaciones($data, 'usuario', $admins->first()->email));



        return response()->json(['message' => 'PQR enviada correctamente'], 200);
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

    
}
