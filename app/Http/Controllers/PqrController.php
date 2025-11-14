<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactoWebRequest;
use App\Http\Requests\PqrRequest;
use App\Models\Departamentos;
use App\Models\Documentos;
use App\Models\Pqr;
use App\Models\Procesos;
use App\Models\User;
use App\Notifications\ContactoNotificacion;
use App\Notifications\Crm\AdminNotifications;
use App\Notifications\Crm\PqrAsignadanotificacion;
use App\Notifications\PqrNotificaciones;
use App\Notifications\PqrNotifycaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PhpParser\Node\Stmt\TryCatch;

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
        try {
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

// Notificar a admins
if ($admins->count() > 0) {
    Notification::send($admins, new AdminNotifications($data, 'admin', $emailSolicitante));

    // Notificar a usuario pero solo si existe email de admin
    $adminEmail = $admins->first()->email;
    Notification::route('mail', $emailSolicitante)
        ->notify(new AdminNotifications($data, 'usuario', $adminEmail));
} else {
    // En caso extremo: no hay admins
    Notification::route('mail', $emailSolicitante)
        ->notify(new AdminNotifications($data, 'usuario', 'soporte@nexus.com'));
}


            return response()->json([
                'message' => 'PQR enviada correctamente',
                'codigo_radicado' => $data->codigo_radicado
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al enviar la PQR', 'error' => $e->getMessage()], 500);
        }
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
    public function destroy($id)
    {
        $pqr = Pqr::findOrFail($id);
        $pqr->delete();
        return response()->json(['message' => 'PQR eliminada correctamente.']);
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
       try {
        
        $pqr = Pqr::findOrFail($id);
        $pqr->asignado_a = $request->asignado_a;
        $pqr->save();
    
        $userAsignado = \App\Models\User::find($request->asignado_a);
    
        $departamento = Departamentos::where('nombre', 'Marketing y Comunicaciones')->first();
if (!$departamento) {
    throw new \Exception('No se encontró el departamento Marketing y Comunicaciones');
}

$proceso = Procesos::where('departamento_id', $departamento->id)
    ->where('nombre', 'Procedimiento')
    ->first();
if (!$proceso) {
    throw new \Exception('No se encontró el proceso PROCEDIMIENTO COMUNICACIONES PQR');
}

$documento = Documentos::where('proceso_id', $proceso->id)
    ->orderByDesc('created_at')
    ->first();

$rutaCompleta = $documento ? storage_path('app/public/' . $documento->documento) : null;

    
        // Enviamos notificación profesional
        $userAsignado->notify(new PqrNotifycaciones($pqr, $documento, $rutaCompleta));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al asignar la PQR', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'PQR asignada correctamente']);
    }
 

    /**
     * Responde a una PQR.
     */    
    public function responder(Request $request, $id)
{
    $request->validate([
        'respuesta' => 'required|string|min:10',
    ]);

    $pqr = Pqr::findOrFail($id);

   
    $pqr->respuesta = $request->respuesta;
    $pqr->save();

    return response()->json(['message' => 'Respuesta guardada correctamente']);
}



    
}
