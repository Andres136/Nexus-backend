<?php

namespace App\Services\Crm;

use App\Models\Crm\ChatbotConversacion;
use App\Models\Crm\ChatbotConfiguracion;
use App\Models\Crm\Cliente;
use App\Models\Crm\Cotizacion;
use App\Models\Crm\CotizacionDetalles;
use App\Models\Crm\empresa;
use App\Models\Departamentos;
use App\Models\User;
use App\Notifications\Crm\ChatbotCotizacionPendienteNotificacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatbotCotizacionService
{
    private const OBSERVACIONES = "El precio ofertado es para pago a treinta (30) días calendario.\n\nTiempo de entrega: quince (15) a veinte (20) días para el primer pedido y tres (3) a seis (6) días para pedidos posteriores.\n\nTodos los artículos tienen garantía por defectos de fabricación. Los productos son fabricados con materiales biodegradables y 100% reciclables.";

    public function __construct(private readonly CotizacionCalculoService $calculo) {}

    public function identificarCliente(ChatbotConversacion $conversacion): string
    {
        if ($conversacion->cliente_id) {
            $cliente = Cliente::find($conversacion->cliente_id);
            if ($cliente) return $this->clienteEncontrado($cliente);
        }

        if (!$conversacion->email_lead && !$conversacion->nit_lead && !$conversacion->telefono_lead) {
            return 'CLIENTE_NO_IDENTIFICADO. Faltan correo, NIT o teléfono para buscar al visitante en el CRM.';
        }

        $cliente = Cliente::query()
            ->when($conversacion->email_lead, fn ($q, $email) => $q->orWhereRaw('LOWER(email) = ?', [Str::lower(trim($email))]))
            ->when($conversacion->nit_lead, fn ($q, $nit) => $q->orWhere('nit', trim($nit)))
            ->when($conversacion->telefono_lead, function ($q, $telefono) {
                $normalizado = preg_replace('/\D+/', '', $telefono);
                if ($normalizado) $q->orWhere('telefono', 'like', "%{$normalizado}%");
            })
            ->first();

        if ($cliente) {
            $conversacion->update(['cliente_id' => $cliente->id]);
            return $this->clienteEncontrado($cliente);
        }

        $faltantes = collect([
            'nombre' => $conversacion->nombre_lead,
            'correo' => $conversacion->email_lead,
            'teléfono' => $conversacion->telefono_lead,
            'NIT o cédula' => $conversacion->nit_lead,
            'dirección' => $conversacion->direccion_lead,
        ])->filter(fn ($valor) => blank($valor))->keys()->values();

        if ($faltantes->isNotEmpty()) {
            return 'CLIENTE_NO_IDENTIFICADO. Para registrarlo y cotizar faltan: ' . $faltantes->implode(', ') . '. Solicita solamente el siguiente dato faltante.';
        }

        $responsable = $this->responsablePredeterminado();
        if (!$responsable) {
            return 'CLIENTE_NO_IDENTIFICADO. No existe un responsable comercial configurado; escala la conversación a un humano.';
        }

        $cliente = Cliente::create([
            'nombre' => $conversacion->nombre_lead,
            'email' => $conversacion->email_lead,
            'telefono' => $conversacion->telefono_lead,
            'direccion' => $conversacion->direccion_lead,
            'nit' => $conversacion->nit_lead,
            'user_id' => $responsable->id,
        ]);
        $conversacion->update(['cliente_id' => $cliente->id]);

        return $this->clienteEncontrado($cliente) . ' El cliente fue registrado en el CRM.';
    }

    public function consultarReferencias(array $args): string
    {
        $descripcion = trim((string) ($args['descripcion'] ?? ''));
        if ($descripcion === '') return 'REFERENCIA_NO_ENCONTRADA. Falta la descripción del producto.';

        $tokens = collect(preg_split('/\s+/u', Str::lower($descripcion)))
            ->filter(fn ($t) => mb_strlen($t) >= 3)->unique()->take(5)->values();

        $query = CotizacionDetalles::query()
            ->with('cotizacion:id,cliente_id,empresa_id,empresa,estado_aprobacion,created_at')
            ->whereHas('cotizacion', fn ($q) => $q->where('estado_aprobacion', '<>', 'rechazada'))
            ->where(function ($q) use ($tokens, $descripcion) {
                if ($tokens->isEmpty()) return $q->where('descripcion', 'like', '%' . $descripcion . '%');
                foreach ($tokens as $token) $q->orWhere('descripcion', 'like', "%{$token}%");
            })
            ->where(fn ($q) => $q->where('precio_total', '>', 0)->orWhere('valor_unitario', '>', 0))
            ->latest('id')->limit(80)->get();

        $ancho = (float) ($args['ancho_cm'] ?? 0);
        $largo = (float) ($args['largo_cm'] ?? 0);
        $calibre = (float) ($args['calibre'] ?? 0);

        $referencias = $query->map(function (CotizacionDetalles $detalle) use ($tokens, $ancho, $largo, $calibre) {
            $texto = Str::lower($detalle->descripcion ?? '');
            $coincidencias = $tokens->filter(fn ($token) => str_contains($texto, $token))->count();
            $score = $tokens->count() ? ($coincidencias / $tokens->count()) * 50 : 20;
            $compatibles = true;
            foreach ([[$ancho, $detalle->ancho_cm], [$largo, $detalle->largo_cm], [$calibre, $detalle->calibre]] as [$pedido, $historico]) {
                if ($pedido <= 0) continue;
                if ((float) $historico <= 0) { $compatibles = false; continue; }
                $diferencia = abs($pedido - (float) $historico) / max($pedido, 0.01);
                if ($diferencia <= 0.05) $score += 15;
                elseif ($diferencia <= 0.15) $score += 5;
                else $compatibles = false;
            }
            if ($detalle->cotizacion?->estado_aprobacion === 'aprobada') $score += 5;
            return ['detalle' => $detalle, 'score' => round($score, 1), 'compatible' => $compatibles];
        })->filter(fn ($r) => $r['compatible'] && $r['score'] >= 45)
          ->sortByDesc('score')->take(5)->values();

        if ($referencias->isEmpty()) {
            return 'REFERENCIA_NO_ENCONTRADA. No existe una cotización histórica suficientemente similar. No inventes precios: solicita medidas faltantes o escala a un asesor.';
        }

        $cantidad = max(0, (float) ($args['cantidad'] ?? 0));
        return "REFERENCIAS_ENCONTRADAS. Usa únicamente uno de estos IDs al crear la cotización. Presenta al visitante el valor calculado de la mejor coincidencia:\n" . $referencias->map(function ($r) use ($args, $cantidad) {
            $d = $r['detalle'];
            $item = [
                'ancho_cm' => $args['ancho_cm'] ?? $d->ancho_cm,
                'largo_cm' => $args['largo_cm'] ?? $d->largo_cm,
                'calibre' => $args['calibre'] ?? $d->calibre,
                'cantidad' => $cantidad,
                'precio_total' => $d->precio_total,
                'valor_unitario' => $d->valor_unitario,
            ];
            $valores = $this->calculo->calcular($item);
            return sprintf(
                '- referencia_detalle_id=%d | coincidencia=%s%% | descripción=%s | ancho=%s cm | largo=%s cm | calibre=%s | cantidad=%s | subtotal=%s | total_con_iva=%s | fecha=%s',
                $d->id, $r['score'], $d->descripcion, $d->ancho_cm, $d->largo_cm, $d->calibre,
                $cantidad, $valores['valor_paquete'], $valores['valor_total'], optional($d->cotizacion?->created_at)->format('Y-m-d')
            );
        })->implode("\n");
    }

    public function crear(ChatbotConversacion $conversacion, array $args): string
    {
        if (!$this->visitanteConfirmoResumen($conversacion)) {
            return 'COTIZACION_NO_CREADA. Falta la confirmación expresa del visitante después de presentarle el resumen y el total.';
        }
        if ($conversacion->cotizaciones()->whereIn('estado_aprobacion', ['pendiente', 'aprobada'])->exists()) {
            return 'COTIZACION_NO_CREADA. Esta conversación ya tiene una cotización pendiente o aprobada.';
        }

        $identificacion = $this->identificarCliente($conversacion);
        $conversacion->refresh();
        if (!$conversacion->cliente_id) return 'COTIZACION_NO_CREADA. ' . $identificacion;

        $items = collect($args['items'] ?? []);
        if ($items->isEmpty()) return 'COTIZACION_NO_CREADA. No se recibieron productos.';
        $responsable = $conversacion->user_id ? User::find($conversacion->user_id) : Cliente::find($conversacion->cliente_id)?->usuario;
        if (!$responsable) $responsable = $this->responsablePredeterminado();
        if (!$responsable) return 'COTIZACION_NO_CREADA. No hay responsable disponible para aprobarla.';

        try {
            $cotizacion = DB::transaction(function () use ($conversacion, $args, $items, $responsable) {
                $referencias = $items->map(fn ($item) => CotizacionDetalles::with('cotizacion')->find($item['referencia_detalle_id'] ?? null));
                $empresasIds = $referencias->pluck('cotizacion.empresa_id')->filter()->unique()->values();
                if ($empresasIds->count() > 1) throw new \DomainException('Las referencias seleccionadas pertenecen a empresas diferentes.');
                $empresa = $empresasIds->isNotEmpty() ? empresa::find($empresasIds->first()) : null;
                if (!$empresa) {
                    $nombreHistorico = $referencias->pluck('cotizacion.empresa')->filter()->first();
                    $empresa = $nombreHistorico ? empresa::where('nombre', 'like', "%{$nombreHistorico}%")->first() : null;
                }
                if (!$empresa) throw new \DomainException('No fue posible determinar la empresa real desde la cotización de referencia.');

                $detalles = $items->values()->map(function ($item, $index) {
                    $referencia = CotizacionDetalles::with('cotizacion')->find($item['referencia_detalle_id'] ?? null);
                    if (!$referencia || $referencia->cotizacion?->estado_aprobacion === 'rechazada') {
                        throw new \DomainException('Una referencia histórica no es válida.');
                    }
                    $datos = [
                        'item' => $index + 1,
                        'descripcion' => Str::upper(trim($item['descripcion'] ?? $referencia->descripcion)),
                        'ancho_cm' => $item['ancho_cm'] ?? $referencia->ancho_cm,
                        'largo_cm' => $item['largo_cm'] ?? $referencia->largo_cm,
                        'calibre' => $item['calibre'] ?? $referencia->calibre,
                        'cantidad' => (float) ($item['cantidad'] ?? 0),
                        'precio_total' => (float) $referencia->precio_total,
                        'valor_unitario' => (float) $referencia->valor_unitario,
                        'cliente_clb' => $item['cliente_clb'] ?? null,
                        'cantidad_requerida_kg' => $item['cantidad_requerida_kg'] ?? 0,
                        'observaciones' => 'Precio referenciado del detalle histórico #' . $referencia->id,
                    ];
                    if ($datos['cantidad'] <= 0) throw new \DomainException('La cantidad debe ser mayor a cero.');
                    if (!$this->referenciaCompatible($referencia, $datos)) {
                        throw new \DomainException('La referencia seleccionada no coincide con el producto o sus medidas.');
                    }
                    return array_merge($datos, $this->calculo->calcular($datos));
                });

                $cotizacion = Cotizacion::create([
                    'chatbot_conversacion_id' => $conversacion->id,
                    'cliente_id' => $conversacion->cliente_id,
                    'empresa_id' => $empresa->id,
                    'empresa' => $empresa->nombre,
                    'observaciones' => self::OBSERVACIONES,
                    'user_id' => $responsable->id,
                    'responsable_id' => $responsable->id,
                    'estado_aprobacion' => 'pendiente',
                    'valor_total' => $detalles->sum('valor_total'),
                ]);
                $cotizacion->detalles()->createMany($detalles->all());
                return $cotizacion;
            });
        } catch (\DomainException $e) {
            return 'COTIZACION_NO_CREADA. ' . $e->getMessage();
        }

        try { $responsable->notify(new ChatbotCotizacionPendienteNotificacion($cotizacion->load('cliente'))); } catch (\Throwable $e) { report($e); }

        return sprintf('COTIZACION_CREADA. Número #%d, total con IVA $%s. Quedó pendiente de aprobación por %s. Informa estos datos al visitante.', $cotizacion->id, number_format($cotizacion->valor_total, 0, ',', '.'), $responsable->nombre_completo);
    }

    private function responsablePredeterminado(): ?User
    {
        $config = ChatbotConfiguracion::query()->first();
        return $config?->departamento_id ? Departamentos::find($config->departamento_id)?->responsable : null;
    }

    private function referenciaCompatible(CotizacionDetalles $referencia, array $item): bool
    {
        $tokens = collect(preg_split('/\s+/u', Str::lower($item['descripcion'] ?? '')))
            ->filter(fn ($token) => mb_strlen($token) >= 3)->unique();
        $textoReferencia = Str::lower($referencia->descripcion ?? '');
        if ($tokens->isNotEmpty() && $tokens->filter(fn ($token) => str_contains($textoReferencia, $token))->isEmpty()) {
            return false;
        }
        foreach (['ancho_cm', 'largo_cm', 'calibre'] as $campo) {
            $pedido = (float) ($item[$campo] ?? 0);
            $historico = (float) ($referencia->{$campo} ?? 0);
            if ($pedido > 0 && ($historico <= 0 || abs($pedido - $historico) / max($pedido, .01) > .15)) return false;
        }
        return true;
    }

    private function clienteEncontrado(Cliente $cliente): string
    {
        return "CLIENTE_IDENTIFICADO. cliente_id={$cliente->id}, nombre={$cliente->nombre}. No reveles otros datos del CRM.";
    }

    private function visitanteConfirmoResumen(ChatbotConversacion $conversacion): bool
    {
        $ultimoLead = $conversacion->mensajes()->where('remitente', 'lead')->reorder('id', 'desc')->first();
        if (!$ultimoLead) return false;
        $preguntaPrevia = $conversacion->mensajes()->where('remitente', 'bot')->where('id', '<', $ultimoLead->id)->reorder('id', 'desc')->value('contenido');
        if (!$preguntaPrevia || !preg_match('/confirm|aprueb|autoriz|proced|total/iu', $preguntaPrevia)) return false;
        return (bool) preg_match('/(^|\b)(s[ií]|confirmo|de acuerdo|aprobado|adelante|procedan?)(\b|$)/iu', trim($ultimoLead->contenido));
    }
}
