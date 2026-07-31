<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\ChatbotCorreoCliente;
use App\Models\Crm\Cliente;
use App\Models\Crm\Cotizacion;
use App\Models\Crm\SeguimientoCliente;
use App\RolEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ChatbotGestionComercialController extends Controller
{
    public function buscarClientes(Request $request)
    {
        $datos = $request->validate(['q' => 'required|string|min:2|max:100']);
        $user = $request->user();
        $q = trim($datos['q']);

        $clientes = Cliente::query()
            ->with(['usuario:id,name,apellidos', 'ultimaGestion:id,cliente_id,created_at'])
            ->when(!$this->esPrivilegiado($user), fn ($query) => $query->where('user_id', $user->id))
            ->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('nit', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get();

        return response()->json(['clientes' => $clientes]);
    }

    public function cotizaciones(Request $request)
    {
        $user = $request->user();
        $items = Cotizacion::query()
            ->with(['cliente:id,nombre,email', 'responsable:id,name,apellidos'])
            ->when(!$this->esPrivilegiado($user), fn ($q) => $q->where(fn ($q) => $q
                ->where('responsable_id', $user->id)->orWhere('user_id', $user->id)))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado_aprobacion', $estado))
            ->orderByDesc('created_at')->paginate(15);

        return response()->json($items);
    }

    public function decidirCotizacion(Request $request, Cotizacion $cotizacion)
    {
        $datos = $request->validate([
            'decision' => ['required', Rule::in(['aprobar', 'rechazar'])],
            'motivo' => 'nullable|required_if:decision,rechazar|string|max:1000',
        ]);

        abort_unless($cotizacion->responsable_id === $request->user()->id, 403, 'Solo el responsable asignado puede decidir esta cotización.');
        abort_unless($cotizacion->estado_aprobacion === 'pendiente', 422, 'La cotización ya fue decidida.');

        $aprobada = $datos['decision'] === 'aprobar';
        $cotizacion->update([
            'estado_aprobacion' => $aprobada ? 'aprobada' : 'rechazada',
            'aprobado_por' => $request->user()->id,
            'aprobado_at' => now(),
            'motivo_rechazo' => $aprobada ? null : $datos['motivo'],
        ]);

        return response()->json(['message' => $aprobada ? 'Cotización aprobada.' : 'Cotización rechazada.', 'cotizacion' => $cotizacion]);
    }

    public function clientesSinGestion(Request $request)
    {
        $datos = $request->validate(['dias' => 'nullable|integer|min:1|max:3650']);
        $dias = $datos['dias'] ?? 30;
        $user = $request->user();

        $clientes = Cliente::query()
            ->with(['usuario:id,name,apellidos', 'ultimaGestion:id,cliente_id,created_at'])
            ->whereNotNull('email')->where('email', '<>', '')
            ->when(!$this->esPrivilegiado($user), fn ($q) => $q->where('user_id', $user->id))
            ->whereDoesntHave('seguimientos', fn ($q) => $q->where('created_at', '>=', now()->subDays($dias)))
            ->withMax('seguimientos', 'created_at')
            ->orderBy('seguimientos_max_created_at')
            ->paginate(20);

        return response()->json($clientes);
    }

    public function enviarCorreo(Request $request, Cliente $cliente)
    {
        $datos = $request->validate(['asunto' => 'required|string|max:180', 'mensaje' => 'required|string|max:10000']);
        abort_unless($this->esPrivilegiado($request->user()) || $cliente->user_id === $request->user()->id, 403);

        $registro = ChatbotCorreoCliente::create([
            'cliente_id' => $cliente->id, 'user_id' => $request->user()->id,
            'destinatario' => $cliente->email, 'asunto' => $datos['asunto'], 'mensaje' => $datos['mensaje'],
            'estado' => 'procesando',
        ]);

        try {
            Mail::raw($datos['mensaje'], fn ($mail) => $mail->to($cliente->email, $cliente->nombre)->subject($datos['asunto']));
            $registro->update(['estado' => 'enviado', 'enviado_at' => now()]);
            SeguimientoCliente::create([
                'cliente_id' => $cliente->id, 'user_id' => $request->user()->id,
                'tipo_contacto' => 'Email', 'estado' => 'Contactado',
                'comentario' => "Correo enviado desde el chatbot: {$datos['asunto']}",
            ]);
        } catch (\Throwable $e) {
            $registro->update(['estado' => 'fallido', 'error' => $e->getMessage()]);
            report($e);
            return response()->json(['message' => 'No se pudo enviar el correo.', 'correo' => $registro], 502);
        }

        return response()->json(['message' => 'Correo enviado y gestión registrada.', 'correo' => $registro]);
    }

    private function esPrivilegiado($user): bool
    {
        return $user->role_id == RolEnum::ADMINISTRADOR->value || $user->esResponsableDeSuDepartamento();
    }

}
