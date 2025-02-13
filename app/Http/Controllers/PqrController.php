<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactoWebRequest;
use App\Http\Requests\PqrRequest;
use App\Models\Pqr;
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
        //
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
        $data->save();

        $emailSolicitante = $request->email;
         $emailAdmin = 'comercialsetasplast6@gmail.com';

        // Notificar al administrador
        Notification::route('mail', $emailAdmin)->notify(new pqrNotifycaciones($data, 'admin', $emailSolicitante));
        Notification::route('mail', $emailSolicitante)->notify(new pqrNotifycaciones($data, 'usuario', $emailAdmin));


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
        $data= [
            'nombre' => $request->nombre,
            'empresa' => $request->empresa,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'mensaje' => $request->mensaje
        ];
    
        Mail::send('emails.contacto', $data, function($message) use ($data){
            $message->to('elveral100@gmail.com')
                    ->subject('Contacto desde la web')
                    ->from('setas@carpediemdistribuidores.com', 'Notificaciones SETASPLAST ') // Remitente autorizado
                    ->replyTo($data['email'], $data['nombre'],$data['telefono'],$data['empresa']); // Para que el admin pueda responder al usuario
        });
    
        return response()->json(['message' => 'Correo enviado correctamente'], 200);
    }
    
}
