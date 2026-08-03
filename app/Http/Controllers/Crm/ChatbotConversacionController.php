<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ChatbotAsignarRequest;
use App\Models\Crm\ChatbotConversacion;
use App\RolEnum;
use App\Services\Crm\ChatbotConversacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotConversacionController extends Controller
{
    public function __construct(private readonly ChatbotConversacionService $conversacionService)
    {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $conversaciones = $this->conversacionService->listar($user, [
            'estado' => $request->input('estado'),
            'sin_asignar' => $request->boolean('sin_asignar'),
        ]);

        return response()->json(array_merge($conversaciones->toArray(), [
            'es_responsable_departamento' => $user->role_id == RolEnum::ADMINISTRADOR->value
                || $user->esResponsableDeSuDepartamento(),
        ]));
    }

    public function show(string $id)
    {
        $conversacion = $this->conversacionService->detalle((int) $id);
        $this->autorizarAcceso($conversacion);

        return response()->json([
            'conversacion' => $conversacion,
            'es_responsable_departamento' => $this->esPrivilegiado(Auth::user()),
        ]);
    }

    public function responder(Request $request, string $id)
    {
        $request->validate(['contenido' => 'required|string|max:2000']);

        $conversacion = ChatbotConversacion::findOrFail($id);
        $this->autorizarAcceso($conversacion);

        $mensaje = $this->conversacionService->responderComoAgente($conversacion, Auth::user(), $request->input('contenido'));

        return response()->json($mensaje);
    }

    public function asignar(ChatbotAsignarRequest $request, string $id)
    {
        $conversacion = ChatbotConversacion::findOrFail($id);
        $conversacion = $this->conversacionService->asignar($conversacion, $request->validated()['user_id'], Auth::user());

        return response()->json([
            'message' => 'Conversación asignada correctamente',
            'conversacion' => $conversacion,
        ]);
    }

    public function asignarAIa(string $id)
    {
        $conversacion = ChatbotConversacion::findOrFail($id);
        $conversacion = $this->conversacionService->asignarAIa($conversacion);

        return response()->json([
            'message' => 'Conversación asignada a la IA correctamente',
            'conversacion' => $conversacion,
        ]);
    }

    public function cerrar(string $id)
    {
        $conversacion = ChatbotConversacion::findOrFail($id);
        $this->autorizarAcceso($conversacion);

        return response()->json($this->conversacionService->cerrar($conversacion));
    }

    private function autorizarAcceso(ChatbotConversacion $conversacion): void
    {
        $user = Auth::user();

        abort_if(!$this->esPrivilegiado($user) && $conversacion->user_id !== $user->id, 403, 'No autorizado');
    }

    private function esPrivilegiado($user): bool
    {
        return $user->role_id == RolEnum::ADMINISTRADOR->value || $user->esResponsableDeSuDepartamento();
    }
}
