<?php
namespace App\Exceptions;

use App\Exceptions\Traslados\EstadoTrasladoInvalidoException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks.
     */
    public function register(): void
    {
        logger()->info('Registering exception handlers');
        $this->renderable(function (
            EstadoTrasladoInvalidoException $e,
            Request $request
        ) {
            // 🌐 Navegador / Email
            if (! $request->expectsJson()) {
                return response()
                    ->view('errors.estado-traslado', [
                        'mensaje' => $e->getMessage(),
                    ], Response::HTTP_CONFLICT);
            }

            // 🔌 API / Frontend
            return response()->json([
                'message' => $e->getMessage(),
                'type'    => 'ESTADO_TRASLADO_INVALIDO',
            ], Response::HTTP_CONFLICT);
        });
    }
}
